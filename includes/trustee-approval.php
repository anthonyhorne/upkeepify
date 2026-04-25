<?php
/**
 * Trustee Approval — token-based, login-free approval flows
 *
 * Three approval gates, each triggered automatically:
 *   task_approval     – trustees approve a new task before contractors are invited
 *   estimate_approval – trustees approve a contractor's ballpark estimate
 *   quote_approval    – trustees approve a contractor's formal quote
 *
 * All gates share the same token model: one 32-char token per trustee per step,
 * stored under UPKEEPIFY_META_KEY_TRUSTEE_TOKENS as a [step][email]=>token array.
 *
 * @package Upkeepify
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// ─────────────────────────────────────────────────────────────────────────────
// Settings helpers
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_get_trustee_emails() {
    $settings = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $raw      = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_EMAILS ] ) ? $settings[ UPKEEPIFY_SETTING_TRUSTEE_EMAILS ] : '';
    $emails   = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw ) ), 'is_email' );
    return array_values( $emails );
}

function upkeepify_trustee_approval_enabled() {
    return ! empty( upkeepify_get_trustee_emails() );
}

function upkeepify_get_trustee_required_approvals() {
    $settings = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $count    = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REQUIRED_APPROVALS ] )
        ? intval( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REQUIRED_APPROVALS ] )
        : 1;
    return max( 1, $count );
}

function upkeepify_get_trustee_approval_url( $task_id, $step, $token ) {
    $settings = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $page     = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_APPROVAL_PAGE ] )
        ? trailingslashit( $settings[ UPKEEPIFY_SETTING_TRUSTEE_APPROVAL_PAGE ] )
        : '';
    if ( empty( $page ) ) {
        return null;
    }
    return add_query_arg(
        array(
            UPKEEPIFY_QUERY_VAR_TRUSTEE_TOKEN => rawurlencode( $token ),
            'task_id'                         => $task_id,
            'step'                            => $step,
        ),
        untrailingslashit( $page )
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Meta accessors
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_get_trustee_tokens( $task_id ) {
    $data = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_TOKENS, true );
    return is_array( $data ) ? $data : array();
}

function upkeepify_get_trustee_approvals( $task_id ) {
    $data = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_APPROVALS, true );
    return is_array( $data ) ? $data : array();
}

function upkeepify_get_trustee_rejections( $task_id ) {
    $data = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REJECTIONS, true );
    return is_array( $data ) ? $data : array();
}

function upkeepify_get_trustee_pending_response_id( $task_id, $step ) {
    $data = get_post_meta( $task_id, '_upkeepify_trustee_pending_response_id', true );
    return ( is_array( $data ) && isset( $data[ $step ] ) ) ? intval( $data[ $step ] ) : 0;
}

function upkeepify_set_trustee_pending_response_id( $task_id, $step, $response_id ) {
    $data = get_post_meta( $task_id, '_upkeepify_trustee_pending_response_id', true );
    if ( ! is_array( $data ) ) {
        $data = array();
    }
    $data[ $step ] = intval( $response_id );
    update_post_meta( $task_id, '_upkeepify_trustee_pending_response_id', $data );
}

// ─────────────────────────────────────────────────────────────────────────────
// Token issuance
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Generate fresh tokens for every trustee on a given step.
 * Replaces any existing tokens for that step and resets reminder state.
 *
 * @return string[] email => token pairs
 */
function upkeepify_issue_trustee_tokens( $task_id, $step ) {
    $emails = upkeepify_get_trustee_emails();
    if ( empty( $emails ) ) {
        return array();
    }

    $step_tokens = array();
    foreach ( $emails as $email ) {
        $step_tokens[ $email ] = wp_generate_password( 32, false );
    }

    $all_tokens         = upkeepify_get_trustee_tokens( $task_id );
    $all_tokens[ $step ] = $step_tokens;
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_TOKENS, $all_tokens );

    $requested                = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_APPROVAL_REQUESTED_AT, true );
    $requested                = is_array( $requested ) ? $requested : array();
    $requested[ $step ]       = time();
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_APPROVAL_REQUESTED_AT, $requested );

    $counts         = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REMINDER_COUNTS, true );
    $counts         = is_array( $counts ) ? $counts : array();
    $counts[ $step ] = 0;
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REMINDER_COUNTS, $counts );

    return $step_tokens;
}

