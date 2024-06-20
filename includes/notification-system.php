<?php
/**
 * Notification System functions
 *
 * @package Upkeepify
 */

 function upkeepify_add_notification($message, $type = 'success', $data = array(), $send_email = false) {
    $notifications = get_option('upkeepify_notifications', array());
    $notifications[] = array(
        'message' => $message,
        'type' => $type,
        'data' => $data
    );
    update_option('upkeepify_notifications', $notifications);

    // Send email notification if requested
    if ($send_email) {
        send_upkeepify_email_notification($message, $type, $data);
    }
}

function upkeepify_display_notifications() {
    $notifications = get_option('upkeepify_notifications', array());

    if (!empty($notifications)) {
        foreach ($notifications as $notification) {
            echo '<div class="notice notice-' . $notification['type'] . '"><p>' . $notification['message'] . '</p></div>';
            // You can add additional logic here to handle the notification data if needed
        }

        // After displaying the notifications, clear them from the database
        delete_option('upkeepify_notifications');
    }
}
add_action('admin_notices', 'upkeepify_display_notifications');

function send_upkeepify_email_notification($message, $type, $data = array()) {
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