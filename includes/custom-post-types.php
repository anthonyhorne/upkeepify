<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Post Types
 *
 * Registers the Maintenance Tasks custom post type with WordPress.
 * This post type is used to manage maintenance tasks throughout the complex.
 *
 * @since 1.0
 * @uses register_post_type()
 * @uses upkeepify_add_notification()
 * @hook init
 */
function upkeepify_register_maintenance_tasks_post_type() {
    // Register the Maintenance Tasks CPT
    $args_maintenance_tasks = array(
        'public' => true,
        'label'  => 'Maintenance Tasks',
        'supports' => array('title', 'editor', 'custom-fields', 'comments'),
        'show_in_rest' => true, // Enables Gutenberg support
        'menu_icon' => 'dashicons-hammer', // Custom dashicon for the menu
        'has_archive' => true,
        'rewrite' => array('slug' => 'maintenance-tasks'), // Custom slug for this CPT
    );
    $maintenance_tasks_registered = register_post_type(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, $args_maintenance_tasks);

    // Check if the post type registration was successful
    if ($maintenance_tasks_registered) {
        // Add a success notification
        //upkeepify_add_notification('Maintenance Tasks custom post type registered successfully.', 'success');
    } else {
        // Add an error notification
        upkeepify_add_notification('Failed to register Maintenance Tasks custom post type.', 'error');
    }

}

add_action('init', 'upkeepify_register_maintenance_tasks_post_type');

/**
 * Register the 'Nearest Unit' meta box for Maintenance Tasks.
 *
 * Adds a meta box to the Maintenance Tasks post edit screen that allows
 * selection of the nearest unit number.
 *
 * @since 1.0
 * @uses add_meta_box()
 * @hook add_meta_boxes
 */
function upkeepify_add_nearest_unit_meta_box() {
    add_meta_box(
        UPKEEPIFY_META_BOX_NEAREST_UNIT, // Unique ID for the meta box
        __('Nearest Unit', 'upkeepify'), // Meta box title
        'upkeepify_nearest_unit_meta_box_callback', // Callback function to display the fields
        UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, // Post type where the meta box will be shown
        'side', // Context where the box will show ('normal', 'side', 'advanced')
        'default' // Priority within the context where the boxes should show ('high', 'low', 'default')
    );
}
add_action('add_meta_boxes', 'upkeepify_add_nearest_unit_meta_box');

/**
 * Display the 'Nearest Unit' dropdown in the meta box.
 *
 * Callback function that renders the nearest unit selection dropdown.
 * Generates dropdown options based on the number of units setting.
 *
 * @since 1.0
 * @param WP_Post $post The post object being edited.
 * @uses wp_nonce_field()
 * @uses get_post_meta()
 * @uses get_option()
 * @uses selected()
 */
function upkeepify_nearest_unit_meta_box_callback($post) {
    // Security nonce for verification
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_NEAREST_UNIT_SAVE, UPKEEPIFY_NONCE_NEAREST_UNIT);

    // Retrieve current 'Nearest Unit' value
    $nearest_unit_value = get_post_meta($post->ID, UPKEEPIFY_META_KEY_NEAREST_UNIT, true);

    // Fetch 'Number of Units' setting from cache, default to 10 if not set
    $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    $number_of_units = isset($settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS]) ? $settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS] : 10;

    // Output the dropdown for selecting the nearest unit
    echo '<select name="upkeepify_nearest_unit" id="upkeepify_nearest_unit" class="postbox">';
    for ($i = 1; $i <= $number_of_units; $i++) {
        echo '<option value="' . esc_attr($i) . '"' . selected($nearest_unit_value, $i, false) . '>' . esc_html($i) . '</option>';
    }
    echo '</select>';
}

/**
 * Save the selected 'Nearest Unit' when the post is saved.
 *
 * Validates nonce, permissions, and autosave status before saving
 * the nearest unit meta data to the post.
 *
 * @since 1.0
 * @param int $post_id The ID of the post being saved.
 * @uses wp_verify_nonce()
 * @uses current_user_can()
 * @uses update_post_meta()
 * @uses sanitize_text_field()
 * @hook save_post
 */