// ─────────────────────────────────────────────────────────────────────────────
// Gate initiation
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Open the task-approval gate for a newly published task.
 * Sets status to Pending Task Approval and emails every trustee.
 *
 * @return bool False when trustee approval is not configured.
 */
function upkeepify_initiate_trustee_task_approval( $task_id ) {
    if ( ! upkeepify_trustee_approval_enabled() ) {
        return false;
    }

    $step   = UPKEEPIFY_TRUSTEE_STEP_TASK;
    $tokens = upkeepify_issue_trustee_tokens( $task_id, $step );
    upkeepify_set_task_status_by_name( $task_id, UPKEEPIFY_TASK_STATUS_PENDING_TASK_APPROVAL );

    $task = get_post( $task_id );
    foreach ( $tokens as $email => $token ) {
        $url = upkeepify_get_trustee_approval_url( $task_id, $step, $token );
        if ( $url ) {
            upkeepify_send_trustee_approval_request( $email, $task, $step, $url );
        }
    }

    return true;
}

/**
 * Open the estimate-approval gate after a contractor submits an estimate.
 *
 * @return bool False when trustee approval is not configured.
 */
function upkeepify_initiate_trustee_estimate_approval( $task_id, $response_id ) {
    if ( ! upkeepify_trustee_approval_enabled() ) {
        return false;
    }

    $step = UPKEEPIFY_TRUSTEE_STEP_ESTIMATE;
    upkeepify_set_trustee_pending_response_id( $task_id, $step, $response_id );
    $tokens = upkeepify_issue_trustee_tokens( $task_id, $step );
    upkeepify_set_task_status_by_name( $task_id, UPKEEPIFY_TASK_STATUS_PENDING_ESTIMATE_APPROVAL );

    $task = get_post( $task_id );
    foreach ( $tokens as $email => $token ) {
        $url = upkeepify_get_trustee_approval_url( $task_id, $step, $token );
        if ( $url ) {
            upkeepify_send_trustee_approval_request( $email, $task, $step, $url, $response_id );
        }
    }

    return true;
}

/**
 * Open the quote-approval gate after a contractor submits a formal quote.
 *
 * @return bool False when trustee approval is not configured.
 */
function upkeepify_initiate_trustee_quote_approval( $task_id, $response_id ) {
    if ( ! upkeepify_trustee_approval_enabled() ) {
        return false;
    }

    $step = UPKEEPIFY_TRUSTEE_STEP_QUOTE;
    upkeepify_set_trustee_pending_response_id( $task_id, $step, $response_id );
    $tokens = upkeepify_issue_trustee_tokens( $task_id, $step );
    upkeepify_set_task_status_by_name( $task_id, UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL );

    $task = get_post( $task_id );
    foreach ( $tokens as $email => $token ) {
        $url = upkeepify_get_trustee_approval_url( $task_id, $step, $token );
        if ( $url ) {
            upkeepify_send_trustee_approval_request( $email, $task, $step, $url, $response_id );
        }
    }

    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// Token validation
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Validate a trustee token.
 * Returns the trustee email on success, false if invalid or already consumed.
 *
 * @return string|false
 */
function upkeepify_validate_trustee_token( $task_id, $step, $token ) {
    $tokens = upkeepify_get_trustee_tokens( $task_id );
    if ( empty( $tokens[ $step ] ) || ! is_array( $tokens[ $step ] ) ) {
        return false;
    }

    foreach ( $tokens[ $step ] as $email => $stored ) {
        if ( hash_equals( $stored, $token ) ) {
            $approvals  = upkeepify_get_trustee_approvals( $task_id );
            $rejections = upkeepify_get_trustee_rejections( $task_id );
            if ( isset( $approvals[ $step ][ $email ] ) || isset( $rejections[ $step ][ $email ] ) ) {
                return false; // already consumed
            }
            return $email;
        }
    }

    return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// Approval / rejection processing
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_process_trustee_approval( $task_id, $step, $email ) {
    $approvals            = upkeepify_get_trustee_approvals( $task_id );
    if ( ! isset( $approvals[ $step ] ) ) {
        $approvals[ $step ] = array();
    }
    $approvals[ $step ][ $email ] = time();
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_APPROVALS, $approvals );

    if ( count( $approvals[ $step ] ) >= upkeepify_get_trustee_required_approvals() ) {
        upkeepify_trigger_step_completion( $task_id, $step );
    }
}

function upkeepify_process_trustee_rejection( $task_id, $step, $email ) {
    $rejections            = upkeepify_get_trustee_rejections( $task_id );
    if ( ! isset( $rejections[ $step ] ) ) {
        $rejections[ $step ] = array();
    }
    $rejections[ $step ][ $email ] = time();
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REJECTIONS, $rejections );

    $settings    = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $reject_kills = ! empty( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REJECT_KILLS ] );

    $kill = $reject_kills
        || count( $rejections[ $step ] ) >= upkeepify_get_trustee_required_approvals();

    if ( $kill ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REJECTION_INFO, array(
            'step'  => $step,
            'email' => $email,
            'at'    => time(),
        ) );
        upkeepify_set_task_status_by_name( $task_id, UPKEEPIFY_TASK_STATUS_ON_HOLD );
        wp_update_post( array(
            'ID'          => $task_id,
            'post_status' => 'draft',
        ) );
    }
}

