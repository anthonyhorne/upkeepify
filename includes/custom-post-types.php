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
 * Format a stored monetary value for plain-text email copy.
 *
 * @since 1.1
 * @param mixed  $amount   Stored numeric amount.
 * @param string $currency Currency symbol.
 * @return string Human-readable amount or dash.
 */
function upkeepify_format_lifecycle_money_text($amount, $currency) {
    if ($amount === '' || $amount === null) {
        return '-';
    }

    return $currency . number_format((float) $amount, 2);
}

/**
 * Return quote attachment IDs for a provider response.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return int[] Attachment IDs.
 */
function upkeepify_get_response_quote_attachment_ids($response_id) {
    $attachment_ids = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_ATTACHMENTS, true);
    if (empty($attachment_ids)) {
        return array();
    }

    $attachment_ids = is_array($attachment_ids) ? $attachment_ids : array($attachment_ids);
    return array_values(array_filter(array_map('intval', $attachment_ids)));
}

/**
 * Render quote document references for emails/admin screens.
 *
 * @since 1.1
 * @param int[] $attachment_ids Attachment IDs.
 * @return string HTML list of quote document references.
 */
function upkeepify_render_quote_attachment_references($attachment_ids) {
    if (empty($attachment_ids)) {
        return '';
    }

    $items = array();
    foreach ($attachment_ids as $attachment_id) {
        $attachment_id = intval($attachment_id);
        $url = function_exists('wp_get_attachment_url') ? wp_get_attachment_url($attachment_id) : '';
        if ($url) {
            $items[] = '<li><a href="' . esc_url($url) . '">' . esc_html(sprintf(__('Quote document #%d', 'upkeepify'), $attachment_id)) . '</a></li>';
        } else {
            $items[] = '<li>' . esc_html(sprintf(__('Quote document #%d', 'upkeepify'), $attachment_id)) . '</li>';
        }
    }

    return '<ul>' . implode('', $items) . '</ul>';
}

/**
 * Get local file paths for quote document attachments when available.
 *
 * @since 1.1
 * @param int[] $attachment_ids Attachment IDs.
 * @return string[] Local file paths suitable for wp_mail attachments.
 */
function upkeepify_get_quote_attachment_file_paths($attachment_ids) {
    if (empty($attachment_ids) || !function_exists('get_attached_file')) {
        return array();
    }

    $paths = array();
    foreach ($attachment_ids as $attachment_id) {
        $path = get_attached_file(intval($attachment_id));
        if ($path && file_exists($path)) {
            $paths[] = $path;
        }
    }

    return $paths;
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
 * Return whether a provider response token was revoked.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return bool True when revoked.
 */
function upkeepify_is_provider_response_token_revoked($response_id) {
    return (bool) get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_REVOKED_AT, true);
}

/**
 * Validate that a posted contractor token matches and has not been revoked.
 *
 * @since 1.1
 * @param int    $response_id Provider response post ID.
 * @param string $token       Contractor response token.
 * @return bool True when token can be used.
 */
function upkeepify_provider_response_token_matches($response_id, $token) {
    $stored_token = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true);
    if (empty($stored_token) || upkeepify_is_provider_response_token_revoked($response_id)) {
        return false;
    }

    return hash_equals((string) $stored_token, (string) $token);
}

/**
 * Return the admin-visible token state for a provider response.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return string active|expired|revoked|missing
 */
function upkeepify_get_provider_response_token_state($response_id) {
    $token = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true);
    if (empty($token)) {
        return 'missing';
    }

    if (upkeepify_is_provider_response_token_revoked($response_id)) {
        return 'revoked';
    }

    $expires = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES, true);
    if ($expires && time() > intval($expires)) {
        return 'expired';
    }

    return 'active';
}

/**
 * Revoke a contractor response token.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return void
 */
function upkeepify_revoke_provider_response_token($response_id) {
    update_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_REVOKED_AT, time());
}

/**
 * Regenerate a contractor response token and reset its expiry.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID.
 * @return string New token.
 */
function upkeepify_regenerate_provider_response_token($response_id) {
    $token = wp_generate_password(20, false);

    update_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, $token);
    update_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES, time() + (UPKEEPIFY_TOKEN_EXPIRY_DAYS * DAY_IN_SECONDS));
    update_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_REGENERATED_AT, time());
    update_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_REGENERATED_BY, get_current_user_id());
    delete_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_REVOKED_AT);

    return $token;
}

