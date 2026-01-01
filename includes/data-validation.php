<?php
/**
 * Data Validation Functions
 *
 * @package Upkeepify
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Validate a single task meta value.
 *
 * @since 1.0
 * @param string $meta_key
 * @param mixed $value
 * @return true|WP_Error
 */
function upkeepify_validate_task_meta($meta_key, $value) {
    $definition = upkeepify_get_meta_field_definition($meta_key);

    if (!$definition) {
        return new WP_Error('upkeepify_invalid_meta_key', 'Unknown meta key: ' . $meta_key);
    }

    switch ($meta_key) {
        case UPKEEPIFY_META_KEY_NEAREST_UNIT:
            $int_value = is_numeric($value) ? intval($value) : 0;
            if ($int_value < 1) {
                return new WP_Error('upkeepify_invalid_nearest_unit', 'Nearest unit must be an integer >= 1.');
            }
            return true;

        case UPKEEPIFY_META_KEY_GPS_LATITUDE:
            if ($value === '' || $value === null) {
                return true;
            }
            if (!is_numeric($value)) {
                return new WP_Error('upkeepify_invalid_latitude', 'Latitude must be numeric.');
            }
            $lat = floatval($value);
            if ($lat < -90 || $lat > 90) {
                return new WP_Error('upkeepify_invalid_latitude', 'Latitude must be between -90 and 90.');
            }
            return true;

        case UPKEEPIFY_META_KEY_GPS_LONGITUDE:
            if ($value === '' || $value === null) {
                return true;
            }
            if (!is_numeric($value)) {
                return new WP_Error('upkeepify_invalid_longitude', 'Longitude must be numeric.');
            }
            $lng = floatval($value);
            if ($lng < -180 || $lng > 180) {
                return new WP_Error('upkeepify_invalid_longitude', 'Longitude must be between -180 and 180.');
            }
            return true;

        case UPKEEPIFY_META_KEY_ROUGH_ESTIMATE:
            if (!is_string($value) && !is_numeric($value)) {
                return new WP_Error('upkeepify_invalid_rough_estimate', 'Rough estimate must be a string.');
            }
            if (strlen((string) $value) > 50) {
                return new WP_Error('upkeepify_invalid_rough_estimate', 'Rough estimate is too long.');
            }
            return true;

        case UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN:
        case UPKEEPIFY_META_KEY_RESPONSE_TOKEN:
            if (!is_string($value) || $value === '') {
                return new WP_Error('upkeepify_invalid_token', 'Token must be a non-empty string.');
            }
            if (strlen($value) < 8) {
                return new WP_Error('upkeepify_invalid_token', 'Token is too short.');
            }
            return true;

        case UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER:
            if ($value === '' || $value === null) {
                return true;
            }
            if (!is_string($value) && !is_numeric($value)) {
                return new WP_Error('upkeepify_invalid_assigned_provider', 'Assigned service provider must be a term ID or slug.');
            }
            if (is_numeric($value) && intval($value) < 1) {
                return new WP_Error('upkeepify_invalid_assigned_provider', 'Assigned service provider ID must be > 0.');
            }
            return true;

        case UPKEEPIFY_META_KEY_RESPONSE_TASK_ID:
            if (!is_numeric($value) || intval($value) < 1) {
                return new WP_Error('upkeepify_invalid_response_task_id', 'Response task ID must be a positive integer.');
            }
            return true;

        case UPKEEPIFY_META_KEY_PROVIDER_ID:
            if (!is_numeric($value) || intval($value) < 1) {
                return new WP_Error('upkeepify_invalid_provider_id', 'Provider ID must be a positive integer.');
            }
            return true;

        case UPKEEPIFY_META_KEY_DUE_DATE:
            if ($value === '' || $value === null) {
                return true;
            }
            if (!is_string($value) || strtotime($value) === false) {
                return new WP_Error('upkeepify_invalid_due_date', 'Due date must be a valid date string.');
            }
            return true;

        default:
            return true;
    }
}

/**
 * Validate maintenance task data (post + meta).
 *
 * Expected keys:
 * - post_title
 * - post_content
 * - meta (array)
 *
 * @since 1.0
 * @param array $data
 * @return true|WP_Error
 */