/**
 * Fire the downstream action once a step's approval threshold is met.
 */
function upkeepify_trigger_step_completion( $task_id, $step ) {
    switch ( $step ) {
        case UPKEEPIFY_TRUSTEE_STEP_TASK:
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_TASK_AT, time() );
            $approvals = upkeepify_get_trustee_approvals( $task_id );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_TASK_BY, array_keys( $approvals[ $step ] ?? array() ) );
            // Invite contractors — the generate function guards against duplicate responses.
            $post = get_post( $task_id );
            if ( $post && function_exists( 'upkeepify_generate_provider_tokens' ) ) {
                upkeepify_generate_provider_tokens( $task_id, $post, true );
            }
            upkeepify_set_task_status_by_name( $task_id, UPKEEPIFY_TASK_STATUS_OPEN );
            break;

        case UPKEEPIFY_TRUSTEE_STEP_ESTIMATE:
            $response_id = upkeepify_get_trustee_pending_response_id( $task_id, $step );
            if ( ! $response_id ) {
                return;
            }
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, $response_id );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_AT, time() );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_BY, 0 );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
                intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) )
            );
            upkeepify_sync_task_lifecycle_status( $task_id );
            upkeepify_send_trustee_lifecycle_approval_email( $task_id, $response_id, 'estimate' );
            break;

        case UPKEEPIFY_TRUSTEE_STEP_QUOTE:
            $response_id          = upkeepify_get_trustee_pending_response_id( $task_id, $step );
            $approved_estimate_id = intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true ) );
            if ( ! $response_id || $approved_estimate_id !== intval( $response_id ) ) {
                return;
            }
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, $response_id );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_AT, time() );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_BY, 0 );
            update_post_meta( $task_id, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
                intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) )
            );
            upkeepify_sync_task_lifecycle_status( $task_id );
            upkeepify_send_trustee_lifecycle_approval_email( $task_id, $response_id, 'quote' );
            if ( function_exists( 'upkeepify_send_quote_audit_email' ) ) {
                upkeepify_send_quote_audit_email( $task_id, $response_id );
            }
            break;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HTTP handler — approve / reject form submission
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_admin_post_trustee_approval_submit() {
    $task_id = isset( $_POST['task_id'] )       ? absint( wp_unslash( $_POST['task_id'] ) )                      : 0;
    $step    = isset( $_POST['step'] )           ? sanitize_key( wp_unslash( $_POST['step'] ) )                   : '';
    $token   = isset( $_POST['trustee_token'] )  ? sanitize_text_field( wp_unslash( $_POST['trustee_token'] ) )  : '';
    $action  = isset( $_POST['trustee_action'] ) ? sanitize_key( wp_unslash( $_POST['trustee_action'] ) )        : '';

    check_admin_referer( UPKEEPIFY_NONCE_ACTION_TRUSTEE_APPROVAL, UPKEEPIFY_NONCE_TRUSTEE_APPROVAL );

    $valid_steps   = array( UPKEEPIFY_TRUSTEE_STEP_TASK, UPKEEPIFY_TRUSTEE_STEP_ESTIMATE, UPKEEPIFY_TRUSTEE_STEP_QUOTE );
    $valid_actions = array( 'approve', 'reject' );

    if ( ! $task_id || ! in_array( $step, $valid_steps, true ) || ! in_array( $action, $valid_actions, true ) || empty( $token ) ) {
        wp_die( esc_html__( 'Invalid trustee approval request.', 'upkeepify' ) );
    }

    $task = get_post( $task_id );
    if ( ! $task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ) {
        wp_die( esc_html__( 'Task not found.', 'upkeepify' ) );
    }

    $email = upkeepify_validate_trustee_token( $task_id, $step, $token );
    if ( ! $email ) {
        wp_die( esc_html__( 'This approval link is invalid or has already been used.', 'upkeepify' ) );
    }

    if ( $action === 'approve' ) {
        upkeepify_process_trustee_approval( $task_id, $step, $email );
    } else {
        upkeepify_process_trustee_rejection( $task_id, $step, $email );
    }

    $settings = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $page     = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_APPROVAL_PAGE ] )
        ? $settings[ UPKEEPIFY_SETTING_TRUSTEE_APPROVAL_PAGE ]
        : home_url();

    wp_safe_redirect( add_query_arg( 'trustee_result', $action === 'approve' ? 'approved' : 'rejected', $page ) );
    exit;
}
add_action( 'admin_post_' . UPKEEPIFY_ADMIN_ACTION_TRUSTEE_APPROVAL_SUBMIT,        'upkeepify_admin_post_trustee_approval_submit' );
add_action( 'admin_post_nopriv_' . UPKEEPIFY_ADMIN_ACTION_TRUSTEE_APPROVAL_SUBMIT, 'upkeepify_admin_post_trustee_approval_submit' );

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle helper
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return UPKEEPIFY_TASK_STATUS_PENDING_TASK_APPROVAL when the task is
 * awaiting trustee sign-off at the very first gate; null otherwise.
 */
