<?php
/**
 * Plugin Name: Upkeepify
 * Plugin URI: http://example.com/upkeepify-plugin
 * Description: A plugin to manage maintenance tasks within a complex.
 * Version: 1.0
 * Author: Your Name
 * Author URI: http://example.com
 */

function upkeepify_register_maintenance_task_cpt() {
    $args = array(
        'public' => true,
        'label'  => 'Maintenance Tasks',
        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'custom-fields'),
        'has_archive' => true,
        'show_in_rest' => true, // This enables Gutenberg editor for this CPT.
        'menu_icon' => 'dashicons-hammer', // Use a dashicon for the menu icon.
        'labels' => array(
            'add_new_item' => 'Add New Maintenance Task',
            'edit_item' => 'Edit Maintenance Task',
            'all_items' => 'All Maintenance Tasks',
            'singular_name' => 'Maintenance Task'
        ),
        'capability_type' => 'post',
    );

    register_post_type('maintenance_tasks', $args);
}

add_action('init', 'upkeepify_register_maintenance_task_cpt');

function upkeepify_activate() {
    upkeepify_register_maintenance_task_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'upkeepify_activate');

function upkeepify_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'upkeepify_deactivate');

function upkeepify_add_meta_boxes() {
    add_meta_box(
        'upkeepify_maintenance_details',
        'Maintenance Details',
        'upkeepify_render_maintenance_details_meta_box',
        'maintenance_tasks',
        'side',
        'default'
    );
}

function upkeepify_render_maintenance_details_meta_box($post) {
    // Render HTML for the meta box content here.
    // Use get_post_meta($post->ID) to retrieve saved values.
}

add_action('add_meta_boxes', 'upkeepify_add_meta_boxes');

function upkeepify_save_maintenance_details($post_id) {
    // Check for nonce here for security, and save/update post meta.
    // Example: update_post_meta($post_id, '_upkeepify_meta_key', $_POST['upkeepify_meta_value']);
}

add_action('save_post_maintenance_tasks', 'upkeepify_save_maintenance_details');
