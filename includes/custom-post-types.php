<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Post Types
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

// Register the 'Nearest Unit' meta box for Maintenance Tasks
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

// Display the 'Nearest Unit' dropdown in the meta box
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

// Save the selected 'Nearest Unit' when the post is saved
function upkeepify_save_nearest_unit_meta_box_data($post_id) {
    // Check nonce, autosave, user permissions
    if (!isset($_POST['upkeepify_nearest_unit_nonce']) || 
        !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_NEAREST_UNIT], UPKEEPIFY_NONCE_ACTION_NEAREST_UNIT_SAVE) || 
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || 
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Update the 'Nearest Unit' post meta
    if (isset($_POST['upkeepify_nearest_unit'])) {
        update_post_meta($post_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, sanitize_text_field($_POST['upkeepify_nearest_unit']));
    }
}
add_action('save_post', 'upkeepify_save_nearest_unit_meta_box_data');

// Register the 'Rough Estimate' meta box for Maintenance Tasks
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

// Display the 'Rough Estimate' field in the meta box
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

// Save the 'Rough Estimate' when the post is saved
function upkeepify_save_rough_estimate_meta_box_data($post_id) {
    // Check nonce, autosave, user permissions
    if (!isset($_POST['upkeepify_rough_estimate_nonce']) || 
        !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_ROUGH_ESTIMATE], UPKEEPIFY_NONCE_ACTION_ROUGH_ESTIMATE_SAVE) || 
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || 
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Update the 'Rough Estimate' post meta
    if (isset($_POST['upkeepify_rough_estimate'])) {
        update_post_meta($post_id, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, sanitize_text_field($_POST['upkeepify_rough_estimate']));
    }
}
add_action('save_post', 'upkeepify_save_rough_estimate_meta_box_data');

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