function upkeepify_get_pending_task_approval_status( $task_id ) {
    if ( ! upkeepify_trustee_approval_enabled() ) {
        return null;
    }
    if ( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_TASK_AT, true ) ) {
        return null;
    }
    if ( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REJECTION_INFO, true ) ) {
        return null;
    }
    $tokens = upkeepify_get_trustee_tokens( $task_id );
    return ! empty( $tokens[ UPKEEPIFY_TRUSTEE_STEP_TASK ] )
        ? UPKEEPIFY_TASK_STATUS_PENDING_TASK_APPROVAL
        : null;
}

// ─────────────────────────────────────────────────────────────────────────────
// Shortcode — [upkeepify_trustee_approval]
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_trustee_approval_shortcode( $atts ) {
    // Result landing (after POST redirect)
    if ( isset( $_GET['trustee_result'] ) ) {
        $result = sanitize_key( wp_unslash( $_GET['trustee_result'] ) );
        if ( $result === 'approved' ) {
            return '<div class="upkeepify-notice upkeepify-notice--success">'
                . esc_html__( 'Thank you — your approval has been recorded.', 'upkeepify' )
                . '</div>';
        }
        if ( $result === 'rejected' ) {
            return '<div class="upkeepify-notice upkeepify-notice--warning">'
                . esc_html__( 'Rejection recorded. The task has been placed on hold.', 'upkeepify' )
                . '</div>';
        }
    }

    $raw_token = isset( $_GET[ UPKEEPIFY_QUERY_VAR_TRUSTEE_TOKEN ] )
        ? sanitize_text_field( wp_unslash( $_GET[ UPKEEPIFY_QUERY_VAR_TRUSTEE_TOKEN ] ) )
        : '';
    $task_id   = isset( $_GET['task_id'] ) ? absint( wp_unslash( $_GET['task_id'] ) ) : 0;
    $step      = isset( $_GET['step'] )    ? sanitize_key( wp_unslash( $_GET['step'] ) )    : '';

    $valid_steps = array( UPKEEPIFY_TRUSTEE_STEP_TASK, UPKEEPIFY_TRUSTEE_STEP_ESTIMATE, UPKEEPIFY_TRUSTEE_STEP_QUOTE );

    if ( ! $raw_token || ! $task_id || ! in_array( $step, $valid_steps, true ) ) {
        return '<p>' . esc_html__( 'No approval request found. Please use the link from your email.', 'upkeepify' ) . '</p>';
    }

    $task = get_post( $task_id );
    if ( ! $task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ) {
        return '<p>' . esc_html__( 'Task not found.', 'upkeepify' ) . '</p>';
    }

    $email = upkeepify_validate_trustee_token( $task_id, $step, $raw_token );
    if ( ! $email ) {
        return '<div class="upkeepify-notice upkeepify-notice--error">'
            . esc_html__( 'This link is invalid or has already been used.', 'upkeepify' )
            . '</div>';
    }

    $response_id = upkeepify_get_trustee_pending_response_id( $task_id, $step );
    $currency    = upkeepify_get_setting_cached( UPKEEPIFY_SETTING_CURRENCY, '$' );
    $settings    = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    if ( is_array( $settings ) && isset( $settings[ UPKEEPIFY_SETTING_CURRENCY ] ) ) {
        $currency = $settings[ UPKEEPIFY_SETTING_CURRENCY ];
    }

    ob_start();

    echo '<div class="upkeepify-trustee-approval">';

    // ── Task details ──────────────────────────────────────────────────────────
    echo '<h2>' . esc_html( $task->post_title ) . '</h2>';

    $unit = get_post_meta( $task_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, true );
    if ( $unit ) {
        echo '<p><strong>' . esc_html__( 'Unit/Location:', 'upkeepify' ) . '</strong> ' . esc_html( $unit ) . '</p>';
    }

    echo '<div class="upkeepify-trustee-approval__description">';
    echo wp_kses_post( wpautop( $task->post_content ) );
    echo '</div>';

    // ── Task photos (attached to the task post) ───────────────────────────────
    $task_images = get_attached_media( 'image', $task_id );
    if ( ! empty( $task_images ) ) {
        echo '<h3>' . esc_html__( 'Submitted Photos', 'upkeepify' ) . '</h3>';
        echo '<div class="upkeepify-photo-grid">';
        foreach ( $task_images as $img ) {
            echo '<a href="' . esc_url( wp_get_attachment_url( $img->ID ) ) . '" target="_blank" rel="noopener">';
            echo wp_get_attachment_image( $img->ID, 'medium' );
            echo '</a>';
        }
        echo '</div>';
    }

    // ── Estimate / quote details ──────────────────────────────────────────────
    if ( $response_id ) {
        $provider_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
        $provider_name = $provider_id ? get_term( $provider_id )->name ?? '' : '';

        if ( $provider_name ) {
            echo '<p><strong>' . esc_html__( 'Contractor:', 'upkeepify' ) . '</strong> ' . esc_html( $provider_name ) . '</p>';
        }

        $availability = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_AVAILABILITY, true );
        if ( $availability ) {
            echo '<p><strong>' . esc_html__( 'Earliest Availability:', 'upkeepify' ) . '</strong> ' . esc_html( $availability ) . '</p>';
        }

        if ( $step === UPKEEPIFY_TRUSTEE_STEP_ESTIMATE ) {
            $estimate    = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true );
            $est_low     = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_LOW, true );
            $est_high    = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_HIGH, true );
            $note        = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_NOTE, true );
            $confidence  = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_CONFIDENCE, true );

            echo '<h3>' . esc_html__( 'Estimate', 'upkeepify' ) . '</h3>';
            if ( $estimate !== '' ) {
                echo '<p><strong>' . esc_html__( 'Amount:', 'upkeepify' ) . '</strong> '
                    . esc_html( $currency ) . esc_html( number_format( (float) $estimate, 2 ) ) . '</p>';
            }
            if ( $est_low !== '' && $est_high !== '' ) {
                echo '<p><strong>' . esc_html__( 'Range:', 'upkeepify' ) . '</strong> '
                    . esc_html( $currency ) . esc_html( number_format( (float) $est_low, 2 ) )
                    . ' – '
                    . esc_html( $currency ) . esc_html( number_format( (float) $est_high, 2 ) ) . '</p>';
            }
            if ( $confidence ) {
                echo '<p><strong>' . esc_html__( 'Confidence:', 'upkeepify' ) . '</strong> ' . esc_html( ucfirst( $confidence ) ) . '</p>';
            }
            if ( $note ) {
                echo '<p><strong>' . esc_html__( 'Note:', 'upkeepify' ) . '</strong> ' . esc_html( $note ) . '</p>';
            }
        }

        if ( $step === UPKEEPIFY_TRUSTEE_STEP_QUOTE ) {
            $formal_quote    = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true );
            $quote_note      = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_NOTE, true );
            $quote_attachments = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_ATTACHMENTS, true );
            $orig_estimate   = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true );

            echo '<h3>' . esc_html__( 'Formal Quote', 'upkeepify' ) . '</h3>';
            if ( $formal_quote !== '' ) {
                echo '<p><strong>' . esc_html__( 'Amount:', 'upkeepify' ) . '</strong> '
                    . esc_html( $currency ) . esc_html( number_format( (float) $formal_quote, 2 ) ) . '</p>';
            }
            if ( $orig_estimate !== '' ) {
                echo '<p><strong>' . esc_html__( 'Original Estimate:', 'upkeepify' ) . '</strong> '
                    . esc_html( $currency ) . esc_html( number_format( (float) $orig_estimate, 2 ) ) . '</p>';
            }
            if ( $quote_note ) {
                echo '<p><strong>' . esc_html__( 'Conditions:', 'upkeepify' ) . '</strong> ' . esc_html( $quote_note ) . '</p>';
            }

            // Quote attachments
            if ( ! empty( $quote_attachments ) && is_array( $quote_attachments ) ) {
                echo '<h4>' . esc_html__( 'Quote Documents', 'upkeepify' ) . '</h4><ul>';
                foreach ( $quote_attachments as $att_id ) {
                    $att_id  = intval( $att_id );
                    $att_url = wp_get_attachment_url( $att_id );
                    if ( $att_url ) {
                        echo '<li><a href="' . esc_url( $att_url ) . '" target="_blank" rel="noopener">'
                            . esc_html( get_the_title( $att_id ) ?: basename( $att_url ) )
                            . '</a></li>';
                    }
                }
                echo '</ul>';
            }
        }

        // Completion photos (available for quote step review)
        $completion_photos = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETION_PHOTOS, true );
        if ( ! empty( $completion_photos ) && is_array( $completion_photos ) ) {
            echo '<h3>' . esc_html__( 'Completion Photos', 'upkeepify' ) . '</h3>';
            echo '<div class="upkeepify-photo-grid">';
            foreach ( $completion_photos as $photo_id ) {
                $photo_id = intval( $photo_id );
                echo '<a href="' . esc_url( wp_get_attachment_url( $photo_id ) ) . '" target="_blank" rel="noopener">';
                echo wp_get_attachment_image( $photo_id, 'medium' );
                echo '</a>';
            }
            echo '</div>';
        }
    }

    // ── Approval form ─────────────────────────────────────────────────────────
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="upkeepify-trustee-approval__form">';
    echo '<input type="hidden" name="action"         value="' . esc_attr( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_APPROVAL_SUBMIT ) . '">';
    echo '<input type="hidden" name="task_id"        value="' . esc_attr( $task_id ) . '">';
    echo '<input type="hidden" name="step"           value="' . esc_attr( $step ) . '">';
    echo '<input type="hidden" name="trustee_token"  value="' . esc_attr( $raw_token ) . '">';
    wp_nonce_field( UPKEEPIFY_NONCE_ACTION_TRUSTEE_APPROVAL, UPKEEPIFY_NONCE_TRUSTEE_APPROVAL );

    echo '<div class="upkeepify-trustee-approval__actions">';
    echo '<button type="submit" name="trustee_action" value="approve" class="upkeepify-btn upkeepify-btn--approve">'
        . esc_html__( 'Approve', 'upkeepify' ) . '</button>';
    echo '<button type="submit" name="trustee_action" value="reject"  class="upkeepify-btn upkeepify-btn--reject">'
        . esc_html__( 'Reject', 'upkeepify' ) . '</button>';
    echo '</div>';
    echo '</form>';

    echo '</div>'; // .upkeepify-trustee-approval

    return ob_get_clean();
}
add_shortcode( UPKEEPIFY_SHORTCODE_TRUSTEE_APPROVAL_FORM, 'upkeepify_trustee_approval_shortcode' );

