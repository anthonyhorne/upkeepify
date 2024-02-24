<?php
/**
 * Plugin Name: Upkeepify
 * Description: A plugin to manage maintenance tasks within a complex. It supports task submissions with categorization and service provider management.
 * Version: 1.0
 * Author: Anthony Horne
 */

if (!session_id()) session_start();

function upkeepify_enqueue_styles() {
    wp_enqueue_style('upkeepify-styles', plugin_dir_url(__FILE__) . 'upkeepify-styles.css');
}
add_action('wp_enqueue_scripts', 'upkeepify_enqueue_styles');

function upkeepify_register_custom_post_types_and_taxonomies() {
    // Maintenance Tasks CPT
    $args_tasks = [
        'public' => true,
        'label'  => 'Maintenance Tasks',
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-hammer',
    ];
    register_post_type('maintenance_tasks', $args_tasks);

    // Task Categories - Flat taxonomy
    $category_args = [
        'hierarchical' => false,
        'label' => 'Task Categories',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
    ];
    register_taxonomy('task_category', ['maintenance_tasks'], $category_args);

    // Task Types - Flat taxonomy
    $type_args = [
        'hierarchical' => false,
        'label' => 'Task Types',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
    ];
    register_taxonomy('task_type', ['maintenance_tasks'], $type_args);

    // Task Statuses - Flat taxonomy
    $status_args = [
        'hierarchical' => false,
        'label' => 'Task Statuses',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
    ];
    register_taxonomy('task_status', ['maintenance_tasks'], $status_args);
}
add_action('init', 'upkeepify_register_custom_post_types_and_taxonomies');

// Shortcode for Displaying Maintenance Tasks
function upkeepify_list_tasks_shortcode() {
    // Implementation
    return '<p>[upkeepify_list_tasks] shortcode content here.</p>';
}
add_shortcode('upkeepify_list_tasks', 'upkeepify_list_tasks_shortcode');

// Shortcode for Task Submission Form
function upkeepify_task_form_shortcode() {
    // Implementation
    return '<p>[upkeepify_task_form] shortcode content here.</p>';
}
add_shortcode('upkeepify_task_form', 'upkeepify_task_form_shortcode');

// Handle Task Form Submission
function upkeepify_handle_form_submission() {
    // Implementation
}
add_action('init', 'upkeepify_handle_form_submission');

// Plugin Activation: Initialize Default Data
function upkeepify_activate() {
    // Default terms for 'task_category', 'task_type', 'task_status'
    $default_categories = ['General', 'Plumbing', 'Electrical'];
    foreach ($default_categories as $category) {
        if (!term_exists($category, 'task_category')) {
            wp_insert_term($category, 'task_category');
        }
    }

    // Default types and statuses can be added similarly
}
register_activation_hook(__FILE__, 'upkeepify_activate');
