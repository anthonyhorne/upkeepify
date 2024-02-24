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