// ─────────────────────────────────────────────────────────────────────────────
// Cron — reminder emails
// ─────────────────────────────────────────────────────────────────────────────

function upkeepify_schedule_trustee_reminders() {
    if ( ! wp_next_scheduled( UPKEEPIFY_CRON_TRUSTEE_REMINDERS ) ) {
        wp_schedule_event( time(), 'daily', UPKEEPIFY_CRON_TRUSTEE_REMINDERS );
    }
}

function upkeepify_unschedule_trustee_reminders() {
    $ts = wp_next_scheduled( UPKEEPIFY_CRON_TRUSTEE_REMINDERS );
    if ( $ts ) {
        wp_unschedule_event( $ts, UPKEEPIFY_CRON_TRUSTEE_REMINDERS );
    }
}

function upkeepify_run_trustee_reminder_check() {
    $settings      = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $max_reminders = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REMINDER_COUNT ] )    ? intval( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REMINDER_COUNT ] )    : 3;
    $interval_days = isset( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REMINDER_INTERVAL ] ) ? intval( $settings[ UPKEEPIFY_SETTING_TRUSTEE_REMINDER_INTERVAL ] ) : 2;

    if ( $max_reminders < 1 || $interval_days < 1 ) {
        return;
    }

    $steps = array( UPKEEPIFY_TRUSTEE_STEP_TASK, UPKEEPIFY_TRUSTEE_STEP_ESTIMATE, UPKEEPIFY_TRUSTEE_STEP_QUOTE );
    foreach ( $steps as $step ) {
        foreach ( upkeepify_get_tasks_pending_trustee_step( $step ) as $task_id ) {
            upkeepify_maybe_send_trustee_reminders( $task_id, $step, $max_reminders, $interval_days );
        }
    }
}
add_action( UPKEEPIFY_CRON_TRUSTEE_REMINDERS, 'upkeepify_run_trustee_reminder_check' );

