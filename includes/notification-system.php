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
 * @return bool True if notification was added successfully, false otherwise.
 * @uses get_option()
 * @uses update_option()
 * @uses upkeepify_send_email_notification()
 */
 function upkeepify_add_notification($message, $type = 'success', $data = array(), $send_email = false) {
    try {
        // Validate input parameters
        if (empty($message)) {
            error_log('Upkeepify Notification Error: Empty message provided');
            return false;
        }

        $allowed_types = array('success', 'error', 'warning', 'info');
        if (!in_array($type, $allowed_types)) {
            error_log('Upkeepify Notification Error: Invalid notification type: ' . $type);
            $type = 'info'; // Default to info if invalid type
        }

        // Sanitize message to prevent XSS
        $sanitized_message = wp_kses($message, array());
        if ($sanitized_message === '') {
            $sanitized_message = wp_kses_post($message);
        }

        // Get existing notifications
        $notifications = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_NOTIFICATIONS, array());
        if (!is_array($notifications)) {
            $notifications = array();
        }

        // Add new notification
        $notifications[] = array(
            'message' => $sanitized_message,
            'type' => $type,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );

        // Limit number of stored notifications (max 50)
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }

        // Save notifications
        $result = update_option(UPKEEPIFY_OPTION_NOTIFICATIONS, $notifications);

        if ($result === false) {
            error_log('Upkeepify Notification Error: Failed to save notification to database');
        }

        // Send email notification if requested
        if ($send_email) {
            $email_result = upkeepify_send_email_notification($sanitized_message, $type, $data);
            if (!$email_result) {
                error_log('Upkeepify Notification Warning: Email notification failed to send');
            }
        }

        return $result !== false;

    } catch (Exception $e) {
        error_log('Upkeepify Notification Exception: ' . $e->getMessage());
        return false;
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
    try {
        $notifications = get_option(UPKEEPIFY_OPTION_NOTIFICATIONS, array());

        // Validate notifications array
        if (!is_array($notifications)) {
            error_log('Upkeepify Notification Error: Invalid notifications data type');
            return;
        }

        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                // Validate notification structure
                if (!is_array($notification) || !isset($notification['message']) || !isset($notification['type'])) {
                    continue; // Skip invalid notifications
                }

                $message = isset($notification['message']) ? $notification['message'] : '';
                $type = isset($notification['type']) ? $notification['type'] : 'info';

                // Sanitize and display notification
                echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . wp_kses_post($message) . '</p></div>';
            }

            // After displaying the notifications, clear them from the database
            delete_option(UPKEEPIFY_OPTION_NOTIFICATIONS);
        }
    } catch (Exception $e) {
        error_log('Upkeepify Notification Display Exception: ' . $e->getMessage());
    }
}
add_action('admin_notices', 'upkeepify_display_notifications');

/**
 * Send an email notification.
 *
 * Sends a notification email to the admin email address
 * with HTML formatting. Includes comprehensive error handling.
 *
 * @since 1.0
 * @param string $message The notification message to send.
 * @param string $type    The notification type.
 * @param array  $data    Optional additional data to include.
 * @return bool True if email was sent successfully, false otherwise.
 * @uses get_option()
 * @uses is_email()
 * @uses wp_mail()
 */
