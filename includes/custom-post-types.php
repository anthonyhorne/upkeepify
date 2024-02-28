<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Post Types
 */
function upkeepify_register_custom_post_types() {
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
    register_post_type('maintenance_tasks', $args_maintenance_tasks);

    // Register the Service Providers CPT
    $args_service_providers = array(
        'public' => true,
        'label'  => 'Service Providers',
        'supports' => array('title', 'editor'),
        'show_in_rest' => true, // Enables Gutenberg support
        'show_ui' => true, // Show UI in admin
        'show_in_menu' => 'edit.php?post_type=maintenance_tasks', // Nested under Maintenance Tasks
        'has_archive' => true,
        'rewrite' => array('slug' => 'service-providers'), // Custom slug for this CPT
    );
    register_post_type('service_provider', $args_service_providers);

}

add_action('init', 'upkeepify_register_custom_post_types');

// Register the 'Nearest Unit' meta box for Maintenance Tasks
function upkeepify_add_nearest_unit_meta_box() {
    add_meta_box(
        'upkeepify_nearest_unit', // Unique ID for the meta box
        __('Nearest Unit', 'upkeepify'), // Meta box title
        'upkeepify_nearest_unit_meta_box_callback', // Callback function to display the fields
        'maintenance_tasks', // Post type where the meta box will be shown
        'side', // Context where the box will show ('normal', 'side', 'advanced')
        'default' // Priority within the context where the boxes should show ('high', 'low', 'default')
    );
}
add_action('add_meta_boxes', 'upkeepify_add_nearest_unit_meta_box');

// Display the 'Nearest Unit' dropdown in the meta box
function upkeepify_nearest_unit_meta_box_callback($post) {
    // Security nonce for verification
    wp_nonce_field('upkeepify_nearest_unit_save', 'upkeepify_nearest_unit_nonce');
    
    // Retrieve current 'Nearest Unit' value
    $nearest_unit_value = get_post_meta($post->ID, 'upkeepify_nearest_unit', true);
    
    // Fetch 'Number of Units' setting, default to 10 if not set
    $number_of_units = get_option('upkeepify_settings')['upkeepify_number_of_units'] ?? 10;

    // Output the dropdown for selecting the nearest unit
    echo '<select name="upkeepify_nearest_unit" id="upkeepify_nearest_unit" class="postbox">';
    for ($i = 1; $i <= $number_of_units - 1; $i++) {
        echo '<option value="' . esc_attr($i) . '"' . selected($nearest_unit_value, $i, false) . '>' . esc_html($i) . '</option>';
    }
    echo '</select>';
}

// Save the selected 'Nearest Unit' when the post is saved
function upkeepify_save_nearest_unit_meta_box_data($post_id) {
    // Check nonce, autosave, user permissions
    if (!isset($_POST['upkeepify_nearest_unit_nonce']) || 
        !wp_verify_nonce($_POST['upkeepify_nearest_unit_nonce'], 'upkeepify_nearest_unit_save') || 
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || 
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Update the 'Nearest Unit' post meta
    if (isset($_POST['upkeepify_nearest_unit'])) {
        update_post_meta($post_id, 'upkeepify_nearest_unit', sanitize_text_field($_POST['upkeepify_nearest_unit']));
    }
}
add_action('save_post', 'upkeepify_save_nearest_unit_meta_box_data');