function upkeepify_validate_maintenance_task($data) {
    if (!is_array($data)) {
        return new WP_Error('upkeepify_invalid_task_data', 'Task data must be an array.');
    }

    $title = isset($data['post_title']) ? (string) $data['post_title'] : '';
    $content = isset($data['post_content']) ? (string) $data['post_content'] : '';

    if (trim($title) === '') {
        return new WP_Error('upkeepify_invalid_task_title', 'Task title is required.');
    }

    if (strlen($title) > 200) {
        return new WP_Error('upkeepify_invalid_task_title', 'Task title is too long.');
    }

    if (trim($content) === '') {
        return new WP_Error('upkeepify_invalid_task_content', 'Task description is required.');
    }

    if (isset($data['meta']) && is_array($data['meta'])) {
        foreach ($data['meta'] as $meta_key => $meta_value) {
            $result = upkeepify_validate_task_meta($meta_key, $meta_value);
            if (is_wp_error($result)) {
                return $result;
            }
        }
    }

    return true;
}

/**
 * Validate service provider data (taxonomy term + term meta).
 *
 * @since 1.0
 * @param array $data
 * @return true|WP_Error
 */
function upkeepify_validate_provider($data) {
    if (!is_array($data)) {
        return new WP_Error('upkeepify_invalid_provider_data', 'Provider data must be an array.');
    }

    if (isset($data['name']) && trim((string) $data['name']) === '') {
        return new WP_Error('upkeepify_invalid_provider_name', 'Provider name cannot be empty.');
    }

    if (isset($data['email']) && $data['email'] !== '') {
        $email = (string) $data['email'];
        if (!is_email($email)) {
            return new WP_Error('upkeepify_invalid_provider_email', 'Provider email address is invalid.');
        }
    }

    if (isset($data['phone']) && $data['phone'] !== '') {
        $phone = (string) $data['phone'];
        if (strlen($phone) > 50) {
            return new WP_Error('upkeepify_invalid_provider_phone', 'Provider phone number is too long.');
        }
    }

    if (isset($data['associated_categories']) && $data['associated_categories'] !== null) {
        if (!is_array($data['associated_categories'])) {
            return new WP_Error('upkeepify_invalid_provider_categories', 'Associated categories must be an array.');
        }

        foreach ($data['associated_categories'] as $term_id) {
            if (!is_numeric($term_id) || intval($term_id) < 1) {
                return new WP_Error('upkeepify_invalid_provider_categories', 'Associated categories must be an array of term IDs.');
            }
        }
    }

    return true;
}

/**
 * Validate and sanitize settings.
 *
 * @since 1.0
 * @param array $settings
 * @return array|WP_Error Sanitized settings array or WP_Error
 */
function upkeepify_validate_settings($settings) {
    if (!is_array($settings)) {
        return new WP_Error('upkeepify_invalid_settings', 'Settings must be an array.');
    }

    $sanitized = array();

    foreach ($settings as $key => $value) {
        switch ($key) {
            case UPKEEPIFY_SETTING_SMTP_OPTION:
            case UPKEEPIFY_SETTING_NOTIFY_OPTION:
            case UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK:
            case UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING:
            case UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE:
            case UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE:
                $sanitized[$key] = !empty($value) ? 1 : 0;
                break;

            case UPKEEPIFY_SETTING_SMTP_HOST:
            case UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL:
                $sanitized[$key] = sanitize_text_field((string) $value);
                break;

            case UPKEEPIFY_SETTING_OVERRIDE_EMAIL:
                $email = sanitize_email((string) $value);
                if ($email !== '' && !is_email($email)) {
                    return new WP_Error('upkeepify_invalid_override_email', 'Override email address is invalid.');
                }
                $sanitized[$key] = $email;
                break;

            case UPKEEPIFY_SETTING_NUMBER_OF_UNITS:
                $count = intval($value);
                if ($count < 0) {
                    return new WP_Error('upkeepify_invalid_number_of_units', 'Number of units must be 0 or greater.');
                }
                $sanitized[$key] = $count;
                break;

            case UPKEEPIFY_SETTING_CURRENCY:
                $currency = sanitize_text_field((string) $value);
                if (strlen($currency) > 8) {
                    return new WP_Error('upkeepify_invalid_currency', 'Currency symbol is too long.');
                }
                $sanitized[$key] = $currency;
                break;

            default:
                $sanitized[$key] = sanitize_text_field((string) $value);
                break;
        }
    }

    return $sanitized;
}
