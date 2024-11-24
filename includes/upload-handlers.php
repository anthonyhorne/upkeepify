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
function upkeepify_upload_prefilter($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $file['error'] = 'An error occurred during the file upload.';
        return $file;
    }

    // Check file size
    if ($file['size'] > 2 * 1024 * 1024) {
        $file['error'] = 'File size exceeds the 2MB limit.';
        return $file;
    }

    // Check file type
    $file_type = wp_check_filetype($file['name']);
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    if (!in_array($file_type['type'], $allowed_types)) {
        $file['error'] = 'Invalid file type. Only JPG, PNG, and GIF files are allowed.';
        return $file;
    }

    return $file;
}

// Upload handling
add_action('wp_handle_upload', 'upkeepify_handle_upload', 10, 2);
function upkeepify_handle_upload($file, $upload_error_string) {
    if ($upload_error_string) {
        // Log the error for debugging purposes
        error_log('Upload error: ' . $upload_error_string);

        // Provide a user-friendly error message
        return array('error' => 'An error occurred during the file upload. Please try again.');
    }

    return $file;
}