function upkeepify_save_nearest_unit_meta_box_data($post_id) {
    // Verify nonce to prevent CSRF attacks
    // Nonce must be present and valid for this specific action
    if (!isset($_POST['upkeepify_nearest_unit_nonce']) ||
        !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_NEAREST_UNIT], UPKEEPIFY_NONCE_ACTION_NEAREST_UNIT_SAVE)) {
        return;
    }

    // Prevent saving during autosave to avoid overwriting user's work
    // Autosave happens automatically and shouldn't trigger this save logic
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verify current user has permission to edit this post
    // This prevents unauthorized users from modifying task metadata
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Only update if the nearest unit field was submitted in the form
    // This check prevents overwriting with empty values during partial saves
    if (isset($_POST['upkeepify_nearest_unit'])) {
        $nearest_unit = intval($_POST['upkeepify_nearest_unit']);
        $validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_NEAREST_UNIT, $nearest_unit);

        if (is_wp_error($validation)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Validation: ' . $validation->get_error_message());
            }
            return;
        }

        update_post_meta($post_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, $nearest_unit);
    }
}
add_action('save_post', 'upkeepify_save_nearest_unit_meta_box_data');

/**
 * Register the 'Rough Estimate' meta box for Maintenance Tasks.
 *
 * Adds a meta box to the Maintenance Tasks post edit screen that allows
 * entry of a rough cost estimate for the task.
 *
 * @since 1.0
 * @uses add_meta_box()
 * @hook add_meta_boxes
 */
function upkeepify_add_rough_estimate_meta_box() {
    add_meta_box(
        UPKEEPIFY_META_BOX_ROUGH_ESTIMATE, // Unique ID for the meta box
        __('Rough Estimate', 'upkeepify'), // Meta box title
        'upkeepify_rough_estimate_meta_box_callback', // Callback function to display the field
        UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, // Post type where the meta box will be shown
        'side', // Context where the box will show ('normal', 'side', 'advanced')
        'default' // Priority within the context where the boxes should show ('high', 'low', 'default')
    );
}
add_action('add_meta_boxes', 'upkeepify_add_rough_estimate_meta_box');

/**
 * Display the 'Rough Estimate' field in the meta box.
 *
 * Callback function that renders the rough estimate input field.
 * Displays the current value and a descriptive help text.
 *
 * @since 1.0
 * @param WP_Post $post The post object being edited.
 * @uses wp_nonce_field()
 * @uses get_post_meta()
 */
function upkeepify_rough_estimate_meta_box_callback($post) {
    // Security nonce for verification
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_ROUGH_ESTIMATE_SAVE, UPKEEPIFY_NONCE_ROUGH_ESTIMATE);

    // Retrieve current 'Rough Estimate' value
    $rough_estimate_value = get_post_meta($post->ID, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, true);

    // Output the field for entering the rough estimate
    echo '<label for="upkeepify_rough_estimate">' . __('Rough Estimate', 'upkeepify') . ':</label>';
    echo '<input type="text" id="upkeepify_rough_estimate" name="upkeepify_rough_estimate" value="' . esc_attr($rough_estimate_value) . '" class="widefat">';
    echo '<p class="description">' . __('Provide a rough estimate for the task.', 'upkeepify') . '</p>';
}

/**
 * Save the 'Rough Estimate' when the post is saved.
 *
 * Validates nonce, permissions, and autosave status before saving
 * the rough estimate meta data to the post.
 *
 * @since 1.0
 * @param int $post_id The ID of the post being saved.
 * @uses wp_verify_nonce()
 * @uses current_user_can()
 * @uses update_post_meta()
 * @uses sanitize_text_field()
 * @hook save_post
 */
