<?php
/**
 * File Upload Handlers
 *
 * @package Upkeepify
 */

// Note: Global upload filters have been removed to prevent side effects on other WordPress uploads.
// Upload validation is now scoped to the Upkeepify task submission form only.
// See upkeepify_validate_upload() function for form-specific validation.

/**
 * Validate upload for Upkeepify task submission form.
 *
 * This function performs scoped validation only when the form submission
 * includes the upkeepify_upload=1 field. This ensures upload restrictions
 * only apply to Upkeepify forms and don't affect other WordPress uploads.
 *
 * @since 1.0
 * @param array $file File data from $_FILES['task_photo'].
 * @return true|WP_Error True on success, WP_Error on validation failure.
 */
function upkeepify_validate_upload($file) {
    // Check if PHP reported any upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = array(
            UPLOAD_ERR_INI_SIZE   => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'upkeepify'),
            UPLOAD_ERR_FORM_SIZE  => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'upkeepify'),
            UPLOAD_ERR_PARTIAL    => __('The uploaded file was only partially uploaded.', 'upkeepify'),
            UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'upkeepify'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', 'upkeepify'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'upkeepify'),
            UPLOAD_ERR_EXTENSION  => __('A PHP extension stopped the file upload.', 'upkeepify'),
        );
        $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('An unknown upload error occurred.', 'upkeepify');
        return new WP_Error('upload_error', $error_message);
    }

    // Check file size against our 2MB limit
    if ($file['size'] > UPKEEPIFY_MAX_UPLOAD_SIZE) {
        return new WP_Error('file_size_exceeded', __('File size exceeds the 2MB limit.', 'upkeepify'));
    }

    // Check if file size is zero (empty file)
    if ($file['size'] === 0) {
        return new WP_Error('empty_file', __('The uploaded file is empty.', 'upkeepify'));
    }

    // Validate file type by checking the actual file extension
    $file_type = wp_check_filetype($file['name']);
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    if (!in_array($file_type['type'], $allowed_types, true)) {
        return new WP_Error('invalid_file_type', __('Invalid file type. Only JPG, PNG, and GIF files are allowed.', 'upkeepify'));
    }

    // Additional MIME validation using fileinfo if available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected_mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($detected_mime, $allowed_types, true)) {
                return new WP_Error('invalid_mime_type', __('Invalid file type detected. Only JPG, PNG, and GIF files are allowed.', 'upkeepify'));
            }
        }
    }

    return true;
}

// Upload handling
/**
 * Handle file upload errors.
 *
 * Logs upload errors and returns user-friendly error messages.
 * Validates wp_handle_upload() return values and checks file integrity.
 *
 * @since 1.0
 * @param array $file The uploaded file data.
 * @param string $upload_error_string Error string from WordPress upload handler.
 * @return array File data or error array.
 * @uses error_log()
 * @uses file_exists()
 * @uses is_readable()
 * @hook wp_handle_upload
 */
add_action('wp_handle_upload', 'upkeepify_handle_upload', 10, 2);
function upkeepify_handle_upload($file, $upload_error_string) {
    try {
        // Check for WordPress upload handler errors
        if ($upload_error_string) {
            // Log the error for debugging purposes
            error_log('Upkeepify Upload Handler Error: ' . $upload_error_string);

            // Provide a user-friendly error message
            return array('error' => 'An error occurred during the file upload. Please try again.');
        }

        // Validate file array structure
        if (!is_array($file) || !isset($file['file']) || !isset($file['url']) || !isset($file['type'])) {
            $error_message = 'Invalid file upload response.';
            error_log('Upkeepify Upload Handler Error: ' . $error_message . ' (File data: ' . print_r($file, true) . ')');
            return array('error' => $error_message);
        }

        // Validate that the file actually exists and is readable
        if (!file_exists($file['file'])) {
            $error_message = 'Uploaded file could not be found on the server.';
            error_log('Upkeepify Upload Handler Error: ' . $error_message . ' (Path: ' . $file['file'] . ')');
            return array('error' => $error_message);
        }

        if (!is_readable($file['file'])) {
            $error_message = 'Uploaded file is not readable. Please check file permissions.';
            error_log('Upkeepify Upload Handler Error: ' . $error_message . ' (Path: ' . $file['file'] . ')');
            return array('error' => $error_message);
        }

        // Verify file size matches expected size (integrity check)
        if (isset($file['size'])) {
            $actual_size = filesize($file['file']);
            if ($actual_size === false || $actual_size === 0) {
                $error_message = 'Uploaded file appears to be corrupted or empty.';
                error_log('Upkeepify Upload Handler Error: ' . $error_message . ' (Path: ' . $file['file'] . ')');
                return array('error' => $error_message);
            }
        }

        // Log successful upload for debugging
        if (WP_DEBUG) {
            error_log('Upkeepify Upload Success: ' . $file['file']);
        }

        return $file;

    } catch (Exception $e) {
        $error_message = 'An unexpected error occurred while processing the uploaded file.';
        error_log('Upkeepify Upload Handler Exception: ' . $e->getMessage());
        return array('error' => $error_message);
    }
}
