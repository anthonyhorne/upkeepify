<?php
/**
 * File Upload Handlers
 *
 * @package Upkeepify
 */

// File size limit
/**
 * Set maximum upload size limit.
 *
 * Restricts file uploads to 2MB maximum size for security and performance.
 *
 * @since 1.0
 * @param int $size The current WordPress maximum upload size.
 * @return int The custom maximum upload size (2MB).
 * @hook wp_max_upload_size
 */
add_filter('wp_max_upload_size', 'upkeepify_get_upload_size_limit');
function upkeepify_get_upload_size_limit($size) {
    return UPKEEPIFY_MAX_UPLOAD_SIZE;
}

// Allowed file types
/**
 * Restrict allowed file types for uploads.
 *
 * Limits uploads to image files only (JPG, PNG, GIF).
 *
 * @since 1.0
 * @param array $existing_mimes Existing allowed MIME types.
 * @return array Filtered allowed MIME types.
 * @hook upload_mimes
 */
add_filter('upload_mimes', 'upkeepify_get_allowed_file_types');
function upkeepify_get_allowed_file_types($existing_mimes) {
    $allowed_mimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'gif'          => 'image/gif',
    );
    return $allowed_mimes;
}

// Upload prefilter
/**
 * Validate uploaded file before processing.
 *
 * Checks for upload errors, file size, and file type.
 * Returns error message if validation fails.
 *
 * @since 1.0
 * @param array $file File data array.
 * @return array Modified file data or error.
 * @uses wp_check_filetype()
 * @hook wp_handle_upload_prefilter
 */
add_filter('wp_handle_upload_prefilter', 'upkeepify_upload_prefilter', 10, 2);
function upkeepify_upload_prefilter($file) {
    // First, check if PHP reported any upload errors
    // UPLOAD_ERR_OK (0) means no errors occurred
    // Other error codes indicate various issues (size, partial upload, etc.)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $file['error'] = 'An error occurred during the file upload.';
        return $file;
    }

    // Check file size against our 2MB limit
    // This double-checks the PHP upload_max_filesize setting
    // to ensure files meet plugin-specific requirements
    if ($file['size'] > UPKEEPIFY_MAX_UPLOAD_SIZE) {
        $file['error'] = 'File size exceeds the 2MB limit.';
        return $file;
    }

    // Validate file type by checking the actual file extension
    // wp_check_filetype() looks up the MIME type from WordPress's allowed list
    // We then verify it's one of our explicitly allowed image types
    $file_type = wp_check_filetype($file['name']);
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    if (!in_array($file_type['type'], $allowed_types)) {
        $file['error'] = 'Invalid file type. Only JPG, PNG, and GIF files are allowed.';
        return $file;
    }

    // All validations passed, return file for further processing
    return $file;
}

// Upload handling
/**
 * Handle file upload errors.
 *
 * Logs upload errors and returns user-friendly error messages.
 *
 * @since 1.0
 * @param array $file The uploaded file data.
 * @param string $upload_error_string Error string from WordPress upload handler.
 * @return array File data or error array.
 * @uses error_log()
 * @hook wp_handle_upload
 */
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
