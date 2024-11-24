<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function insert_upkeepify_sample_data() {
    // Insert Sample Categories
    $categories = ['General Maintenance', 'Electrical', 'Plumbing', 'Landscaping'];
    foreach ($categories as $category) {
        if (!term_exists($category, 'task_category')) {
            wp_insert_term($category, 'task_category');
        }
    }

    // Insert Sample Types
    $types = ['Repair', 'Inspection', 'Installation'];
    foreach ($types as $type) {
        if (!term_exists($type, 'task_type')) {
            wp_insert_term($type, 'task_type');
        }
    }

    // Insert Sample Statuses
    $statuses = ['Open', 'In Progress', 'Completed', 'On Hold'];
    foreach ($statuses as $status) {
        if (!term_exists($status, 'task_status')) {
            wp_insert_term($status, 'task_status');
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
        $provider_exists = get_page_by_title($provider['name'], OBJECT, 'service_provider');
        if (!$provider_exists) {
            wp_insert_post([
                'post_title'    => $provider['name'],
                'post_content'  => $provider['description'],
                'post_status'   => 'publish',
                'post_author'   => get_current_user_id(),
                'post_type'     => 'service_provider',
            ]);
        }
    }
}