/**
 * Email a regenerated contractor response link.
 *
 * @since 1.1
 * @param int    $task_id     Maintenance task post ID.
 * @param int    $response_id Provider response post ID.
 * @param string $token       New contractor token.
 * @return bool True when sent.
 */
function upkeepify_send_regenerated_provider_token_email($task_id, $response_id, $token) {
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
    $subject       = sprintf(__('[%s] Updated job response link: %s', 'upkeepify'), get_bloginfo('name'), $task ? $task->post_title : __('Maintenance job', 'upkeepify'));

    $body  = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
    $body .= '<h2>' . esc_html__('Updated Job Link', 'upkeepify') . '</h2>';
    $body .= '<p>' . sprintf(esc_html__('Hi %s,', 'upkeepify'), esc_html($provider_name)) . '</p>';
    $body .= '<p>' . esc_html__('The property manager has updated your secure job response link. Please use the new link below; older links will no longer work.', 'upkeepify') . '</p>';
    $body .= '<p style="margin:24px 0;"><a href="' . esc_url($response_url) . '" style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">' . esc_html__('Open job response', 'upkeepify') . '</a></p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__('Or copy this link:', 'upkeepify') . '<br><code style="word-break:break-all;">' . esc_url($response_url) . '</code></p>';
    $body .= '</div>';

    return wp_mail($provider_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
}

/**
 * Render contractor token controls for a provider response row.
 *
 * @since 1.1
 * @param int $task_id     Maintenance task post ID.
 * @param int $response_id Provider response post ID.
 * @return void
 */
function upkeepify_render_provider_token_controls($task_id, $response_id) {
    $state = upkeepify_get_provider_response_token_state($response_id);
    $labels = array(
        'active'  => __('Active', 'upkeepify'),
        'expired' => __('Expired', 'upkeepify'),
        'revoked' => __('Revoked', 'upkeepify'),
        'missing' => __('Missing', 'upkeepify'),
    );
    $expires = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES, true);

    echo '<div style="margin-top:8px;">';
    echo '<small><strong>' . esc_html__('Link:', 'upkeepify') . '</strong> ' . esc_html($labels[$state] ?? $state) . '</small>';
    if ($expires) {
        echo '<br><small>' . esc_html__('Expires:', 'upkeepify') . ' ' . wp_kses_post(upkeepify_format_lifecycle_timestamp($expires)) . '</small>';
    }

    echo '<div style="margin-top:4px;">';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 4px 0 0;">';
    echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_PROVIDER_TOKEN_MANAGE) . '">';
    echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
    echo '<input type="hidden" name="response_id" value="' . esc_attr($response_id) . '">';
    echo '<input type="hidden" name="token_action" value="regenerate">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);
    echo '<button type="submit" class="button button-small">' . esc_html__('Regenerate link', 'upkeepify') . '</button>';
    echo '</form>';

    if ($state !== 'revoked' && $state !== 'missing') {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0;">';
        echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_PROVIDER_TOKEN_MANAGE) . '">';
        echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
        echo '<input type="hidden" name="response_id" value="' . esc_attr($response_id) . '">';
        echo '<input type="hidden" name="token_action" value="revoke">';
        wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);
        echo '<button type="submit" class="button button-small">' . esc_html__('Revoke link', 'upkeepify') . '</button>';
        echo '</form>';
    }
    echo '</div></div>';
}

/**
 * Format a lifecycle timestamp for admin display.
 *
 * @since 1.1
 * @param mixed $timestamp Unix timestamp.
 * @return string Human-readable timestamp or dash.
 */
function upkeepify_format_lifecycle_timestamp($timestamp) {
    if (!$timestamp) {
        return '&mdash;';
    }

    $format = get_option('date_format') . ' ' . get_option('time_format');
    $label  = function_exists('date_i18n') ? date_i18n($format, intval($timestamp)) : date($format, intval($timestamp));

    return esc_html($label);
}

/**
 * Resolve a resident-reported issue after contractor follow-up.
 *
 * @since 1.1
 * @param int    $task_id Maintenance task post ID.
 * @param string $note    Optional trustee resolution note.
 * @return void
 */
function upkeepify_resolve_resident_issue($task_id, $note = '') {
    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT, time());
    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_BY, get_current_user_id());

    $note = sanitize_textarea_field($note);
    if ($note !== '') {
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE, substr($note, 0, 500));
    }

    upkeepify_clear_resident_issue_followup($task_id);
    upkeepify_set_task_status_by_name($task_id, UPKEEPIFY_TASK_STATUS_COMPLETED);
}

