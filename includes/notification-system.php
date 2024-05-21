<?php
/**
 * Notification System functions
 *
 * @package Upkeepify
 */

function upkeepify_add_notification($message, $type = 'success', $data = array()) {
    $notifications = get_option('upkeepify_notifications', array());
    $notifications[] = array(
        'message' => $message,
        'type' => $type,
        'data' => $data
    );
    update_option('upkeepify_notifications', $notifications);
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