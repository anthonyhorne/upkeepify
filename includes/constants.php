<?php
/**
 * Constants Definition File
 * 
 * This file centralizes all magic strings used throughout the Upkeepify plugin.
 * 
 * @package Upkeepify
 */

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

// Post Type Slugs
define('UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS', 'maintenance_tasks');
define('UPKEEPIFY_POST_TYPE_RESPONSES', 'upkeepify_responses');
define('UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES', 'provider_responses');

// Taxonomy Slugs
define('UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER', 'service_provider');
define('UPKEEPIFY_TAXONOMY_TASK_CATEGORY', 'task_category');
define('UPKEEPIFY_TAXONOMY_TASK_TYPE', 'task_type');
define('UPKEEPIFY_TAXONOMY_TASK_STATUS', 'task_status');
define('UPKEEPIFY_TAXONOMY_UNIT', 'unit');

// Meta Box IDs
define('UPKEEPIFY_META_BOX_NEAREST_UNIT', 'upkeepify_nearest_unit');
define('UPKEEPIFY_META_BOX_ROUGH_ESTIMATE', 'upkeepify_rough_estimate');

// Meta Keys (Post Meta)
define('UPKEEPIFY_META_KEY_NEAREST_UNIT', 'upkeepify_nearest_unit');
define('UPKEEPIFY_META_KEY_ROUGH_ESTIMATE', 'upkeepify_rough_estimate');
define('UPKEEPIFY_META_KEY_GPS_LATITUDE', 'upkeepify_gps_latitude');
define('UPKEEPIFY_META_KEY_GPS_LONGITUDE', 'upkeepify_gps_longitude');
define('UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN', '_upkeepify_task_update_token');
define('UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER', 'assigned_service_provider');

// Term Meta Keys
define('UPKEEPIFY_TERM_META_PROVIDER_PHONE', 'provider_phone');
define('UPKEEPIFY_TERM_META_PROVIDER_EMAIL', 'provider_email');
define('UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES', 'associated_task_categories');

// Response Post Meta Keys
define('UPKEEPIFY_META_KEY_RESPONSE_TASK_ID', 'response_task_id');
define('UPKEEPIFY_META_KEY_PROVIDER_ID', 'provider_id');
define('UPKEEPIFY_META_KEY_RESPONSE_TOKEN', 'response_token');

// Option Names
define('UPKEEPIFY_OPTION_SETTINGS', 'upkeepify_settings');
define('UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED', 'upkeepify_sample_data_inserted');
define('UPKEEPIFY_OPTION_NOTIFICATIONS', 'upkeepify_notifications');

// Settings Keys
define('UPKEEPIFY_SETTING_SMTP_OPTION', 'upkeepify_smtp_option');
define('UPKEEPIFY_SETTING_SMTP_HOST', 'upkeepify_smtp_host');
define('UPKEEPIFY_SETTING_NOTIFY_OPTION', 'upkeepify_notify_option');
define('UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK', 'upkeepify_provider_delete_task');
define('UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING', 'upkeepify_public_task_logging');
define('UPKEEPIFY_SETTING_OVERRIDE_EMAIL', 'upkeepify_override_email');
define('UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE', 'upkeepify_enable_token_update');
define('UPKEEPIFY_SETTING_NUMBER_OF_UNITS', 'upkeepify_number_of_units');
define('UPKEEPIFY_SETTING_CURRENCY', 'upkeepify_currency');
define('UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE', 'upkeepify_enable_thank_you_page');
define('UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL', 'upkeepify_thank_you_page_url');

