import sys

with open('includes/shortcodes.php', 'r') as f:
    content = f.read()

# 1. Add logging to upkeepify_handle_task_form_submission error paths
old1 = """    if ( empty( $settings[ UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING ] ) ) {
        upkeepify_redirect_task_form_status( 'error', 'public_disabled' );
    }"""
new1 = """    if ( empty( $settings[ UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING ] ) ) {
        upkeepify_log('Public task submission attempt while disabled', 'warning');
        upkeepify_redirect_task_form_status( 'error', 'public_disabled' );
    }"""
content = content.replace(old1, new1)

old2 = """    if ( ! $task_submit_nonce || ! wp_verify_nonce($task_submit_nonce, UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT)) {
        upkeepify_redirect_task_form_status( 'error', 'security_failed' );
    }"""
new2 = """    if ( ! $task_submit_nonce || ! wp_verify_nonce($task_submit_nonce, UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT)) {
        upkeepify_log('Task submission security check failed', 'warning');
        upkeepify_redirect_task_form_status( 'error', 'security_failed' );
    }"""
content = content.replace(old2, new2)

old3 = """    if (!isset($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) || intval($user_answer) !== intval($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT])) {
        upkeepify_redirect_task_form_status( 'error', 'captcha_failed' );
    }"""
new3 = """    if (!isset($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) || intval($user_answer) !== intval($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT])) {
        upkeepify_log('Task submission CAPTCHA failed', 'warning', array('expected' => $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT] ?? 'unknown', 'received' => $user_answer));
        upkeepify_redirect_task_form_status( 'error', 'captcha_failed' );
    }"""
content = content.replace(old3, new3)

old4 = """        // Validate upload using scoped validation
        $validation = upkeepify_validate_upload($task_photo);
        if (is_wp_error($validation)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Validation Error: ' . $validation->get_error_message());
            }
            upkeepify_redirect_task_form_status( 'error', 'upload_invalid' );
        }"""
new4 = """        // Validate upload using scoped validation
        $validation = upkeepify_validate_upload($task_photo);
        if (is_wp_error($validation)) {
            upkeepify_log('Task submission photo validation failed', 'warning', array('error' => $validation->get_error_message()));
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Validation Error: ' . $validation->get_error_message());
            }
            upkeepify_redirect_task_form_status( 'error', 'upload_invalid' );
        }"""
content = content.replace(old4, new4)

# 2. Contractor response logging
old5 = """    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, $decision );

    if ( $decision === 'decline' ) {"""
new5 = """    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, $decision );

    upkeepify_log(
        'Contractor responded to job invitation',
        'info',
        array(
            'response_id' => $response_id,
            'decision' => $decision,
        )
    );

    if ( $decision === 'decline' ) {"""
content = content.replace(old5, new5)

# 3. wp_mail checks in shortcodes.php
# line 1408
old6 = """            wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );"""
new6 = """            $sent = wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
            if (!$sent) {
                upkeepify_log('Estimate notification email failed', 'error', array('recipient' => $recipient, 'response_id' => $response_id));
            }"""
content = content.replace(old6, new6)

# line 1606
old7 = """            wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );"""
# Note: we need to be careful as old6 and old7 are identical. We might need more context or use a count.
# Actually they are in different functions.

# line 1812
old8 = """        wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );"""
new8 = """        $sent = wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
        if (!$sent) {
            upkeepify_log('Job completion notification email failed', 'error', array('recipient' => $recipient, 'response_id' => $response_id));
        }"""
content = content.replace(old8, new8)

# line 1953
old9 = """    $sent = wp_mail( $provider_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    if ( $sent ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_CONTRACTOR_NOTIFIED_AT, time() );
    }"""
new9 = """    $sent = wp_mail( $provider_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    if ( $sent ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_CONTRACTOR_NOTIFIED_AT, time() );
    } else {
        upkeepify_log('Contractor resident issue email failed', 'error', array('task_id' => $task_id, 'provider_email' => $provider_email));
    }"""
content = content.replace(old9, new9)

# line 2066
old10 = """    $sent = wp_mail( $resident_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

    if ( WP_DEBUG ) {"""
new10 = """    $sent = wp_mail( $resident_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

    if (!$sent) {
        upkeepify_log('Resident confirmation email failed', 'error', array('task_id' => $task_id, 'resident_email' => $resident_email));
    }

    if ( WP_DEBUG ) {"""
content = content.replace(old10, new10)

# line 2332
old11 = """    wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );"""
new11 = """    $sent = wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    if (!$sent) {
        upkeepify_log('Resident feedback notification email failed', 'error', array('task_id' => $task_id, 'recipient' => $recipient));
    }"""
content = content.replace(old11, new11)

with open('includes/shortcodes.php', 'w') as f:
    f.write(content)
