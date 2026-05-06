<?php
/**
 * System Logging Functions
 *
 * Provides persistent logging into a WordPress option for capturing email
 * failures, configuration warnings, and important lifecycle events.
 *
 * @package Upkeepify
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Maximum number of log entries to retain.
 */
define('UPKEEPIFY_LOG_MAX_ENTRIES', 500);

/**
 * Log a message to the persistent System Log.
 *
 * Appends a timestamped, typed entry to the upkeepify_system_log option and
 * trims the log to UPKEEPIFY_LOG_MAX_ENTRIES to prevent database bloat.
 *
 * @since 1.4.0
 * @param string $message The log message.
 * @param string $type    Log type: 'info', 'success', 'warning', 'error'.
 * @param array  $context Optional contextual data (task_id, email, etc.).
 * @return bool True if logged successfully.
 */
function upkeepify_log($message, $type = 'info', $context = array()) {
    if (!in_array($type, array('info', 'success', 'warning', 'error'), true)) {
        $type = 'info';
    }

    $entry = array(
        'timestamp' => current_time('mysql'),
        'type'      => $type,
        'message'   => sanitize_text_field($message),
        'context'   => is_array($context) ? array_map('sanitize_text_field', $context) : array(),
    );

    $log = get_option(UPKEEPIFY_OPTION_SYSTEM_LOG, array());
    if (!is_array($log)) {
        $log = array();
    }

    $log[] = $entry;

    // Trim to max entries
    if (count($log) > UPKEEPIFY_LOG_MAX_ENTRIES) {
        $log = array_slice($log, -UPKEEPIFY_LOG_MAX_ENTRIES);
    }

    $result = update_option(UPKEEPIFY_OPTION_SYSTEM_LOG, $log, false);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        $context_str = !empty($context) ? ' ' . wp_json_encode($context) : '';
        error_log('Upkeepify [' . strtoupper($type) . '] ' . $message . $context_str);
    }

    return $result !== false;
}

/**
 * Get all log entries.
 *
 * @since 1.4.0
 * @return array
 */
function upkeepify_get_log_entries() {
    $log = get_option(UPKEEPIFY_OPTION_SYSTEM_LOG, array());
    return is_array($log) ? $log : array();
}

/**
 * Clear all log entries.
 *
 * @since 1.4.0
 * @return bool
 */
function upkeepify_clear_log() {
    return update_option(UPKEEPIFY_OPTION_SYSTEM_LOG, array(), false);
}

/**
 * Get log entry count.
 *
 * @since 1.4.0
 * @return int
 */
function upkeepify_get_log_count() {
    $log = get_option(UPKEEPIFY_OPTION_SYSTEM_LOG, array());
    return is_array($log) ? count($log) : 0;
}