/**
 * Determine whether resident email confirmation can still be sent.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return bool True when a resident email and confirmation URL are available.
 */
function upkeepify_task_has_resident_confirmation_route($task_id) {
    $resident_email = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL, true);
    if (!is_email($resident_email)) {
        return false;
    }

    return function_exists('upkeepify_get_resident_confirmation_url') && (bool) upkeepify_get_resident_confirmation_url($task_id);
}

/**
 * Determine whether the trustee can manually close a task lifecycle.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return bool True when manual close controls should be available.
 */
function upkeepify_can_manual_close_task_lifecycle($task_id) {
    if (get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_AT, true)) {
        return false;
    }
    if (get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, true)) {
        return false;
    }
    if (get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true)) {
        return false;
    }
    if (upkeepify_task_has_resident_confirmation_route($task_id)) {
        return false;
    }

    return upkeepify_get_task_lifecycle_status_name($task_id) === UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION;
}

/**
 * Manually close a completed task lifecycle when resident email confirmation is unavailable.
 *
 * @since 1.1
 * @param int    $task_id Maintenance task post ID.
 * @param string $mode    Manual close mode.
 * @param string $note    Optional trustee note.
 * @return void
 */
function upkeepify_manual_close_task_lifecycle($task_id, $mode, $note = '') {
    if (!in_array($mode, array(UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED, UPKEEPIFY_MANUAL_CLOSE_MODE_CLOSED_WITHOUT_CONFIRMATION), true)) {
        $mode = UPKEEPIFY_MANUAL_CLOSE_MODE_CLOSED_WITHOUT_CONFIRMATION;
    }

    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_AT, time());
    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_BY, get_current_user_id());
    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_MODE, $mode);

    $note = sanitize_textarea_field($note);
    if ($note !== '') {
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_NOTE, substr($note, 0, 500));
    }

    if ($mode === UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED) {
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, '1');
        update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, time());
    }

    upkeepify_clear_resident_issue_followup($task_id);
    upkeepify_sync_task_lifecycle_status($task_id);
}

/**
 * Re-open resident confirmation after contractor follow-up.
 *
 * @since 1.1
 * @param int     $task_id   Maintenance task post ID.
 * @param WP_Post $task_post Maintenance task post object.
 * @return bool True when the resident email was sent.
 */
function upkeepify_rerequest_resident_confirmation($task_id, $task_post) {
    $meta_keys = array(
        UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_BY,
        UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE,
    );
    $previous_meta = array();
    foreach ($meta_keys as $meta_key) {
        $previous_meta[$meta_key] = get_post_meta($task_id, $meta_key, true);
    }

    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED);
    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT);
    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE);
    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT);
    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_BY);
    delete_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE);
    upkeepify_clear_resident_issue_followup($task_id);

    $sent = function_exists('upkeepify_send_resident_confirmation_email')
        ? (bool) upkeepify_send_resident_confirmation_email($task_id, $task_post)
        : false;

    if (!$sent) {
        foreach ($previous_meta as $meta_key => $meta_value) {
            if ($meta_value !== '') {
                update_post_meta($task_id, $meta_key, $meta_value);
            }
        }
    } else {
        upkeepify_sync_task_lifecycle_status($task_id);
    }

    return $sent;
}

/**
 * Render trustee controls for resident issue follow-up review.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return void
 */
