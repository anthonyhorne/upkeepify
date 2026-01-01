<?php
/**
 * Meta Field Registry
 *
 * Central registry for all supported Upkeepify meta keys.
 *
 * @package Upkeepify
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Get the meta field registry.
 *
 * The registry is used by:
 * - validation helpers
 * - schema verification/health check
 * - developer documentation
 *
 * @since 1.0
 * @return array<string, array{constant:string,type:string,default:mixed,description:string,examples:array<int, mixed>,used_by:array<int, string>,validation:string}>
 */
function upkeepify_get_meta_field_registry() {
    return array(
        UPKEEPIFY_META_KEY_NEAREST_UNIT => array(
            'constant' => 'UPKEEPIFY_META_KEY_NEAREST_UNIT',
            'type' => 'integer',
            'default' => null,
            'description' => 'Stores the nearest unit number for the maintenance task.',
            'examples' => array(1, 12, 42),
            'used_by' => array(
                'upkeepify_nearest_unit_meta_box_callback()',
                'upkeepify_save_nearest_unit_meta_box_data()',
                'upkeepify_handle_task_form_submission()',
            ),
            'validation' => 'Integer >= 1',
        ),
        UPKEEPIFY_META_KEY_ROUGH_ESTIMATE => array(
            'constant' => 'UPKEEPIFY_META_KEY_ROUGH_ESTIMATE',
            'type' => 'string',
            'default' => '',
            'description' => 'A rough cost estimate entered by an admin for the task. Stored as text to allow currency symbols/ranges.',
            'examples' => array('150', '150-250', '$250'),
            'used_by' => array(
                'upkeepify_rough_estimate_meta_box_callback()',
                'upkeepify_save_rough_estimate_meta_box_data()',
                'upkeepify_list_tasks_shortcode()',
            ),
            'validation' => 'String up to 50 chars (recommended: numeric/range)',
        ),
        UPKEEPIFY_META_KEY_GPS_LATITUDE => array(
            'constant' => 'UPKEEPIFY_META_KEY_GPS_LATITUDE',
            'type' => 'string',
            'default' => '',
            'description' => 'Latitude captured from the task submission form.',
            'examples' => array('-33.865143', '40.712776'),
            'used_by' => array('upkeepify_handle_task_form_submission()'),
            'validation' => 'Numeric string between -90 and 90',
        ),
        UPKEEPIFY_META_KEY_GPS_LONGITUDE => array(
            'constant' => 'UPKEEPIFY_META_KEY_GPS_LONGITUDE',
            'type' => 'string',
            'default' => '',
            'description' => 'Longitude captured from the task submission form.',
            'examples' => array('151.209900', '-74.005974'),
            'used_by' => array('upkeepify_handle_task_form_submission()'),
            'validation' => 'Numeric string between -180 and 180',
        ),
        UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN => array(
            'constant' => 'UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN',
            'type' => 'string',
            'default' => '',
            'description' => 'Token that allows a service provider to update a task without logging in (when enabled).',
            'examples' => array('a8B3kLm9pQ2rS7tU1vWx'),
            'used_by' => array(
                'upkeepify_generate_task_update_token()',
                'upkeepify_validate_task_update_token()',
            ),
            'validation' => 'Non-empty string (recommended length 20)',
        ),
        UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER => array(
            'constant' => 'UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER',
            'type' => 'string|int',
            'default' => '',
            'description' => 'Tracks which service provider is assigned to a task. Implementation may store a provider term ID or slug.',
            'examples' => array('handyman-heroes', 123),
            'used_by' => array('upkeepify_tasks_by_provider_shortcode()'),
            'validation' => 'Non-empty string or positive integer',
        ),
        UPKEEPIFY_META_KEY_DUE_DATE => array(
            'constant' => 'UPKEEPIFY_META_KEY_DUE_DATE',
            'type' => 'string',
            'default' => '',
            'description' => 'Optional due date for a task (used by the task calendar shortcode).',
            'examples' => array('2026-01-01', '2026-01-01 10:00:00'),
            'used_by' => array('upkeepify_task_calendar_shortcode()'),
            'validation' => 'Date string parseable by strtotime()',
        ),
        UPKEEPIFY_META_KEY_RESPONSE_TASK_ID => array(
            'constant' => 'UPKEEPIFY_META_KEY_RESPONSE_TASK_ID',
            'type' => 'integer',
            'default' => 0,
            'description' => 'On provider response posts: references the maintenance task ID.',
            'examples' => array(101),
            'used_by' => array(
                'upkeepify_generate_provider_tokens()',
                'upkeepify_provider_response_form_shortcode()',
            ),
            'validation' => 'Positive integer (existing post ID)',
        ),
        UPKEEPIFY_META_KEY_PROVIDER_ID => array(
            'constant' => 'UPKEEPIFY_META_KEY_PROVIDER_ID',
            'type' => 'integer',
            'default' => 0,
            'description' => 'On provider response posts: references the service provider term_id.',
            'examples' => array(55),
            'used_by' => array('upkeepify_generate_provider_tokens()'),
            'validation' => 'Positive integer (existing term ID)',
        ),
        UPKEEPIFY_META_KEY_RESPONSE_TOKEN => array(
            'constant' => 'UPKEEPIFY_META_KEY_RESPONSE_TOKEN',
            'type' => 'string',
            'default' => '',
            'description' => 'Unique token used by service providers to access their response form.',
            'examples' => array('pR0v1d3rT0k3nAbCdEfGh'),
            'used_by' => array(
                'upkeepify_generate_provider_tokens()',
                'upkeepify_provider_response_form_shortcode()',
            ),
            'validation' => 'Non-empty string (recommended length 20)',
        ),
    );
}

/**
 * Get a single meta field definition.
 *
 * @since 1.0
 * @param string $meta_key
 * @return array|null
 */
function upkeepify_get_meta_field_definition($meta_key) {
    $registry = upkeepify_get_meta_field_registry();
    return isset($registry[$meta_key]) ? $registry[$meta_key] : null;
}
