<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Check if current user can delete a specific task.
 *
 * Evaluates whether the current user has permissions to delete
 * a maintenance task. Checks for delete_posts capability by default.
 *
 * @since 1.0
 * @param int $task_id The ID of the task to check.
 * @return bool True if the current user can delete the task, false otherwise.
 * @uses current_user_can()
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
 * Send notification email for task status changes.
 *
 * Sends an email notification when a task's status is updated,
 * if notifications are enabled in plugin settings.
 *
 * @since 1.0
 * @param int    $task_id   The ID of the task that changed.
 * @param string $new_status The new status of the task.
 * @return void
 * @uses get_post()
 * @uses get_option()
 * @uses wp_mail()
 */
function upkeepify_send_status_change_email($task_id, $new_status) {
    $task = get_post($task_id);
    if (!$task) {
        return;
    }

    // Fetch plugin settings to see if notifications are enabled
    $settings = get_option(UPKEEPIFY_OPTION_SETTINGS);
    if (isset($settings[UPKEEPIFY_SETTING_NOTIFY_OPTION]) && $settings[UPKEEPIFY_SETTING_NOTIFY_OPTION]) {
        // Construct the email
        $to = $settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL] ?? get_option('admin_email');
        $subject = "Task Status Updated: {$task->post_title}";
        $message = "The status of task '{$task->post_title}' has been updated to '{$new_status}'.";
        // Send the email
        wp_mail($to, $subject, $message);
    }
}

/**
 * Generate a token for service providers to update task status without logging in.
 *
 * Creates a unique 20-character token that providers can use
 * to access and update their task responses without needing
 * a WordPress account.
 *
 * @since 1.0
 * @param int $task_id The ID of the task for which to generate a token.
 * @return string The generated token.
 * @uses wp_generate_password()
 * @uses update_post_meta()
 */
function upkeepify_generate_task_update_token($task_id) {
    // This is a simplified example. You should use a more secure method for generating and storing tokens.
    $token = wp_generate_password(20, false);
    update_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, $token);
    return $token;
}

/**
 * Validate the task update token.
 *
 * Checks if a provided token matches the stored token
 * for a specific task. Used for provider authentication.
 *
 * @since 1.0
 * @param int    $task_id The ID of the task.
 * @param string $token   The token to validate.
 * @return bool True if the token is valid, false otherwise.
 * @uses get_post_meta()
 */
function upkeepify_validate_task_update_token($task_id, $token) {
    $stored_token = get_post_meta($task_id, UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, true);
    return $token === $stored_token;
}
