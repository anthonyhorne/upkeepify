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

    // Fetch 'Number of Units' setting, default to 10 if not set
    $number_of_units = get_option(UPKEEPIFY_OPTION_SETTINGS)[UPKEEPIFY_SETTING_NUMBER_OF_UNITS] ?? 10;

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
        update_post_meta($post_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, sanitize_text_field($_POST['upkeepify_nearest_unit']));
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
    // Sanitize the input to remove any potentially harmful content
    if (isset($_POST['upkeepify_rough_estimate'])) {
        update_post_meta($post_id, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, sanitize_text_field($_POST['upkeepify_rough_estimate']));
    }
}
add_action('save_post', 'upkeepify_save_rough_estimate_meta_box_data');

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
            // Further labels as needed
        ),
    );
    register_post_type(UPKEEPIFY_POST_TYPE_RESPONSES, $args);
}
add_action('init', 'upkeepify_register_response_post_type');
