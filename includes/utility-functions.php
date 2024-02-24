<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Utility function to check if a given task is allowed to be deleted by the current user.
 *
 * @param int $task_id The ID of the task to check.
 * @return bool True if the current user can delete the task, false otherwise.
 */
function upkeepify_can_user_delete_task($task_id) {
    // Example logic to determine if a task can be deleted
    // This could check if the current user is a service provider with permissions, an admin, or has some other role
    if (current_user_can('delete_posts')) {
        return true;
    }
    return false;
}

/**
 * Send notification emails for task status changes.
 *
 * @param int $task_id The ID of the task that changed.
 * @param string $new_status The new status of the task.
 * @return void
 */
function upkeepify_send_status_change_email($task_id, $new_status) {
    $task = get_post($task_id);
    if (!$task) {
        return;
    }

    // Fetch plugin settings to see if notifications are enabled
    $settings = get_option('upkeepify_settings');
    if (isset($settings['upkeepify_notify_option']) && $settings['upkeepify_notify_option']) {
        // Construct the email
        $to = $settings['upkeepify_override_email'] ?? get_option('admin_email');
        $subject = "Task Status Updated: {$task->post_title}";
        $message = "The status of task '{$task->post_title}' has been updated to '{$new_status}'.";
        // Send the email
        wp_mail($to, $subject, $message);
    }
}

/**
 * Generate a token for service providers to update task status without logging in.
 *
 * @param int $task_id The ID of the task for which to generate a token.
 * @return string The generated token.
 */
function upkeepify_generate_task_update_token($task_id) {
    // This is a simplified example. You should use a more secure method for generating and storing tokens.
    $token = wp_generate_password(20, false);
    update_post_meta($task_id, '_upkeepify_task_update_token', $token);
    return $token;
}

/**
 * Validate the task update token.
 *
 * @param int $task_id The ID of the task.
 * @param string $token The token to validate.
 * @return bool True if the token is valid, false otherwise.
 */
function upkeepify_validate_task_update_token($task_id, $token) {
    $stored_token = get_post_meta($task_id, '_upkeepify_task_update_token', true);
    return $token === $stored_token;
}
