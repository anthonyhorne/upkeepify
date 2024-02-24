<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Taxonomies
 */
function upkeepify_register_taxonomies() {
    // Task Categories - Non-hierarchical (like tags)
    $args_task_category = array(
        'hierarchical' => false,
        'label' => 'Task Categories',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-category'),
    );
    register_taxonomy('task_category', array('maintenance_tasks'), $args_task_category);

    // Task Types - Non-hierarchical (like tags)
    $args_task_type = array(
        'hierarchical' => false,
        'label' => 'Task Types',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-type'),
    );
    register_taxonomy('task_type', array('maintenance_tasks'), $args_task_type);

    // Task Statuses - Non-hierarchical (like tags)
    $args_task_status = array(
        'hierarchical' => false,
        'label' => 'Task Statuses',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-status'),
    );
    register_taxonomy('task_status', array('maintenance_tasks'), $args_task_status);
}

add_action('init', 'upkeepify_register_taxonomies');