function upkeepify_save_rough_estimate_meta_box_data($post_id) {
    // Verify nonce to prevent CSRF attacks
    // Nonce must be present and valid for this specific action
    if (!isset($_POST['upkeepify_rough_estimate_nonce']) ||
        !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_ROUGH_ESTIMATE], UPKEEPIFY_NONCE_ACTION_ROUGH_ESTIMATE_SAVE)) {
        return;
    }

    // Prevent saving during autosave to avoid overwriting user's work
    // Autosave happens automatically and shouldn't trigger this save logic
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verify current user has permission to edit this post
    // This prevents unauthorized users from modifying task metadata
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Only update if rough estimate field was submitted in the form
    // This check prevents overwriting with empty values during partial saves
    if (isset($_POST['upkeepify_rough_estimate'])) {
        $rough_estimate = sanitize_text_field($_POST['upkeepify_rough_estimate']);
        $validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, $rough_estimate);

        if (is_wp_error($validation)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Validation: ' . $validation->get_error_message());
            }
            return;
        }

        update_post_meta($post_id, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, $rough_estimate);
    }
}
add_action('save_post', 'upkeepify_save_rough_estimate_meta_box_data');

/**
 * Register the trustee lifecycle panel on maintenance task edit screens.
 *
 * @since 1.1
 * @hook add_meta_boxes
 */
function upkeepify_add_trustee_lifecycle_meta_box() {
    add_meta_box(
        'upkeepify_trustee_lifecycle',
        __('Trustee Lifecycle', 'upkeepify'),
        'upkeepify_trustee_lifecycle_meta_box_callback',
        UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'upkeepify_add_trustee_lifecycle_meta_box');

/**
 * Fetch provider responses connected to a maintenance task.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return WP_Post[] Provider response posts.
 */
function upkeepify_get_provider_responses_for_task($task_id) {
    return get_posts(
        array(
            'post_type'      => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
            'post_status'    => 'any',
            'posts_per_page' => 50,
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'ASC',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Provider responses are linked to tasks through post meta in the existing schema.
            'meta_query'     => array(
                array(
                    'key'     => UPKEEPIFY_META_KEY_RESPONSE_TASK_ID,
                    'value'   => intval($task_id),
                    'compare' => '=',
                ),
            ),
        )
    );
}

/**
 * Return the display name for a provider response's provider term.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return string Provider name.
 */
function upkeepify_get_response_provider_name($response_id) {
    $provider_id = intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true));
    if (!$provider_id) {
        return __('Unknown provider', 'upkeepify');
    }

    $provider = get_term($provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER);
    if ($provider && !is_wp_error($provider) && !empty($provider->name)) {
        return $provider->name;
    }

    return __('Unknown provider', 'upkeepify');
}

/**
 * Format a stored monetary value for lifecycle display.
 *
 * @since 1.1
 * @param mixed  $amount   Stored numeric amount.
 * @param string $currency Currency symbol.
 * @return string Human-readable amount or dash.
 */
function upkeepify_format_lifecycle_money($amount, $currency) {
    if ($amount === '' || $amount === null) {
        return '&mdash;';
    }

    return esc_html($currency . number_format((float) $amount, 2));
}

/**
 * Render a lifecycle approval button.
 *
 * @since 1.1
 * @param int    $task_id     Maintenance task post ID.
 * @param int    $response_id Provider response post ID.
 * @param string $approval    Approval type: estimate|quote.
 * @param string $label       Button label.
 * @return void
 */
function upkeepify_render_lifecycle_approval_button($task_id, $response_id, $approval, $label) {
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0;">';
    echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_APPROVAL) . '">';
    echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
    echo '<input type="hidden" name="response_id" value="' . esc_attr($response_id) . '">';
    echo '<input type="hidden" name="approval_type" value="' . esc_attr($approval) . '">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);
    echo '<button type="submit" class="button button-secondary">' . esc_html($label) . '</button>';
    echo '</form>';
}

/**
 * Render the trustee-facing response lifecycle panel.
 *
 * @since 1.1
 * @param WP_Post $post Maintenance task post object.
 * @return void
 */