/**
 * Return task IDs that have live tokens for a step but haven't reached threshold.
 *
 * @return int[]
 */
function upkeepify_get_tasks_pending_trustee_step( $step ) {
    $query = new WP_Query( array(
        'post_type'      => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => UPKEEPIFY_META_KEY_TRUSTEE_TOKENS,
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    $required   = upkeepify_get_trustee_required_approvals();
    $qualifying = array();

    foreach ( $query->posts as $task_id ) {
        $tokens = upkeepify_get_trustee_tokens( $task_id );
        if ( empty( $tokens[ $step ] ) ) {
            continue;
        }
        $approvals = upkeepify_get_trustee_approvals( $task_id );
        if ( isset( $approvals[ $step ] ) && count( $approvals[ $step ] ) >= $required ) {
            continue;
        }
        $rejections = upkeepify_get_trustee_rejections( $task_id );
        if ( ! empty( $rejections[ $step ] ) ) {
            continue;
        }
        $qualifying[] = intval( $task_id );
    }

    return $qualifying;
}

/**
 * Send a reminder email to each trustee who hasn't yet responded, if the
 * reminder interval has elapsed and the cap hasn't been hit.
 */
function upkeepify_maybe_send_trustee_reminders( $task_id, $step, $max_reminders, $interval_days ) {
    $requested_map = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_APPROVAL_REQUESTED_AT, true );
    $initiated     = is_array( $requested_map ) && isset( $requested_map[ $step ] ) ? intval( $requested_map[ $step ] ) : 0;
    if ( ! $initiated ) {
        return;
    }

    $counts          = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REMINDER_COUNTS, true );
    $counts          = is_array( $counts ) ? $counts : array();
    $sent            = isset( $counts[ $step ] ) ? intval( $counts[ $step ] ) : 0;

    if ( $sent >= $max_reminders ) {
        return;
    }

    $next_due = $initiated + ( ( $sent + 1 ) * $interval_days * DAY_IN_SECONDS );
    if ( time() < $next_due ) {
        return;
    }

    $tokens     = upkeepify_get_trustee_tokens( $task_id );
    $approvals  = upkeepify_get_trustee_approvals( $task_id );
    $rejections = upkeepify_get_trustee_rejections( $task_id );
    $task       = get_post( $task_id );
    $response_id = upkeepify_get_trustee_pending_response_id( $task_id, $step );

    if ( empty( $tokens[ $step ] ) || ! $task ) {
        return;
    }

    foreach ( $tokens[ $step ] as $email => $token ) {
        if ( isset( $approvals[ $step ][ $email ] ) || isset( $rejections[ $step ][ $email ] ) ) {
            continue;
        }
        $url = upkeepify_get_trustee_approval_url( $task_id, $step, $token );
        if ( $url ) {
            upkeepify_send_trustee_approval_request( $email, $task, $step, $url, $response_id, true );
        }
    }

    $counts[ $step ] = $sent + 1;
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TRUSTEE_REMINDER_COUNTS, $counts );
}
