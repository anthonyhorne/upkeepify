<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Insert sample data for the plugin.
 *
 * Creates default taxonomies (categories, types, statuses) and
 * sample service providers to help users get started quickly.
 * All operations are idempotent and include comprehensive error handling.
 *
 * @since 1.0
 * @uses term_exists()
 * @uses wp_insert_term()
 * @uses get_current_user_id()
 * @uses error_log()
 * @return bool True if all sample data was inserted successfully, false otherwise.
 */
function upkeepify_insert_sample_data() {
    $success = true;
    $errors = array();

    try {
        // Insert Sample Categories
        $categories = ['General Maintenance', 'Electrical', 'Plumbing', 'Landscaping'];
        foreach ($categories as $category) {
            if (!term_exists($category, UPKEEPIFY_TAXONOMY_TASK_CATEGORY)) {
                $result = wp_insert_term($category, UPKEEPIFY_TAXONOMY_TASK_CATEGORY);

                if (is_wp_error($result)) {
                    $error_message = $result->get_error_message();
                    error_log('Upkeepify Sample Data Error: Failed to insert category "' . $category . '": ' . $error_message);
                    $errors[] = 'Category: ' . $error_message;
                    $success = false;
                } elseif (WP_DEBUG) {
                    error_log('Upkeepify Sample Data: Successfully inserted category: ' . $category);
                }
            }
        }

        // Insert Sample Types
        $types = ['Repair', 'Inspection', 'Installation'];
        foreach ($types as $type) {
            if (!term_exists($type, UPKEEPIFY_TAXONOMY_TASK_TYPE)) {
                $result = wp_insert_term($type, UPKEEPIFY_TAXONOMY_TASK_TYPE);

                if (is_wp_error($result)) {
                    $error_message = $result->get_error_message();
                    error_log('Upkeepify Sample Data Error: Failed to insert type "' . $type . '": ' . $error_message);
                    $errors[] = 'Type: ' . $error_message;
                    $success = false;
                } elseif (WP_DEBUG) {
                    error_log('Upkeepify Sample Data: Successfully inserted type: ' . $type);
                }
            }
        }

        // Insert Sample Statuses
        $statuses = ['Open', 'In Progress', 'Completed', 'On Hold'];
        foreach ($statuses as $status) {
            if (!term_exists($status, UPKEEPIFY_TAXONOMY_TASK_STATUS)) {
                $result = wp_insert_term($status, UPKEEPIFY_TAXONOMY_TASK_STATUS);

                if (is_wp_error($result)) {
                    $error_message = $result->get_error_message();
                    error_log('Upkeepify Sample Data Error: Failed to insert status "' . $status . '": ' . $error_message);
                    $errors[] = 'Status: ' . $error_message;
                    $success = false;
                } elseif (WP_DEBUG) {
                    error_log('Upkeepify Sample Data: Successfully inserted status: ' . $status);
                }
            }
        }

        // Insert Sample Providers (as terms in the service_provider taxonomy)
        $providers = [
            ['name' => 'Handyman Heroes', 'description' => 'Your local heroes for all things repair.'],
            ['name' => 'Plumb Perfect', 'description' => 'Precision plumbing services.'],
            ['name' => 'Bright Lights Electrical', 'description' => 'Electrical services with a smile.'],
            ['name' => 'Green Thumb Gardeners', 'description' => 'For all your landscaping needs.']
        ];

        foreach ($providers as $provider) {
            // Validate provider data
            if (empty($provider['name'])) {
                error_log('Upkeepify Sample Data Error: Provider name is empty');
                $errors[] = 'Provider: Name cannot be empty';
                $success = false;
                continue;
            }

            // Check if provider term already exists
            $term_exists = term_exists($provider['name'], UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER);

            if (!$term_exists) {
                // Insert provider as a term
                $result = wp_insert_term(
                    $provider['name'],
                    UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
                    array(
                        'description' => isset($provider['description']) ? sanitize_textarea_field($provider['description']) : ''
                    )
                );

                if (is_wp_error($result)) {
                    $error_message = $result->get_error_message();
                    error_log('Upkeepify Sample Data Error: Failed to insert provider "' . $provider['name'] . '": ' . $error_message);
                    $errors[] = 'Provider "' . $provider['name'] . '": ' . $error_message;
                    $success = false;
                } elseif (WP_DEBUG) {
                    error_log('Upkeepify Sample Data: Successfully inserted provider: ' . $provider['name']);
                }
            } elseif (WP_DEBUG) {
                error_log('Upkeepify Sample Data: Provider already exists: ' . $provider['name'] . ' (skipping)');
            }
        }

        // Log summary
        if (!empty($errors)) {
            error_log('Upkeepify Sample Data Warning: Some errors occurred during insertion: ' . implode('; ', $errors));
        }

        return $success;

    } catch (Exception $e) {
        error_log('Upkeepify Sample Data Exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Conditionally insert sample data.
 *
 * Checks if sample data has already been inserted and
 * inserts it only once on activation. Includes error handling
 * and status tracking.
 *
 * @since 1.0
 * @uses get_option()
 * @uses update_option()
 * @uses upkeepify_insert_sample_data()
 * @uses error_log()
 * @hook admin_init
 */
function upkeepify_maybe_insert_sample_data() {
    try {
        // Check if sample data has already been marked as inserted
        $sample_data_inserted = get_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED, false);

        if (!$sample_data_inserted) {
            if (WP_DEBUG) {
                error_log('Upkeepify Sample Data: Starting sample data insertion');
            }

            // Insert sample data
            $result = upkeepify_insert_sample_data();

            // Mark as inserted only if successful
            if ($result) {
                update_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED, time());
                if (WP_DEBUG) {
                    error_log('Upkeepify Sample Data: Successfully completed insertion');
                }
            } else {
                error_log('Upkeepify Sample Data Warning: Insertion completed with errors. Will retry on next admin_init.');
                // Don't mark as inserted if there were errors, so we can retry
            }
        } else {
            if (WP_DEBUG) {
                error_log('Upkeepify Sample Data: Sample data already inserted at ' . date('Y-m-d H:i:s', $sample_data_inserted));
            }
        }
    } catch (Exception $e) {
        error_log('Upkeepify Sample Data Exception in upkeepify_maybe_insert_sample_data: ' . $e->getMessage());
        // Don't mark as inserted on exception, allowing retry
    }
}

add_action('admin_init', 'upkeepify_maybe_insert_sample_data');