function upkeepify_render_resident_issue_review_panel($task_id) {
    $followup_status      = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true);
    $followup_response_id = intval(get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, true));

    if ($followup_status !== UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE && $followup_status !== UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED) {
        return;
    }

    $resident_note = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, true);
    $reported_at   = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_REPORTED_AT, true);

    echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__('Resident issue under review', 'upkeepify') . '</strong></p>';
    echo '<p>' . esc_html__('The resident was not satisfied after completion. Review the resident issue and contractor follow-up before closing the lifecycle or asking the resident to confirm again.', 'upkeepify') . '</p>';
    echo '<p><strong>' . esc_html__('Reported:', 'upkeepify') . '</strong> ' . wp_kses_post(upkeepify_format_lifecycle_timestamp($reported_at)) . '</p>';

    if ($resident_note) {
        echo '<p><strong>' . esc_html__('Resident comment:', 'upkeepify') . '</strong><br>' . nl2br(esc_html($resident_note)) . '</p>';
    }

    if ($followup_response_id) {
        $followup_note = get_post_meta($followup_response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_NOTE, true);
        $followup_at   = get_post_meta($followup_response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT, true);
        $followup_photos = get_post_meta($followup_response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_PHOTOS, true);
        $followup_photos = is_array($followup_photos) ? $followup_photos : array();

        echo '<p><strong>' . esc_html__('Contractor:', 'upkeepify') . '</strong> ' . esc_html(upkeepify_get_response_provider_name($followup_response_id)) . '</p>';
        if ($followup_at) {
            echo '<p><strong>' . esc_html__('Follow-up submitted:', 'upkeepify') . '</strong> ' . wp_kses_post(upkeepify_format_lifecycle_timestamp($followup_at)) . '</p>';
        }
        if ($followup_note) {
            echo '<p><strong>' . esc_html__('Contractor follow-up:', 'upkeepify') . '</strong><br>' . nl2br(esc_html($followup_note)) . '</p>';
        }
        if (!empty($followup_photos)) {
            echo '<p>' . esc_html(sprintf(__('%d follow-up photo(s) uploaded.', 'upkeepify'), count($followup_photos))) . '</p>';
        }
    }

    if ($followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE) {
        echo '<p><em>' . esc_html__('Waiting for the contractor to submit their follow-up.', 'upkeepify') . '</em></p>';
        echo '</div>';
        return;
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_FOLLOWUP) . '">';
    echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);
    echo '<p><label for="upkeepify_resident_issue_resolution_note"><strong>' . esc_html__('Trustee note (optional)', 'upkeepify') . '</strong></label><br>';
    echo '<textarea id="upkeepify_resident_issue_resolution_note" name="resolution_note" rows="3" maxlength="500" style="width:100%;max-width:720px;"></textarea></p>';
    echo '<p>';
    echo '<button type="submit" name="followup_action" value="resolve" class="button button-primary">' . esc_html__('Resolve issue and close', 'upkeepify') . '</button> ';
    $resident_email = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL, true);
    $can_rerequest  = is_email($resident_email) && function_exists('upkeepify_get_resident_confirmation_url') && upkeepify_get_resident_confirmation_url($task_id);
    if ($can_rerequest) {
        echo '<button type="submit" name="followup_action" value="rerequest" class="button button-secondary">' . esc_html__('Re-request resident confirmation', 'upkeepify') . '</button>';
    } else {
        echo '<span style="margin-left:8px;color:#646970;">' . esc_html__('No resident email/confirmation link is available for re-request.', 'upkeepify') . '</span>';
    }
    echo '</p>';
    echo '</form>';
    echo '</div>';
}

/**
 * Render manual lifecycle close controls and audit details.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return void
 */
