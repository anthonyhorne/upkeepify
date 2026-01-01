<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Insert sample data for the plugin.
 *
 * Creates default taxonomies (categories, types, statuses) and
 * sample service providers to help users get started quickly.
 *
 * @since 1.0
 * @uses term_exists()
 * @uses wp_insert_term()
 * @uses get_page_by_title()
 * @uses wp_insert_post()
 * @uses get_current_user_id()
 */
function upkeepify_insert_sample_data() {
    // Insert Sample Categories
    $categories = ['General Maintenance', 'Electrical', 'Plumbing', 'Landscaping'];
    foreach ($categories as $category) {
        if (!term_exists($category, UPKEEPIFY_TAXONOMY_TASK_CATEGORY)) {
            wp_insert_term($category, UPKEEPIFY_TAXONOMY_TASK_CATEGORY);
        }
    }

    // Insert Sample Types
    $types = ['Repair', 'Inspection', 'Installation'];
    foreach ($types as $type) {
        if (!term_exists($type, UPKEEPIFY_TAXONOMY_TASK_TYPE)) {
            wp_insert_term($type, UPKEEPIFY_TAXONOMY_TASK_TYPE);
        }
    }

    // Insert Sample Statuses
    $statuses = ['Open', 'In Progress', 'Completed', 'On Hold'];
    foreach ($statuses as $status) {
        if (!term_exists($status, UPKEEPIFY_TAXONOMY_TASK_STATUS)) {
            wp_insert_term($status, UPKEEPIFY_TAXONOMY_TASK_STATUS);
        }
    }

    // Insert Sample Providers
    $providers = [
        ['name' => 'Handyman Heroes', 'description' => 'Your local heroes for all things repair.'],
        ['name' => 'Plumb Perfect', 'description' => 'Precision plumbing services.'],
        ['name' => 'Bright Lights Electrical', 'description' => 'Electrical services with a smile.'],
        ['name' => 'Green Thumb Gardeners', 'description' => 'For all your landscaping needs.']
    ];

    foreach ($providers as $provider) {
        $provider_exists = get_page_by_title($provider['name'], OBJECT, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER);
        if (!$provider_exists) {
            wp_insert_post([
                'post_title'    => $provider['name'],
                'post_content'  => $provider['description'],
                'post_status'   => 'publish',
                'post_author'   => get_current_user_id(),
                'post_type'     => UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
            ]);
        }
    }
}

/**
 * Conditionally insert sample data.
 *
 * Checks if sample data has already been inserted and
 * inserts it only once on activation.
 *
 * @since 1.0
 * @uses get_option()
 * @uses update_option()
 * @uses upkeepify_insert_sample_data()
 * @hook admin_init
 */
function upkeepify_maybe_insert_sample_data() {
    if (!get_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED)) {
        upkeepify_insert_sample_data();
        update_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED, 1);
    }
}

add_action('admin_init', 'upkeepify_maybe_insert_sample_data');
