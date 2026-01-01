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
 * Checks for upload errors, file size, disk space, and file type.
 * Returns error message if validation fails.
 *
 * @since 1.0
 * @param array $file File data array.
 * @return array Modified file data or error.
 * @uses wp_check_filetype()
 * @uses disk_free_space()
 * @uses error_log()
 * @hook wp_handle_upload_prefilter
 */
add_filter('wp_handle_upload_prefilter', 'upkeepify_upload_prefilter', 10, 2);
function upkeepify_upload_prefilter($file) {
    try {
        // First, check if PHP reported any upload errors
        // UPLOAD_ERR_OK (0) means no errors occurred
        // Other error codes indicate various issues (size, partial upload, etc.)
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            );
            $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'An unknown upload error occurred.';
            error_log('Upkeepify Upload Error: ' . $error_message);
            $file['error'] = $error_message;
            return $file;
        }

        // Check file size against our 2MB limit
        // This double-checks the PHP upload_max_filesize setting
        // to ensure files meet plugin-specific requirements
        if ($file['size'] > UPKEEPIFY_MAX_UPLOAD_SIZE) {
            $error_message = 'File size exceeds the 2MB limit.';
            error_log('Upkeepify Upload Error: ' . $error_message . ' (Size: ' . $file['size'] . ' bytes)');
            $file['error'] = $error_message;
            return $file;
        }

        // Check if file size is zero (empty file)
        if ($file['size'] === 0) {
            $error_message = 'The uploaded file is empty.';
            error_log('Upkeepify Upload Error: ' . $error_message);
            $file['error'] = $error_message;
            return $file;
        }

        // Check available disk space before processing
        // Require at least 5MB free space as a safety buffer
        $upload_dir = wp_upload_dir();
        $required_space = $file['size'] + (5 * 1024 * 1024); // File size + 5MB buffer
        if (function_exists('disk_free_space')) {
            $free_space = disk_free_space($upload_dir['basedir']);
            if ($free_space !== false && $free_space < $required_space) {
                $error_message = 'Insufficient disk space to complete the upload.';
                error_log('Upkeepify Upload Error: ' . $error_message . ' (Free: ' . round($free_space / 1024 / 1024, 2) . 'MB, Required: ' . round($required_space / 1024 / 1024, 2) . 'MB)');
                $file['error'] = $error_message;
                return $file;
            }
        }

        // Validate file type by checking the actual file extension
        // wp_check_filetype() looks up the MIME type from WordPress's allowed list
        // We then verify it's one of our explicitly allowed image types
        $file_type = wp_check_filetype($file['name']);
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file_type['type'], $allowed_types)) {
            $error_message = 'Invalid file type. Only JPG, PNG, and GIF files are allowed.';
            error_log('Upkeepify Upload Error: ' . $error_message . ' (Detected type: ' . $file_type['type'] . ')');
            $file['error'] = $error_message;
            return $file;
        }

        // Additional MIME validation using fileinfo if available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected_mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($detected_mime, $allowed_types)) {
                    $error_message = 'Invalid file type detected. Only JPG, PNG, and GIF files are allowed.';
                    error_log('Upkeepify Upload Error: ' . $error_message . ' (Detected MIME: ' . $detected_mime . ')');
                    $file['error'] = $error_message;
                    return $file;
                }
            }
        }

        // Check if upload directory is writable
        if (!is_writable($upload_dir['basedir'])) {
            $error_message = 'Upload directory is not writable. Please check file permissions.';
            error_log('Upkeepify Upload Error: ' . $error_message . ' (Directory: ' . $upload_dir['basedir'] . ')');
            $file['error'] = $error_message;
            return $file;
        }

        // All validations passed, return file for further processing
        if (WP_DEBUG) {
            error_log('Upkeepify Upload Validation Passed: ' . $file['name']);
        }
        return $file;

    } catch (Exception $e) {
        $error_message = 'An unexpected error occurred during upload validation.';
        error_log('Upkeepify Upload Exception: ' . $e->getMessage());
        $file['error'] = $error_message;
        return $file;
    }
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
