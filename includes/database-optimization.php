<?php
/**
 * Database Optimization Helper Functions
 *
 * Provides functions for creating recommended database indexes
 * to improve query performance for the Upkeepify plugin.
 *
 * @package Upkeepify
 */

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Create recommended database indexes for Upkeepify.
 *
 * Creates indexes on frequently queried meta keys to improve
 * query performance. Safe to run multiple times as it uses
 * CREATE INDEX IF NOT EXISTS syntax.
 *
 * @since 1.0
 * @return array Results of index creation operations
 */
function upkeepify_create_database_indexes() {
    global $wpdb;

    $results = array();
    $success_count = 0;
    $error_count = 0;

    try {
        // Index for nearest unit queries
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_nearest_unit
            ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(20))
            WHERE meta_key = 'upkeepify_nearest_unit'"
        );

        if ($result !== false) {
            $results['idx_postmeta_nearest_unit'] = 'Success';
            $success_count++;
        } else {
            $results['idx_postmeta_nearest_unit'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_postmeta_nearest_unit'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    try {
        // Index for rough estimate queries
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_rough_estimate
            ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
            WHERE meta_key = 'upkeepify_rough_estimate'"
        );

        if ($result !== false) {
            $results['idx_postmeta_rough_estimate'] = 'Success';
            $success_count++;
        } else {
            $results['idx_postmeta_rough_estimate'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_postmeta_rough_estimate'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    try {
        // Index for assigned service provider queries
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_assigned_provider
            ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
            WHERE meta_key = 'assigned_service_provider'"
        );

        if ($result !== false) {
            $results['idx_postmeta_assigned_provider'] = 'Success';
            $success_count++;
        } else {
            $results['idx_postmeta_assigned_provider'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_postmeta_assigned_provider'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    try {
        // Index for GPS latitude queries
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_gps_latitude
            ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
            WHERE meta_key = 'upkeepify_gps_latitude'"
        );

        if ($result !== false) {
            $results['idx_postmeta_gps_latitude'] = 'Success';
            $success_count++;
        } else {
            $results['idx_postmeta_gps_latitude'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_postmeta_gps_latitude'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    try {
        // Index for GPS longitude queries
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_gps_longitude
            ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
            WHERE meta_key = 'upkeepify_gps_longitude'"
        );

        if ($result !== false) {
            $results['idx_postmeta_gps_longitude'] = 'Success';
            $success_count++;
        } else {
            $results['idx_postmeta_gps_longitude'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_postmeta_gps_longitude'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    try {
        // Composite index for posts (post_type, post_status, post_date)
        $result = $wpdb->query(
            "CREATE INDEX IF NOT EXISTS idx_posts_maintenance_tasks
            ON {$wpdb->posts}(post_type(50), post_status, post_date DESC)"
        );

        if ($result !== false) {
            $results['idx_posts_maintenance_tasks'] = 'Success';
            $success_count++;
        } else {
            $results['idx_posts_maintenance_tasks'] = 'Failed: ' . $wpdb->last_error;
            $error_count++;
        }
    } catch (Exception $e) {
        $results['idx_posts_maintenance_tasks'] = 'Exception: ' . $e->getMessage();
        $error_count++;
    }

    // Log results
    $log_message = sprintf(
        'Upkeepify Database Index Creation: %d successful, %d failed',
        $success_count,
        $error_count
    );

    if (WP_DEBUG) {
        error_log($log_message);
        error_log('Index Results: ' . print_r($results, true));
    }

    // Add notification to admin
    if ($success_count > 0) {
        upkeepify_add_notification(
            sprintf(
                'Database indexes created successfully. %d indexes created, %d failed. See logs for details.',
                $success_count,
                $error_count
            ),
            'success',
            array('results' => $results)
        );
    }

    return $results;
}

/**
 * Check if recommended indexes exist.
 *
 * Returns a list of which recommended indexes are present
 * and which are missing.
 *
 * @since 1.0
 * @return array Array of index status information
 */
function upkeepify_check_database_indexes() {
    global $wpdb;

    $recommended_indexes = array(
        'idx_postmeta_nearest_unit' => 'postmeta',
        'idx_postmeta_rough_estimate' => 'postmeta',
        'idx_postmeta_assigned_provider' => 'postmeta',
        'idx_postmeta_gps_latitude' => 'postmeta',
        'idx_postmeta_gps_longitude' => 'postmeta',
        'idx_posts_maintenance_tasks' => 'posts',
    );

    $status = array();

    foreach ($recommended_indexes as $index_name => $table) {
        // Check if index exists
        $query = $wpdb->prepare(
            "SHOW INDEX FROM {$wpdb->$table} WHERE Key_name = %s",
            $index_name
        );

        $result = $wpdb->get_row($query);

        $status[$index_name] = array(
            'table' => $table,
            'exists' => !empty($result),
            'created_at' => !empty($result) ? current_time('mysql') : null,
        );
    }

    return $status;
}

/**
 * Get database performance statistics.
 *
 * Returns metrics about database size, query performance,
 * and cache effectiveness.
 *
 * @since 1.0
 * @return array Database performance metrics
 */
function upkeepify_get_database_stats() {
    global $wpdb;

    $stats = array();

    // Get table sizes
    $tables = array(
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->term_relationships,
    );

    foreach ($tables as $table) {
        $row = $wpdb->get_row(
            "SELECT
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = '{$table}'"
        );

        $stats[$table] = array(
            'size_mb' => $row ? $row->size_mb : 0,
            'rows' => $row ? $row->table_rows : 0,
        );
    }

    // Get index count
    $index_count = $wpdb->get_var(
        "SELECT COUNT(DISTINCT key_name)
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
        AND (table_name = '{$wpdb->posts}' OR table_name = '{$wpdb->postmeta}')"
    );

    $stats['total_indexes'] = $index_count;

    // Get maintenance tasks count
    $tasks_count = wp_count_posts(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
    $stats['maintenance_tasks_count'] = $tasks_count->publish;

    return $stats;
}

/**
 * Optimize database tables.
 *
 * Runs OPTIMIZE TABLE on Upkeepify-related tables
 * to reclaim space and improve performance.
 *
 * @since 1.0
 * @return array Results of optimization operations
 */
function upkeepify_optimize_tables() {
    global $wpdb;

    $results = array();
    $tables = array(
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->term_relationships,
        $wpdb->termmeta,
    );

    foreach ($tables as $table) {
        $start_time = microtime(true);
        $result = $wpdb->query("OPTIMIZE TABLE $table");
        $end_time = microtime(true);

        $results[$table] = array(
            'success' => $result !== false,
            'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
            'error' => $result === false ? $wpdb->last_error : null,
        );
    }

    // Log results
    if (WP_DEBUG) {
        error_log('Upkeepify Table Optimization Results: ' . print_r($results, true));
    }

    return $results;
}

// Register activation hook for automatic index creation
register_activation_hook(__FILE__, 'upkeepify_create_database_indexes');

// Schedule monthly table optimization
if (!wp_next_scheduled('upkeepify_monthly_table_optimization')) {
    wp_schedule_event(time(), 'monthly', 'upkeepify_monthly_table_optimization');
}
add_action('upkeepify_monthly_table_optimization', 'upkeepify_optimize_tables');
