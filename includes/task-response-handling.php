<?php
/**
 * Generate provider response posts for new tasks.
 *
 * When a new maintenance task is created, generates a draft response post
 * for each service provider with a unique token for access.
 * Includes comprehensive validation and error handling.
 *
 * @since 1.0
 * @param int     $post_id The ID of post being saved.
 * @param WP_Post $post    The post object being saved.
 * @param bool    $update  Whether this is an update to an existing post.
 * @uses get_terms()
 * @uses wp_generate_password()
 * @uses wp_insert_post()
 * @uses is_wp_error()
 * @uses error_log()
 * @hook save_post
 */
function upkeepify_generate_provider_tokens($post_id, $post, $update) {
    try {
        // Skip auto-saves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Validate post object
        if (!isset($post) || !is_a($post, 'WP_Post')) {
            error_log('Upkeepify Task Response Error: Invalid post object provided');
            return;
        }

        // Validate post ID
        if (!is_numeric($post_id) || $post_id <= 0) {
            error_log('Upkeepify Task Response Error: Invalid post ID: ' . $post_id);
            return;
        }

        // Check if this is a UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS post type
        if ($post->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
            return;
        }

        // Verify post is being published (not just saved as draft)
        if ($post->post_status !== 'publish') {
            if (WP_DEBUG) {
                error_log('Upkeepify Task Response: Post ID ' . $post_id . ' is not published (status: ' . $post->post_status . '), skipping token generation');
            }
            return;
        }

        // Public submissions start as pending. When an admin later publishes the
        // task, save_post runs with $update=true, so guard by existing responses
        // instead of skipping all updates.
        if ( upkeepify_provider_responses_exist( $post_id ) ) {
            if (WP_DEBUG) {
                error_log('Upkeepify Task Response: Provider responses already exist for task ID ' . $post_id . ', skipping token generation');
            }
            return;
        }

        // When trustee approval is configured, open the task-approval gate
        // instead of inviting contractors immediately. Contractors are invited
        // by upkeepify_trigger_step_completion() once the threshold is met.
        if ( function_exists( 'upkeepify_trustee_approval_enabled' ) && upkeepify_trustee_approval_enabled() ) {
            $already_approved = get_post_meta( $post_id, UPKEEPIFY_META_KEY_TASK_APPROVED_TASK_AT, true );
            if ( ! $already_approved ) {
                upkeepify_initiate_trustee_task_approval( $post_id );
                if ( WP_DEBUG ) {
                    error_log( 'Upkeepify Task Response: Task ID ' . $post_id . ' sent to trustee approval gate.' );
                }
                return;
            }
        }

        if (WP_DEBUG) {
            error_log('Upkeepify Task Response: Generating provider tokens for task ID ' . $post_id);
        }

        // Fetch all service providers capable of performing the task
        $service_providers = get_terms([
            'taxonomy' => UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
            'hide_empty' => false,
            'fields' => 'all'
        ]);

        // Check for errors in fetching providers
        if (is_wp_error($service_providers)) {
            $error_message = $service_providers->get_error_message();
            error_log('Upkeepify Task Response Error: Failed to fetch service providers: ' . $error_message);
            return;
        }

        // Validate we have providers
        if (empty($service_providers)) {
            error_log('Upkeepify Task Response Warning: No service providers found for task ID ' . $post_id);
            return;
        }

        // Get the task's categories so we can filter providers by relevance.
        $task_category_ids = wp_get_object_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_CATEGORY, array('fields' => 'ids'));
        if (is_wp_error($task_category_ids)) {
            $task_category_ids = array();
        }

        $created_count = 0;
        $error_count = 0;

        foreach ($service_providers as $provider) {
            // Validate provider object
            if (!is_a($provider, 'WP_Term') || !isset($provider->term_id) || !isset($provider->name)) {
                error_log('Upkeepify Task Response Error: Invalid provider object encountered');
                $error_count++;
                continue;
            }

            // Filter by category: if a provider has associated categories configured, only
            // invite them when the task overlaps. Providers with no categories set receive
            // all tasks (backwards-compatible default).
            $provider_categories = get_term_meta($provider->term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES, true);
            if (!empty($provider_categories) && is_array($provider_categories) && !empty($task_category_ids)) {
                $overlap = array_intersect(array_map('intval', $provider_categories), $task_category_ids);
                if (empty($overlap)) {
                    if (WP_DEBUG) {
                        error_log('Upkeepify Task Response: Skipping provider "' . $provider->name . '" — no category match for task ID ' . $post_id);
                    }
                    continue;
                }
            }

            // Generate a unique token for each provider
            $token = wp_generate_password(20, false);

            if (empty($token)) {
                error_log('Upkeepify Task Response Error: Failed to generate token for provider ID ' . $provider->term_id);
                $error_count++;
                continue;
            }

            $token_validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_RESPONSE_TOKEN, $token);
            if (is_wp_error($token_validation)) {
                error_log('Upkeepify Task Response Error: Invalid token generated for provider ID ' . $provider->term_id);
                $error_count++;
                continue;
            }

            // Validate metadata before assignment
            $task_id = intval($post_id);
            $provider_id = intval($provider->term_id);

            $task_id_validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, $task_id);
            $provider_id_validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_PROVIDER_ID, $provider_id);

            if (is_wp_error($task_id_validation) || is_wp_error($provider_id_validation)) {
                error_log('Upkeepify Task Response Error: Invalid metadata values (task_id: ' . $task_id . ', provider_id: ' . $provider_id . ')');
                $error_count++;
                continue;
            }

            // Token expires UPKEEPIFY_TOKEN_EXPIRY_DAYS days from now.
            $token_expires = time() + (UPKEEPIFY_TOKEN_EXPIRY_DAYS * DAY_IN_SECONDS);

            // Prepare post data for the provider's response post
            $provider_response_data = [
                'post_title'   => 'Response for Task #' . $task_id . ' - Provider: ' . $provider->name,
                'post_status'  => 'draft', // Start as a draft
                'post_type'    => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
                'post_author'  => get_current_user_id(),
                'meta_input'   => [
                    UPKEEPIFY_META_KEY_RESPONSE_TASK_ID      => $task_id,
                    UPKEEPIFY_META_KEY_PROVIDER_ID           => $provider_id,
                    UPKEEPIFY_META_KEY_RESPONSE_TOKEN        => $token,
                    UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES => $token_expires,
                ],
            ];

            // Insert the provider's response post
            $response_post_id = wp_insert_post($provider_response_data, true);

            // Check for errors
            if (is_wp_error($response_post_id)) {
                $error_message = $response_post_id->get_error_message();
                error_log('Upkeepify Task Response Error: Failed to create response post for provider "' . $provider->name . '": ' . $error_message);
                $error_count++;
            } elseif ($response_post_id === 0) {
                error_log('Upkeepify Task Response Error: Failed to create response post for provider "' . $provider->name . '" (unknown error)');
                $error_count++;
            } else {
                $created_count++;
                if (WP_DEBUG) {
                    error_log('Upkeepify Task Response: Successfully created response post ID ' . $response_post_id . ' for provider "' . $provider->name . '"');
                }

                // Send the tokenized invite email to the provider.
                $provider_email = get_term_meta($provider->term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, true);
                if (!empty($provider_email)) {
                    upkeepify_send_contractor_invite(
                        $provider_email,
                        $provider->name,
                        $post,
                        $token,
                        $response_post_id
                    );
                } elseif (WP_DEBUG) {
                    error_log('Upkeepify Task Response: No email address for provider "' . $provider->name . '" — invite not sent');
                }
            }
        }

        // Log summary
        if (WP_DEBUG) {
            error_log('Upkeepify Task Response Summary: Created ' . $created_count . ' response posts, ' . $error_count . ' errors for task ID ' . $post_id);
        }

    } catch (Exception $e) {
        error_log('Upkeepify Task Response Exception: ' . $e->getMessage());
    }
}

/**
 * Check whether provider response posts already exist for a task.
 *
 * @since 1.0
 * @param int $task_id The maintenance task post ID.
 * @return bool True when at least one provider response already exists.
 */
function upkeepify_provider_responses_exist( $task_id ) {
    $existing_responses = get_posts(
        array(
            'post_type'      => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => UPKEEPIFY_META_KEY_RESPONSE_TASK_ID,
                    'value'   => intval( $task_id ),
                    'compare' => '=',
                ),
            ),
        )
    );

    return ! empty( $existing_responses );
}

add_action('save_post', 'upkeepify_generate_provider_tokens', 10, 3);