function upkeepify_render_manual_close_panel($task_id) {
    $manual_closed_at = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_AT, true);
    $manual_mode      = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_MODE, true);
    $manual_note      = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_NOTE, true);

    if ($manual_closed_at) {
        $mode_label = $manual_mode === UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED
            ? __('Resident confirmation recorded manually', 'upkeepify')
            : __('Closed without resident confirmation', 'upkeepify');

        echo '<div class="notice notice-success inline"><p><strong>' . esc_html__('Lifecycle manually closed', 'upkeepify') . '</strong></p>';
        echo '<p>' . esc_html($mode_label) . '</p>';
        echo '<p><strong>' . esc_html__('Closed:', 'upkeepify') . '</strong> ' . wp_kses_post(upkeepify_format_lifecycle_timestamp($manual_closed_at)) . '</p>';
        if ($manual_note) {
            echo '<p><strong>' . esc_html__('Trustee note:', 'upkeepify') . '</strong><br>' . nl2br(esc_html($manual_note)) . '</p>';
        }
        echo '</div>';
        return;
    }

    if (!upkeepify_can_manual_close_task_lifecycle($task_id)) {
        return;
    }

    echo '<div class="notice notice-info inline"><p><strong>' . esc_html__('Resident confirmation unavailable', 'upkeepify') . '</strong></p>';
    echo '<p>' . esc_html__('This job is complete, but no resident email/confirmation link is available. Record how the lifecycle was closed.', 'upkeepify') . '</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_TRUSTEE_MANUAL_CLOSE) . '">';
    echo '<input type="hidden" name="task_id" value="' . esc_attr($task_id) . '">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);
    echo '<p><label for="upkeepify_manual_close_note"><strong>' . esc_html__('Trustee note (optional)', 'upkeepify') . '</strong></label><br>';
    echo '<textarea id="upkeepify_manual_close_note" name="manual_close_note" rows="3" maxlength="500" style="width:100%;max-width:720px;"></textarea></p>';
    echo '<p>';
    echo '<button type="submit" name="manual_close_mode" value="' . esc_attr(UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED) . '" class="button button-primary">' . esc_html__('Mark resident confirmed and close', 'upkeepify') . '</button> ';
    echo '<button type="submit" name="manual_close_mode" value="' . esc_attr(UPKEEPIFY_MANUAL_CLOSE_MODE_CLOSED_WITHOUT_CONFIRMATION) . '" class="button button-secondary">' . esc_html__('Close without resident confirmation', 'upkeepify') . '</button>';
    echo '</p>';
    echo '</form>';
    echo '</div>';
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
        } elseif ($notice === 'resident_issue_resolved') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Resident issue resolved and lifecycle closed.', 'upkeepify') . '</p></div>';
        } elseif ($notice === 'resident_confirmation_requested') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Resident confirmation requested again.', 'upkeepify') . '</p></div>';
        } elseif ($notice === 'token_revoked') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Contractor response link revoked.', 'upkeepify') . '</p></div>';
        } elseif ($notice === 'token_regenerated') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Contractor response link regenerated and emailed when a provider email was available.', 'upkeepify') . '</p></div>';
        } elseif ($notice === 'manual_closed') {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('Lifecycle closed manually.', 'upkeepify') . '</p></div>';
        }
    }

    echo '<p>' . esc_html__('Approve an estimate before asking for a formal quote, then approve the formal quote before the contractor can mark the job complete.', 'upkeepify') . '</p>';

    upkeepify_render_resident_issue_review_panel($post->ID);
    upkeepify_render_manual_close_panel($post->ID);

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
        $quote_attachments = upkeepify_get_response_quote_attachment_ids($response_id);
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
        if (!empty($quote_attachments)) {
            echo '<br><small>' . esc_html(sprintf(__('%d quote document(s) uploaded.', 'upkeepify'), count($quote_attachments))) . '</small>';
        }
        if ($approved_quote_id === $response_id) {
            echo '<br><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' . esc_html__('Approved', 'upkeepify');
        } elseif ($approved_quote_id && $formal_quote !== '') {
            echo '<br><small>' . esc_html__('Another quote is approved.', 'upkeepify') . '</small>';
        }
        echo '</td>';
        echo '<td>';
        if ($completed_at) {
            echo wp_kses_post(upkeepify_format_lifecycle_timestamp($completed_at));
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
        upkeepify_render_provider_token_controls($post->ID, $response_id);
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
 * Return the lifecycle status filter/status options.
 *
 * @since 1.1
 * @return array<string,string> Slug to label map.
 */
function upkeepify_get_lifecycle_status_options() {
    return array(
        UPKEEPIFY_TASK_STATUS_SLUG_PENDING_ESTIMATE_APPROVAL       => UPKEEPIFY_TASK_STATUS_PENDING_ESTIMATE_APPROVAL,
        UPKEEPIFY_TASK_STATUS_SLUG_PENDING_QUOTE_APPROVAL          => UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL,
        UPKEEPIFY_TASK_STATUS_SLUG_AWAITING_COMPLETION             => UPKEEPIFY_TASK_STATUS_AWAITING_COMPLETION,
        UPKEEPIFY_TASK_STATUS_SLUG_AWAITING_RESIDENT_CONFIRMATION  => UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION,
        UPKEEPIFY_TASK_STATUS_SLUG_NEEDS_REVIEW                    => UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW,
    );
}

/**
 * Determine the visible task status that matches lifecycle meta.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return string Task status term name.
 */
function upkeepify_get_task_lifecycle_status_name($task_id) {
    $followup_status = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true);
    if ($followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE || $followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED) {
        return UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW;
    }

    $resident_confirmed = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, true);
    $resolved_at        = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT, true);
    $manual_closed_at   = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_AT, true);
    if ($resident_confirmed === '1' || $resolved_at || $manual_closed_at) {
        return UPKEEPIFY_TASK_STATUS_COMPLETED;
    }

    $approved_estimate_id = intval(get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true));
    $approved_quote_id    = intval(get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, true));

    if ($approved_quote_id) {
        $completed_at = get_post_meta($approved_quote_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT, true);
        return $completed_at ? UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION : UPKEEPIFY_TASK_STATUS_AWAITING_COMPLETION;
    }

    if ($approved_estimate_id) {
        return UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL;
    }

    $responses = upkeepify_get_provider_responses_for_task($task_id);
    foreach ($responses as $response) {
        $response_id = intval($response->ID);
        $quote       = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true);
        if ($quote !== '') {
            return UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL;
        }
    }

    foreach ($responses as $response) {
        $response_id = intval($response->ID);
        $decision    = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, true);
        $estimate    = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true);
        if ($decision === 'accept' && $estimate !== '') {
            return UPKEEPIFY_TASK_STATUS_PENDING_ESTIMATE_APPROVAL;
        }
    }

    return UPKEEPIFY_TASK_STATUS_OPEN;
}

