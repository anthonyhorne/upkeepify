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

function upkeepify_task_form_shortcode() {
    // Check user permission
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to submit a task.</p>';
    }

    $form_html = '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">';
    $form_html .= '<label for="task_title">Title:</label>';
    $form_html .= '<input type="text" id="task_title" name="task_title" required>';
    $form_html .= '<label for="task_description">Description:</label>';
    $form_html .= '<textarea id="task_description" name="task_description" required></textarea>';
    // Add any other fields here
    $form_html .= '<input type="submit" name="upkeepify_task_submit" value="Submit Task">';
    $form_html .= '</form>';

    return $form_html;
}
add_shortcode('upkeepify_task_form', 'upkeepify_task_form_shortcode');

function upkeepify_handle_task_form_submission() {
    if (isset($_POST['upkeepify_task_submit'])) {
        // Sanitize form values
        $task_title = sanitize_text_field($_POST['task_title']);
        $task_description = sanitize_textarea_field($_POST['task_description']);

        // Insert the post into the database
        $task_id = wp_insert_post([
            'post_title' => $task_title,
            'post_content' => $task_description,
            'post_status' => 'pending', // Use 'pending' to require admin approval
            'post_type' => 'maintenance_tasks',
        ]);

        // Handle file upload for images, if applicable
        // You can use the media_handle_upload function here

        if ($task_id) {
            // Redirect or display a success message
            echo '<p>Thank you for submitting your task. It is pending review.</p>';
        }
    }
}
add_action('init', 'upkeepify_handle_task_form_submission');

function upkeepify_list_tasks_shortcode() {
    $args = array(
        'post_type' => 'maintenance_tasks',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );

    $query = new WP_Query($args);
    $output = '';

    if ($query->have_posts()) {
        $output .= '<ul class="upkeepify-tasks-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $status = get_post_meta(get_the_ID(), 'status', true); // Example of displaying status

            // Constructing a minimalist list item.
            $output .= '<li>';
            $output .= '<strong>' . get_the_title() . '</strong> - '; // Task Title
            $output .= '<span>Status: ' . esc_html($status) . '</span>'; // Task Status
            $output .= '</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No maintenance tasks found.</p>';
    }

    wp_reset_postdata();

    return $output;
}
add_shortcode('upkeepify_list_tasks', 'upkeepify_list_tasks_shortcode');

function upkeepify_enqueue_styles() {
    wp_enqueue_style('upkeepify-styles', plugin_dir_url(__FILE__) . 'upkeepify-styles.css');
}
add_action('wp_enqueue_scripts', 'upkeepify_enqueue_styles');
