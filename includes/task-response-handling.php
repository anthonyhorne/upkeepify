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

        // Skip if post is being updated (we only want to create on first publish)
        if ($update) {
            return;
        }

        // Verify post is being published (not just saved as draft)
        if ($post->post_status !== 'publish') {
            if (WP_DEBUG) {
                error_log('Upkeepify Task Response: Post ID ' . $post_id . ' is not published (status: ' . $post->post_status . '), skipping token generation');
            }
            return;
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

        $created_count = 0;
        $error_count = 0;

        foreach ($service_providers as $provider) {
            // Validate provider object
            if (!is_a($provider, 'WP_Term') || !isset($provider->term_id) || !isset($provider->name)) {
                error_log('Upkeepify Task Response Error: Invalid provider object encountered');
                $error_count++;
                continue;
            }

            // Generate a unique token for each provider
            $token = wp_generate_password(20, false);

            if (empty($token)) {
                error_log('Upkeepify Task Response Error: Failed to generate token for provider ID ' . $provider->term_id);
                $error_count++;
                continue;
            }

            // Validate metadata before assignment
            $task_id = intval($post_id);
            $provider_id = intval($provider->term_id);

            if ($task_id <= 0 || $provider_id <= 0) {
                error_log('Upkeepify Task Response Error: Invalid metadata values (task_id: ' . $task_id . ', provider_id: ' . $provider_id . ')');
                $error_count++;
                continue;
            }

            // Prepare post data for the provider's response post
            $provider_response_data = [
                'post_title'   => 'Response for Task #' . $task_id . ' - Provider: ' . $provider->name,
                'post_status'  => 'draft', // Start as a draft
                'post_type'    => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
                'post_author'  => get_current_user_id(),
                'meta_input'   => [
                    UPKEEPIFY_META_KEY_RESPONSE_TASK_ID => $task_id, // ID of the maintenance task
                    UPKEEPIFY_META_KEY_PROVIDER_ID => $provider_id, // ID of the service provider
                    UPKEEPIFY_META_KEY_RESPONSE_TOKEN => $token, // Unique token for the provider to edit this response
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

add_action('save_post', 'upkeepify_generate_provider_tokens', 10, 3);
