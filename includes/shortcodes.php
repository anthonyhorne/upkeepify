<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Shortcode to Display Maintenance Tasks
 */
function upkeepify_list_tasks_shortcode() {
    ob_start(); // Start output buffering

    $query = new WP_Query([
        'post_type' => 'maintenance_tasks',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ]);

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-list">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li><strong>' . get_the_title() . '</strong>';
            // Optionally display more details here (e.g., status, category)
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No maintenance tasks found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean(); // Return the buffered output
}
add_shortcode('upkeepify_list_tasks', 'upkeepify_list_tasks_shortcode');

/**
 * Shortcode for Task Submission Form
 */
function upkeepify_task_form_shortcode() {
    // Ensure session start for math CAPTCHA
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['upkeepify_math_result'] = $num1 + $num2;

    ob_start(); // Start output buffering

    // Display form
    echo '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post">';
    echo '<label for="task_title">Task Title:</label><br />';
    echo '<input type="text" id="task_title" name="task_title" required><br />';
    echo '<label for="task_description">Task Description:</label><br />';
    echo '<textarea id="task_description" name="task_description" required></textarea><br />';
    echo '<label for="math">What is ' . $num1 . ' + ' . $num2 . '?</label><br />';
    echo '<input type="text" id="math" name="math" required><br />';
    echo '<input type="submit" name="upkeepify_task_submit" value="Submit Task">';
    echo '</form>';

    return ob_get_clean(); // Return the buffered output
}
add_shortcode('upkeepify_task_form', 'upkeepify_task_form_shortcode');

/**
 * Handle Form Submission
 */
function upkeepify_handle_task_form_submission() {
    if (isset($_POST['upkeepify_task_submit'], $_POST['math'], $_POST['task_title'], $_POST['task_description']) && isset($_SESSION['upkeepify_math_result'])) {
        $user_answer = filter_input(INPUT_POST, 'math', FILTER_SANITIZE_NUMBER_INT);
        if ($user_answer == $_SESSION['upkeepify_math_result']) {
            // Proceed if math answer is correct
            $task_title = sanitize_text_field($_POST['task_title']);
            $task_description = sanitize_textarea_field($_POST['task_description']);

            // Insert the new task
            $task_id = wp_insert_post([
                'post_title' => $task_title,
                'post_content' => $task_description,
                'post_status' => 'publish',
                'post_type' => 'maintenance_tasks',
            ]);

            if ($task_id) {
                // Optionally redirect or display a success message
            }
        } else {
            // Handle incorrect math answer
        }
    }
}
add_action('init', 'upkeepify_handle_task_form_submission');