function upkeepify_send_email_notification($message, $type, $data = array()) {
    try {
        // Validate message
        if (empty($message)) {
            error_log('Upkeepify Email Error: Cannot send email with empty message');
            return false;
        }

        // Get settings from cache
        $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
        if (!is_array($settings)) {
            $settings = array();
        }

        // Determine recipient - use override email if set, otherwise admin email
        $recipient = isset($settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL]) && !empty($settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL])
            ? $settings[UPKEEPIFY_SETTING_OVERRIDE_EMAIL]
            : get_option('admin_email');

        // Validate email address
        if (empty($recipient) || !is_email($recipient)) {
            error_log('Upkeepify Email Error: Invalid recipient email address: ' . $recipient);
            return false;
        }

        // Check if notifications are enabled
        $notify_enabled = isset($settings[UPKEEPIFY_SETTING_NOTIFY_OPTION]) ? $settings[UPKEEPIFY_SETTING_NOTIFY_OPTION] : false;
        if (!$notify_enabled) {
            if (WP_DEBUG) {
                error_log('Upkeepify Email: Notifications disabled, skipping email send');
            }
            return false;
        }

        // Check SMTP configuration if enabled
        $smtp_enabled = isset($settings[UPKEEPIFY_SETTING_SMTP_OPTION]) ? $settings[UPKEEPIFY_SETTING_SMTP_OPTION] : false;
        if ($smtp_enabled) {
            $smtp_host = isset($settings[UPKEEPIFY_SETTING_SMTP_HOST]) ? $settings[UPKEEPIFY_SETTING_SMTP_HOST] : '';
            if (empty($smtp_host)) {
                error_log('Upkeepify Email Warning: SMTP enabled but host not configured');
            }
        }

        // Sanitize email content
        $sanitized_type = sanitize_text_field($type);
        $sanitized_message = wp_kses_post($message);

        // Set up email subject and headers
        $site_name = get_bloginfo('name');
        $subject = '[' . $site_name . '] Upkeepify Notification: ' . ucfirst($sanitized_type);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Prepare email body
        $email_body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $email_body .= '<h2 style="color: #333;">Upkeepify Notification</h2>';
        $email_body .= '<p><strong>Type:</strong> ' . esc_html($sanitized_type) . '</p>';
        $email_body .= '<p><strong>Time:</strong> ' . current_time('mysql') . '</p>';
        $email_body .= '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">';
        $email_body .= '<h3 style="color: #555;">Message:</h3>';
        $email_body .= '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa;">';
        $email_body .= $sanitized_message;
        $email_body .= '</div>';

        // Add additional data to the email body if needed
        if (!empty($data) && is_array($data)) {
            $email_body .= '<h3 style="color: #555; margin-top: 20px;">Additional Data:</h3>';
            $email_body .= '<pre style="background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 4px;">';
            $email_body .= esc_html(print_r($data, true));
            $email_body .= '</pre>';
        }

        $email_body .= '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">';
        $email_body .= '<p style="color: #666; font-size: 12px;">This is an automated notification from ' . esc_html($site_name) . '.</p>';
        $email_body .= '</div>';

        // Send the email
        $sent = wp_mail($recipient, $subject, $email_body, $headers);

        // Log result
        if ($sent) {
            if (WP_DEBUG) {
                error_log('Upkeepify Email Success: Sent to ' . $recipient);
            }
        } else {
            error_log('Upkeepify Email Error: wp_mail() failed to send to ' . $recipient);
        }

        return $sent;

    } catch (Exception $e) {
        error_log('Upkeepify Email Exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a tokenized job invitation email to a contractor.
 *
 * Called when a maintenance task is published and a matching provider response
 * post has been created. The email contains a unique link that gives the
 * contractor access to the response form without requiring a login.
 *
 * @since 1.1
 * @param string  $provider_email Provider email address.
 * @param string  $provider_name  Provider display name.
 * @param WP_Post $task           The published maintenance task post.
 * @param string  $token          The unique response token.
 * @param int     $response_id    The provider response post ID.
 * @return bool True if the email was sent successfully, false otherwise.
 */
function upkeepify_send_contractor_invite( $provider_email, $provider_name, $task, $token, $response_id ) {
    if ( ! is_email( $provider_email ) ) {
        error_log( 'Upkeepify Invite: Invalid provider email for response ID ' . $response_id );
        return false;
    }

    $settings      = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $response_page = isset( $settings[ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] )
        ? trailingslashit( $settings[ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] )
        : '';

    if ( empty( $response_page ) ) {
        error_log( 'Upkeepify Invite: Contractor response page URL not configured. Set it in Upkeepify Settings → Contractor Invite Settings.' );
        return false;
    }

    $invite_url = add_query_arg( UPKEEPIFY_QUERY_VAR_TOKEN, rawurlencode( $token ), untrailingslashit( $response_page ) );
    $site_name  = get_bloginfo( 'name' );

    $subject = sprintf(
        /* translators: %s: site name */
        __( '[%s] New maintenance job request', 'upkeepify' ),
        $site_name
    );

    $excerpt = wp_trim_words( wp_strip_all_tags( $task->post_content ), 40, '…' );

    $body  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
    $body .= '<h2 style="color:#333;">' . esc_html__( 'New Job Request', 'upkeepify' ) . '</h2>';
    $body .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'upkeepify' ), esc_html( $provider_name ) ) . '</p>';
    $body .= '<p>' . esc_html__( 'A new maintenance task has been submitted that matches your service categories.', 'upkeepify' ) . '</p>';
    $body .= '<h3 style="color:#555;">' . esc_html( $task->post_title ) . '</h3>';
    $body .= '<p style="color:#444;">' . esc_html( $excerpt ) . '</p>';
    $body .= '<p style="margin:24px 0;">';
    $body .= '<a href="' . esc_url( $invite_url ) . '" style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">';
    $body .= esc_html__( 'View job and respond', 'upkeepify' );
    $body .= '</a></p>';
    $body .= '<p style="color:#999;font-size:12px;">' . sprintf(
        /* translators: %d: number of days */
        esc_html__( 'This link is unique to you and expires in %d days. Do not forward it.', 'upkeepify' ),
        UPKEEPIFY_TOKEN_EXPIRY_DAYS
    ) . '</p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__( 'Or copy this link:', 'upkeepify' ) . '<br>';
    $body .= '<code style="word-break:break-all;">' . esc_url( $invite_url ) . '</code></p>';
    $body .= '</div>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $sent    = wp_mail( $provider_email, $subject, $body, $headers );

    if ( ! $sent ) {
        error_log( 'Upkeepify Invite: wp_mail() failed for provider "' . $provider_name . '" (response ID ' . $response_id . ')' );
    } elseif ( WP_DEBUG ) {
        error_log( 'Upkeepify Invite: Sent to ' . $provider_email . ' for task "' . $task->post_title . '"' );
    }

    return $sent;
}