/**
 * Sync the public task status taxonomy term to lifecycle state.
 *
 * @since 1.1
 * @param int $task_id Maintenance task post ID.
 * @return string Status name selected for the task.
 */
function upkeepify_sync_task_lifecycle_status($task_id) {
    $status_name = upkeepify_get_task_lifecycle_status_name($task_id);
    upkeepify_set_task_status_by_name($task_id, $status_name);

    return $status_name;
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
 * Email an audit pack when a formal quote is approved.
 *
 * @since 1.1
 * @param int         $task_id     Maintenance task post ID.
 * @param int         $response_id Approved provider response post ID.
 * @param string|null $recipient   Optional explicit recipient email.
 * @return bool Whether the email was sent.
 */
function upkeepify_send_quote_audit_email($task_id, $response_id, $recipient = null) {
    $task = get_post($task_id);
    if (!$task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        return false;
    }

    $settings = function_exists('upkeepify_get_setting_cached') ? upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array()) : array();
    $recipient = $recipient ?: (!empty($settings[UPKEEPIFY_SETTING_AUDIT_EMAIL]) ? $settings[UPKEEPIFY_SETTING_AUDIT_EMAIL] : '');
    if (!$recipient) {
        $recipient = !empty($settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL]) ? $settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL] : get_option('admin_email');
    }
    $recipient = sanitize_email((string) $recipient);
    if (!$recipient || !is_email($recipient)) {
        return false;
    }

    $currency        = !empty($settings[UPKEEPIFY_SETTING_CURRENCY]) ? $settings[UPKEEPIFY_SETTING_CURRENCY] : '$';
    $provider_name   = upkeepify_get_response_provider_name($response_id);
    $estimate        = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true);
    $formal_quote    = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true);
    $quote_note      = get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_NOTE, true);
    $quote_documents = upkeepify_get_response_quote_attachment_ids($response_id);
    $approved_at     = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_AT, true);
    $approved_at     = $approved_at ? intval($approved_at) : time();
    $approved_label  = function_exists('date_i18n')
        ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $approved_at)
        : date('Y-m-d H:i', $approved_at);
    $admin_url       = admin_url('post.php?post=' . intval($task_id) . '&action=edit');

    $subject = sprintf(__('[%s] Approved quote audit pack: %s', 'upkeepify'), get_bloginfo('name'), $task->post_title);
    $body    = '<div style="font-family:Arial,sans-serif;max-width:760px;">';
    $body   .= '<h2>' . esc_html__('Approved Quote Audit Pack', 'upkeepify') . '</h2>';
    $body   .= '<p>' . sprintf(esc_html__('Task: %s', 'upkeepify'), esc_html($task->post_title)) . '</p>';
    $body   .= '<p>' . sprintf(esc_html__('Approved contractor: %s', 'upkeepify'), esc_html($provider_name)) . '<br>';
    $body   .= sprintf(esc_html__('Estimate: %s', 'upkeepify'), esc_html(upkeepify_format_lifecycle_money_text($estimate, $currency))) . '<br>';
    $body   .= '<strong>' . sprintf(esc_html__('Approved quote: %s', 'upkeepify'), esc_html(upkeepify_format_lifecycle_money_text($formal_quote, $currency))) . '</strong><br>';
    $body   .= sprintf(esc_html__('Approved at: %s', 'upkeepify'), esc_html($approved_label)) . '</p>';

    if ($quote_note !== '') {
        $body .= '<p><strong>' . esc_html__('Quote notes:', 'upkeepify') . '</strong><br>' . nl2br(esc_html($quote_note)) . '</p>';
    }

    if (!empty($quote_documents)) {
        $body .= '<p><strong>' . esc_html__('Accepted quote document(s):', 'upkeepify') . '</strong></p>';
        $body .= upkeepify_render_quote_attachment_references($quote_documents);
    } else {
        $body .= '<p>' . esc_html__('No quote document was uploaded; the approved quote amount and notes are recorded above.', 'upkeepify') . '</p>';
    }

    $responses = upkeepify_get_provider_responses_for_task($task_id);
    if (!empty($responses)) {
        $reference_rows = '';
        foreach ($responses as $response) {
            $candidate_id = intval($response->ID);
            if ($candidate_id === intval($response_id)) {
                continue;
            }

            $decision = get_post_meta($candidate_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, true);
            $decision_label = $decision === 'accept' ? __('Accepted', 'upkeepify') : ($decision === 'decline' ? __('Declined', 'upkeepify') : __('Waiting', 'upkeepify'));
            $candidate_estimate = get_post_meta($candidate_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true);
            $candidate_quote = get_post_meta($candidate_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true);
            $candidate_docs = upkeepify_get_response_quote_attachment_ids($candidate_id);

            $reference_rows .= '<tr>';
            $reference_rows .= '<td>' . esc_html(upkeepify_get_response_provider_name($candidate_id)) . '</td>';
            $reference_rows .= '<td>' . esc_html($decision_label) . '</td>';
            $reference_rows .= '<td>' . esc_html(upkeepify_format_lifecycle_money_text($candidate_estimate, $currency)) . '</td>';
            $reference_rows .= '<td>' . esc_html(upkeepify_format_lifecycle_money_text($candidate_quote, $currency)) . '</td>';
            $reference_rows .= '<td>' . esc_html((string) count($candidate_docs)) . '</td>';
            $reference_rows .= '</tr>';
        }

        if ($reference_rows !== '') {
            $body .= '<h3>' . esc_html__('Other contractor response references', 'upkeepify') . '</h3>';
            $body .= '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#ddd;">';
            $body .= '<thead><tr><th align="left">' . esc_html__('Contractor', 'upkeepify') . '</th><th align="left">' . esc_html__('Decision', 'upkeepify') . '</th><th align="left">' . esc_html__('Estimate', 'upkeepify') . '</th><th align="left">' . esc_html__('Formal quote', 'upkeepify') . '</th><th align="left">' . esc_html__('Quote docs', 'upkeepify') . '</th></tr></thead><tbody>';
            $body .= $reference_rows;
            $body .= '</tbody></table>';
        }
    }

    $body .= '<p>' . esc_html__('WordPress task record:', 'upkeepify') . '<br><a href="' . esc_url($admin_url) . '">' . esc_url($admin_url) . '</a></p>';
    $body .= '</div>';

    $attachments = upkeepify_get_quote_attachment_file_paths($quote_documents);
    return wp_mail($recipient, $subject, $body, array('Content-Type: text/html; charset=UTF-8'), $attachments);
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
        upkeepify_sync_task_lifecycle_status($task_id);
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
        upkeepify_sync_task_lifecycle_status($task_id);
        upkeepify_send_trustee_lifecycle_approval_email($task_id, $response_id, 'quote');
        upkeepify_send_quote_audit_email($task_id, $response_id);
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
 * Handle trustee review actions after contractor resident-issue follow-up.
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_FOLLOWUP}
 */