function upkeepify_trustee_lifecycle_meta_box_callback($post) {
    $responses = upkeepify_get_provider_responses_for_task($post->ID);
    $settings  = function_exists('upkeepify_get_setting_cached') ? upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array()) : array();
    $currency  = !empty($settings[UPKEEPIFY_SETTING_CURRENCY]) ? $settings[UPKEEPIFY_SETTING_CURRENCY] : '$';

    $approved_estimate_id = intval(get_post_meta($post->ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true));
    $approved_quote_id    = intval(get_post_meta($post->ID, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, true));

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after a nonce-checked redirect.
    if (isset($_GET['upkeepify_lifecycle'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after a nonce-checked redirect.
        $notice = sanitize_key(wp_unslash($_GET['upkeepify_lifecycle']));
        if ($notice === 'estimate_approved') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Estimate approved. The contractor can now submit a formal quote.', 'upkeepify') . '</p></div>';
        } elseif ($notice === 'quote_approved') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Quote approved. The contractor can now complete the work and upload proof.', 'upkeepify') . '</p></div>';
        }
    }

    echo '<p>' . esc_html__('Approve an estimate before asking for a formal quote, then approve the formal quote before the contractor can mark the job complete.', 'upkeepify') . '</p>';

    if (empty($responses)) {
        echo '<p>' . esc_html__('No contractor responses have been created yet. They are generated when this task is published and matching service providers exist.', 'upkeepify') . '</p>';
        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Provider', 'upkeepify') . '</th>';
    echo '<th>' . esc_html__('Decision', 'upkeepify') . '</th>';
    echo '<th>' . esc_html__('Estimate', 'upkeepify') . '</th>';
    echo '<th>' . esc_html__('Quote', 'upkeepify') . '</th>';
    echo '<th>' . esc_html__('Completion', 'upkeepify') . '</th>';
    echo '<th>' . esc_html__('Trustee action', 'upkeepify') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($responses as $response) {
        $response_id  = intval($response->ID);
        $provider     = upkeepify_get_response_provider_name($response_id);
        $decision     = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, true);
        $estimate     = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true);
        $formal_quote = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true);
        $completed_at = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT, true);

        $decision_label = __('Waiting', 'upkeepify');
        if ($decision === 'accept') {
            $decision_label = __('Accepted', 'upkeepify');
        } elseif ($decision === 'decline') {
            $decision_label = __('Declined', 'upkeepify');
        }

        echo '<tr>';
        echo '<td><strong>' . esc_html($provider) . '</strong><br><small>' . esc_html(sprintf(__('Response #%d', 'upkeepify'), $response_id)) . '</small></td>';
        echo '<td>' . esc_html($decision_label) . '</td>';
        echo '<td>' . wp_kses_post(upkeepify_format_lifecycle_money($estimate, $currency));
        if ($approved_estimate_id === $response_id) {
            echo '<br><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' . esc_html__('Approved', 'upkeepify');
        } elseif ($approved_estimate_id && $decision === 'accept') {
            echo '<br><small>' . esc_html__('Another estimate is approved.', 'upkeepify') . '</small>';
        }
        echo '</td>';
        echo '<td>' . wp_kses_post(upkeepify_format_lifecycle_money($formal_quote, $currency));
        if ($approved_quote_id === $response_id) {
            echo '<br><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' . esc_html__('Approved', 'upkeepify');
        } elseif ($approved_quote_id && $formal_quote !== '') {
            echo '<br><small>' . esc_html__('Another quote is approved.', 'upkeepify') . '</small>';
        }
        echo '</td>';
        echo '<td>';
        if ($completed_at) {
            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($completed_at)));
        } else {
            echo '&mdash;';
        }
        echo '</td>';
        echo '<td>';
        if ($decision === 'accept' && $estimate !== '' && (!$approved_quote_id || $approved_estimate_id === $response_id) && $approved_estimate_id !== $response_id) {
            upkeepify_render_lifecycle_approval_button($post->ID, $response_id, 'estimate', $approved_estimate_id ? __('Switch estimate approval', 'upkeepify') : __('Approve estimate', 'upkeepify'));
        } elseif ($decision === 'accept' && $estimate === '') {
            echo '<small>' . esc_html__('Waiting for estimate.', 'upkeepify') . '</small>';
        }

        if ($formal_quote !== '' && $approved_estimate_id === $response_id && $approved_quote_id !== $response_id && !$completed_at) {
            if ($decision === 'accept' && $estimate !== '') {
                echo $approved_estimate_id === $response_id ? '<br>' : '';
                upkeepify_render_lifecycle_approval_button($post->ID, $response_id, 'quote', $approved_quote_id ? __('Switch quote approval', 'upkeepify') : __('Approve quote', 'upkeepify'));
            }
        } elseif ($formal_quote !== '' && $approved_estimate_id !== $response_id) {
            echo '<small>' . esc_html__('Approve this estimate before approving its quote.', 'upkeepify') . '</small>';
        }

        if ($decision === 'decline') {
            echo '<small>' . esc_html__('No action needed.', 'upkeepify') . '</small>';
        } elseif ($completed_at) {
            echo '<small>' . esc_html__('Completion submitted.', 'upkeepify') . '</small>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Set a task status term by name when that term exists.
 *
 * @since 1.1
 * @param int    $task_id     Maintenance task post ID.
 * @param string $status_name Task status term name.
 * @return void
 */
function upkeepify_set_task_status_by_name($task_id, $status_name) {
    $term = get_term_by('name', $status_name, UPKEEPIFY_TAXONOMY_TASK_STATUS);
    if ($term && !is_wp_error($term)) {
        wp_set_object_terms($task_id, array(intval($term->term_id)), UPKEEPIFY_TAXONOMY_TASK_STATUS);
    }
}

/**
 * Email the contractor when a trustee approval unlocks the next step.
 *
 * @since 1.1
 * @param int    $task_id     Maintenance task post ID.
 * @param int    $response_id Provider response post ID.
 * @param string $approval    Approval type: estimate|quote.
 * @return bool True when email was sent.
 */
function upkeepify_send_trustee_lifecycle_approval_email($task_id, $response_id, $approval) {
    $provider_id    = intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true));
    $provider_email = $provider_id ? get_term_meta($provider_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, true) : '';
    if (!is_email($provider_email)) {
        return false;
    }

    $response_url = function_exists('upkeepify_get_provider_response_url') ? upkeepify_get_provider_response_url($response_id) : null;
    if (!$response_url) {
        return false;
    }

    $task          = get_post($task_id);
    $provider_name = upkeepify_get_response_provider_name($response_id);
    $site_name     = get_bloginfo('name');

    if ($approval === 'estimate') {
        $subject = sprintf(__('[%s] Estimate approved: %s', 'upkeepify'), $site_name, $task ? $task->post_title : __('Maintenance job', 'upkeepify'));
        $heading = __('Estimate Approved', 'upkeepify');
        $message = __('Your estimate has been approved. Please submit a formal quote using your job link.', 'upkeepify');
        $button  = __('Submit formal quote', 'upkeepify');
    } else {
        $subject = sprintf(__('[%s] Quote approved: %s', 'upkeepify'), $site_name, $task ? $task->post_title : __('Maintenance job', 'upkeepify'));
        $heading = __('Quote Approved', 'upkeepify');
        $message = __('Your formal quote has been approved. Once the work is complete, use your job link to upload completion proof.', 'upkeepify');
        $button  = __('Upload completion proof', 'upkeepify');
    }

    $body  = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
    $body .= '<h2>' . esc_html($heading) . '</h2>';
    $body .= '<p>' . sprintf(esc_html__('Hi %s,', 'upkeepify'), esc_html($provider_name)) . '</p>';
    $body .= '<p>' . esc_html($message) . '</p>';
    $body .= '<p style="margin:24px 0;"><a href="' . esc_url($response_url) . '" style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">' . esc_html($button) . '</a></p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__('Or copy this link:', 'upkeepify') . '<br><code style="word-break:break-all;">' . esc_url($response_url) . '</code></p>';
    $body .= '</div>';

    return wp_mail($provider_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
}

