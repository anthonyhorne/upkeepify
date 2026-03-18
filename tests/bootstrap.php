<?php
/**
 * Bootstrap file for PHPUnit tests.
 *
 * Sets up a minimal WordPress environment for unit testing the plugin.
 * Defines WordPress function stubs to allow testing without a live WP install.
 *
 * @package Upkeepify
 */

// Define WPINC to simulate WordPress environment
define('WPINC', 'wp-includes');

// Load plugin constants
require_once dirname(__DIR__) . '/includes/constants.php';

// WordPress function stubs for testing
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = false) {
        return '';
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value) {
        return true;
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id = null) {
        return null;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
        return true;
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true) {
        return str_repeat('a', $length);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return preg_replace('/[\r\n\t]/', '', strip_tags((string) $str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return (string) $str;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        return (object) ['user_login' => 'test_user'];
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 0;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        // Suppress in tests
    }
}

// Load files under test
require_once dirname(__DIR__) . '/includes/data-validation.php';
require_once dirname(__DIR__) . '/includes/utility-functions.php';