function upkeepify_admin_post_trustee_lifecycle_followup() {
    $task_id         = isset($_POST['task_id']) ? absint(wp_unslash($_POST['task_id'])) : 0;
    $followup_action = isset($_POST['followup_action']) ? sanitize_key(wp_unslash($_POST['followup_action'])) : '';
    $resolution_note = isset($_POST['resolution_note']) ? sanitize_textarea_field(wp_unslash($_POST['resolution_note'])) : '';

    if (!$task_id || !current_user_can('edit_post', $task_id)) {
        wp_die(esc_html__('Insufficient permissions.', 'upkeepify'));
    }

    check_admin_referer(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);

    $task = get_post($task_id);
    if (!$task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        wp_die(esc_html__('Invalid lifecycle follow-up request.', 'upkeepify'));
    }

    $followup_status      = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true);
    $followup_response_id = intval(get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, true));
    if ($followup_status !== UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED || !$followup_response_id) {
        wp_die(esc_html__('Contractor follow-up must be submitted before this action is available.', 'upkeepify'));
    }

    $redirect_status = '';
    if ($followup_action === 'resolve') {
        upkeepify_resolve_resident_issue($task_id, $resolution_note);
        $redirect_status = 'resident_issue_resolved';
    } elseif ($followup_action === 'rerequest') {
        $resident_email = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL, true);
        if (!is_email($resident_email) || !function_exists('upkeepify_get_resident_confirmation_url') || !upkeepify_get_resident_confirmation_url($task_id)) {
            wp_die(esc_html__('This task does not have a resident email and confirmation link available.', 'upkeepify'));
        }

        if (!upkeepify_rerequest_resident_confirmation($task_id, $task)) {
            wp_die(esc_html__('Resident confirmation email could not be sent.', 'upkeepify'));
        }
        $redirect_status = 'resident_confirmation_requested';
    } else {
        wp_die(esc_html__('Unknown lifecycle follow-up action.', 'upkeepify'));
    }

    $redirect = add_query_arg(
        array(
            'post'                => $task_id,
            'action'              => 'edit',
            'upkeepify_lifecycle' => $redirect_status,
        ),
        admin_url('post.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_FOLLOWUP, 'upkeepify_admin_post_trustee_lifecycle_followup');

/**
 * Handle trustee manual lifecycle close actions.
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_TRUSTEE_MANUAL_CLOSE}
 */
function upkeepify_admin_post_trustee_manual_close() {
    $task_id = isset($_POST['task_id']) ? absint(wp_unslash($_POST['task_id'])) : 0;
    $mode    = isset($_POST['manual_close_mode']) ? sanitize_key(wp_unslash($_POST['manual_close_mode'])) : '';
    $note    = isset($_POST['manual_close_note']) ? sanitize_textarea_field(wp_unslash($_POST['manual_close_note'])) : '';

    if (!$task_id || !current_user_can('edit_post', $task_id)) {
        wp_die(esc_html__('Insufficient permissions.', 'upkeepify'));
    }

    check_admin_referer(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);

    $task = get_post($task_id);
    if (!$task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        wp_die(esc_html__('Invalid lifecycle manual close request.', 'upkeepify'));
    }

    if (!upkeepify_can_manual_close_task_lifecycle($task_id)) {
        wp_die(esc_html__('This task cannot be manually closed from its current lifecycle state.', 'upkeepify'));
    }

    upkeepify_manual_close_task_lifecycle($task_id, $mode, $note);

    $redirect = add_query_arg(
        array(
            'post'                => $task_id,
            'action'              => 'edit',
            'upkeepify_lifecycle' => 'manual_closed',
        ),
        admin_url('post.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_TRUSTEE_MANUAL_CLOSE, 'upkeepify_admin_post_trustee_manual_close');

/**
 * Handle contractor response token revoke/regenerate actions.
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_TOKEN_MANAGE}
 */
function upkeepify_admin_post_provider_token_manage() {
    $task_id      = isset($_POST['task_id']) ? absint(wp_unslash($_POST['task_id'])) : 0;
    $response_id  = isset($_POST['response_id']) ? absint(wp_unslash($_POST['response_id'])) : 0;
    $token_action = isset($_POST['token_action']) ? sanitize_key(wp_unslash($_POST['token_action'])) : '';

    if (!$task_id || !current_user_can('edit_post', $task_id)) {
        wp_die(esc_html__('Insufficient permissions.', 'upkeepify'));
    }

    check_admin_referer(UPKEEPIFY_NONCE_ACTION_TRUSTEE_LIFECYCLE, UPKEEPIFY_NONCE_TRUSTEE_LIFECYCLE);

    $task     = get_post($task_id);
    $response = get_post($response_id);
    if (!$task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS || !$response || $response->post_type !== UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES) {
        wp_die(esc_html__('Invalid contractor link request.', 'upkeepify'));
    }

    $response_task_id = intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true));
    if ($response_task_id !== intval($task_id)) {
        wp_die(esc_html__('This provider response does not belong to this task.', 'upkeepify'));
    }

    if ($token_action === 'revoke') {
        upkeepify_revoke_provider_response_token($response_id);
        $redirect_status = 'token_revoked';
    } elseif ($token_action === 'regenerate') {
        $token = upkeepify_regenerate_provider_response_token($response_id);
        upkeepify_send_regenerated_provider_token_email($task_id, $response_id, $token);
        $redirect_status = 'token_regenerated';
    } else {
        wp_die(esc_html__('Unknown contractor link action.', 'upkeepify'));
    }

    $redirect = add_query_arg(
        array(
            'post'                => $task_id,
            'action'              => 'edit',
            'upkeepify_lifecycle' => $redirect_status,
        ),
        admin_url('post.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_PROVIDER_TOKEN_MANAGE, 'upkeepify_admin_post_provider_token_manage');

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