// Shortcode Names
define('UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS', 'maintenance_tasks');
define('UPKEEPIFY_SHORTCODE_LIST_TASKS', 'upkeepify_list_tasks');
define('UPKEEPIFY_SHORTCODE_TASK_FORM', 'upkeepify_task_form');
define('UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM', 'upkeepify_provider_response_form');
define('UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY', 'upkeepify_tasks_by_category');
define('UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER', 'upkeepify_tasks_by_provider');
define('UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS', 'upkeepify_tasks_by_status');
define('UPKEEPIFY_SHORTCODE_TASK_SUMMARY', 'upkeepify_task_summary');
define('UPKEEPIFY_SHORTCODE_TASK_CALENDAR', 'upkeepify_task_calendar');

// Nonce Names
define('UPKEEPIFY_NONCE_NEAREST_UNIT', 'upkeepify_nearest_unit_nonce');
define('UPKEEPIFY_NONCE_ROUGH_ESTIMATE', 'upkeepify_rough_estimate_nonce');
define('UPKEEPIFY_NONCE_TASK_SUBMIT', 'upkeepify_task_submit_nonce');

// Nonce Actions
define('UPKEEPIFY_NONCE_ACTION_NEAREST_UNIT_SAVE', 'upkeepify_nearest_unit_save');
define('UPKEEPIFY_NONCE_ACTION_ROUGH_ESTIMATE_SAVE', 'upkeepify_rough_estimate_save');
define('UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT', 'upkeepify_task_submit_action');

// Post Type Labels
define('UPKEEPIFY_LABEL_MAINTENANCE_TASKS', 'Maintenance Tasks');
define('UPKEEPIFY_LABEL_RESPONSES', 'Responses');
define('UPKEEPIFY_LABEL_RESPONSE', 'Response');

// Taxonomy Labels
define('UPKEEPIFY_LABEL_SERVICE_PROVIDERS', 'Service Providers');
define('UPKEEPIFY_LABEL_TASK_CATEGORIES', 'Task Categories');
define('UPKEEPIFY_LABEL_TASK_TYPES', 'Task Types');
define('UPKEEPIFY_LABEL_TASK_STATUSES', 'Task Statuses');

// Cache Groups
define('UPKEEPIFY_CACHE_GROUP', 'upkeepify');

// Form Field Names
define('UPKEEPIFY_FORM_FIELD_TASK_SUBMIT', 'upkeepify_task_submit');
define('UPKEEPIFY_FORM_FIELD_MATH', 'math');
define('UPKEEPIFY_FORM_FIELD_TASK_TITLE', 'task_title');
define('UPKEEPIFY_FORM_FIELD_TASK_DESCRIPTION', 'task_description');
define('UPKEEPIFY_FORM_FIELD_NEAREST_UNIT', 'nearest_unit');

// Session Keys
define('UPKEEPIFY_SESSION_MATH_RESULT', 'upkeepify_math_result');

// File Upload Limits
define('UPKEEPIFY_MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB

// Admin Actions
define('UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT', 'upkeepify_provider_response_submit');

// Menu Positions
define('UPKEEPIFY_MENU_SETTINGS_PAGE', 'upkeepify_settings');
define('UPKEEPIFY_MENU_SETUP_WIZARD_PAGE', 'upkeepify_setup_wizard');

// Cache Groups
define('UPKEEPIFY_CACHE_GROUP', 'upkeepify');
define('UPKEEPIFY_CACHE_GROUP_SETTINGS', 'upkeepify_settings');
define('UPKEEPIFY_CACHE_GROUP_TERMS', 'upkeepify_terms');
define('UPKEEPIFY_CACHE_GROUP_SHORTCODES', 'upkeepify_shortcodes');
define('UPKEEPIFY_CACHE_GROUP_QUERIES', 'upkeepify_queries');

// Cache Expiration Times (in seconds)
define('UPKEEPIFY_CACHE_EXPIRE_SHORT', 1800); // 30 minutes
define('UPKEEPIFY_CACHE_EXPIRE_MEDIUM', 3600); // 1 hour
define('UPKEEPIFY_CACHE_EXPIRE_LONG', 7200); // 2 hours
define('UPKEEPIFY_CACHE_EXPIRE_VERY_LONG', 21600); // 6 hours