/**
 * Handle trustee estimate/quote approvals from the lifecycle panel.
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_APPROVAL}
 */
function upkeepify_admin_post_trustee_lifecycle_approval() {
    $task_id       = isset($_POST['task_id']) ? absint(wp_unslash($_POST['task_id'])) : 0;
    $response_id   = isset($_POST['response_id']) ? absint(wp_unslash($_POST['response_id'])) : 0;
    $approval_type = isset($_POST['approval_type']) ? sanitize_key(wp_unslash($_POST['approval_type'])) : '';

    if (!$task_id || !current_user_can('edit_post', $task_id)) {
        wp_die(esc_html__('Insufficient permissions.', 'upkeepify'));
    }

    check_admin_referer(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);

    $task     = get_post($task_id);
    $response = get_post($response_id);
    if (!$task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS || !$response || $response->post_type !== UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES) {
        wp_die(esc_html__('Invalid lifecycle approval request.', 'upkeepify'));
    }

    $response_task_id = intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true));
    if ($response_task_id !== intval($task_id)) {
        wp_die(esc_html__('This provider response does not belong to this task.', 'upkeepify'));
    }

    $decision = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, true);
    if ($decision !== 'accept') {
        wp_die(esc_html__('Only accepted estimates can be approved.', 'upkeepify'));
    }

    $redirect_status = '';
    if ($approval_type === 'estimate') {
        $estimate = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true);
        if ($estimate === '') {
            wp_die(esc_html__('This response does not have an estimate to approve.', 'upkeepify'));
        }

        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, $response_id);
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_AT, time());
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_BY, get_current_user_id());
        update_post_meta($task_id, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true)));
        upkeepify_send_trustee_lifecycle_approval_email($task_id, $response_id, 'estimate');
        $redirect_status = 'estimate_approved';
    } elseif ($approval_type === 'quote') {
        $formal_quote = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true);
        $approved_estimate_id = intval(get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true));

        if ($formal_quote === '') {
            wp_die(esc_html__('This response does not have a formal quote to approve.', 'upkeepify'));
        }
        if ($approved_estimate_id !== intval($response_id)) {
            wp_die(esc_html__('Approve this estimate before approving its formal quote.', 'upkeepify'));
        }

        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, $response_id);
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_AT, time());
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_BY, get_current_user_id());
        update_post_meta($task_id, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true)));
        upkeepify_set_task_status_by_name($task_id, 'In Progress');
        upkeepify_send_trustee_lifecycle_approval_email($task_id, $response_id, 'quote');
        $redirect_status = 'quote_approved';
    } else {
        wp_die(esc_html__('Unknown lifecycle approval type.', 'upkeepify'));
    }

    $redirect = add_query_arg(
        array(
            'post'                 => $task_id,
            'action'               => 'edit',
            'upkeepify_lifecycle'  => $redirect_status,
        ),
        admin_url('post.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_APPROVAL, 'upkeepify_admin_post_trustee_lifecycle_approval');

/**
 * Register the Responses custom post type.
 *
 * Registers a post type for storing task responses. This post type is
 * hidden from the front-end but visible in the admin dashboard nested
 * under Maintenance Tasks.
 *
 * @since 1.0
 * @uses register_post_type()
 * @hook init
 */
function upkeepify_register_response_post_type() {
    $args = array(
        'public' => false, // Set to false to hide from the front end
        'publicly_queryable' => true, // Allows querying by authorized users
        'show_ui' => true, // Display in the admin dashboard
        'show_in_menu' => 'edit.php?post_type=maintenance_tasks', // Nest under Maintenance Tasks
        'supports' => array('title', 'editor', 'custom-fields'),
        'labels' => array(
            'name' => 'Responses',
            'singular_name' => 'Response',
        ),
    );
    register_post_type(UPKEEPIFY_POST_TYPE_RESPONSES, $args);
}
add_action('init', 'upkeepify_register_response_post_type');

/**
 * Register the Provider Responses custom post type.
 *
 * Stores a draft response per provider, tied to a maintenance task via meta.
 *
 * @since 1.0
 * @uses register_post_type()
 * @hook init
 */
function upkeepify_register_provider_response_post_type() {
    $args = array(
        'public' => false,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=maintenance_tasks',
        'supports' => array('title', 'editor', 'custom-fields'),
        'labels' => array(
            'name' => UPKEEPIFY_LABEL_PROVIDER_RESPONSES,
            'singular_name' => 'Provider Response',
        ),
    );

    register_post_type(UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES, $args);
}
add_action('init', 'upkeepify_register_provider_response_post_type');
