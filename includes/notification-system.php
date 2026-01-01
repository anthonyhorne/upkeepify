<?php
/**
 * Notification System functions
 *
 * @package Upkeepify
 */

/**
 * Add a notification to the queue.
 *
 * Stores a notification message in the database for later display.
 * Optionally sends an email notification as well.
 *
 * @since 1.0
 * @param string $message The notification message.
 * @param string $type    Notification type (success, error, warning, info).
 * @param array  $data    Optional additional data to attach to the notification.
 * @param bool   $send_email Whether to send an email notification.
 * @uses get_option()
 * @uses update_option()
 * @uses upkeepify_send_email_notification()
 */
 function upkeepify_add_notification($message, $type = 'success', $data = array(), $send_email = false) {
    $notifications = get_option(UPKEEPIFY_OPTION_NOTIFICATIONS, array());
    $notifications[] = array(
        'message' => $message,
        'type' => $type,
        'data' => $data
    );
    update_option(UPKEEPIFY_OPTION_NOTIFICATIONS, $notifications);

    // Send email notification if requested
    if ($send_email) {
        upkeepify_send_email_notification($message, $type, $data);
    }
}

/**
 * Display queued notifications in the admin area.
 *
 * Retrieves and displays all pending notifications as WordPress admin notices.
 * Clears the notification queue after display.
 *
 * @since 1.0
 * @uses get_option()
 * @uses delete_option()
 * @hook admin_notices
 */
function upkeepify_display_notifications() {
    $notifications = get_option(UPKEEPIFY_OPTION_NOTIFICATIONS, array());

    if (!empty($notifications)) {
        foreach ($notifications as $notification) {
            echo '<div class="notice notice-' . $notification['type'] . '"><p>' . $notification['message'] . '</p></div>';
            // You can add additional logic here to handle the notification data if needed
        }

        // After displaying the notifications, clear them from the database
        delete_option(UPKEEPIFY_OPTION_NOTIFICATIONS);
    }
}
add_action('admin_notices', 'upkeepify_display_notifications');

/**
 * Send an email notification.
 *
 * Sends a notification email to the admin email address
 * with HTML formatting.
 *
 * @since 1.0
 * @param string $message The notification message to send.
 * @param string $type    The notification type.
 * @param array  $data    Optional additional data to include.
 * @uses get_option()
 * @uses wp_mail()
 */
function upkeepify_send_email_notification($message, $type, $data = array()) {
    // Set up email recipient, subject, and headers
    $recipient = get_option('admin_email'); // You can modify this to use a custom email address
    $subject = 'Upkeepify Notification';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Prepare email body
    $email_body = '<h3>Notification Type: ' . $type . '</h3>';
    $email_body .= '<p>' . $message . '</p>';

    // Add additional data to the email body if needed
    if (!empty($data)) {
        $email_body .= '<h4>Additional Data:</h4>';
        $email_body .= '<pre>' . print_r($data, true) . '</pre>';
    }

    // Send the email
    wp_mail($recipient, $subject, $email_body, $headers);
}