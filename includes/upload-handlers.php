<?php
/**
 * File Upload Handlers
 *
 * @package Upkeepify
 */

// File size limit
add_filter('wp_max_upload_size', 'upkeepify_upload_size_limit');
function upkeepify_upload_size_limit($size) {
    return 2 * 1024 * 1024; // 2MB
}

// Allowed file types
add_filter('upload_mimes', 'upkeepify_allowed_file_types');
function upkeepify_allowed_file_types($existing_mimes) {
    $allowed_mimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'gif'          => 'image/gif',
    );
    return $allowed_mimes;
}

// Upload prefilter
add_filter('wp_handle_upload_prefilter', 'upkeepify_upload_prefilter', 10, 2);
function upkeepify_upload_prefilter($file, $errors) {
    // Handle upload errors
}

// Upload handling
add_action('wp_handle_upload', 'upkeepify_handle_upload', 10, 2);
function upkeepify_handle_upload($file, $upload_error_string) {
    // Handle upload errors
}