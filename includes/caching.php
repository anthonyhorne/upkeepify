<?php
/**
 * Caching System Functions
 *
 * Provides caching mechanisms for settings, terms, and query results
 * to improve plugin performance.
 *
 * @package Upkeepify
 */

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Retrieve an option value from cache or database with transient support.
 *
 * Implements a multi-layer caching strategy: WordPress object cache
 * first, then transients for persistent caching across requests.
 * Falls back to database query if no cached data exists.
 *
 * @since 1.0
 * @param string $option_name The option name to retrieve.
 * @param mixed $default Optional default value if option doesn't exist.
 * @return mixed The option value or default.
 */
function upkeepify_get_setting_cached($option_name, $default = false) {
    $cache_key = 'upkeepify_setting_' . md5($option_name);

    // Try WordPress object cache first (fastest)
    $cached_value = wp_cache_get($cache_key, UPKEEPIFY_CACHE_GROUP_SETTINGS);

    if ($cached_value !== false) {
        return $cached_value;
    }

    // Try transient cache (persists across requests)
    $transient_key = UPKEEPIFY_CACHE_GROUP_SETTINGS . '_' . $cache_key;
    $transient_value = get_transient($transient_key);

    if ($transient_value !== false) {
        // Update object cache for next request
        wp_cache_set($cache_key, $transient_value, UPKEEPIFY_CACHE_GROUP_SETTINGS, UPKEEPIFY_CACHE_EXPIRE_MEDIUM);
        return $transient_value;
    }

    // Fetch from database
    $value = get_option($option_name, $default);

    // Cache the value
    wp_cache_set($cache_key, $value, UPKEEPIFY_CACHE_GROUP_SETTINGS, UPKEEPIFY_CACHE_EXPIRE_MEDIUM);
    set_transient($transient_key, $value, UPKEEPIFY_CACHE_EXPIRE_MEDIUM);

    if (WP_DEBUG) {
        error_log(sprintf('Upkeepify Cache MISS: %s', $option_name));
    }

    return $value;
}

/**
 * Update an option value and refresh all cache layers.
 *
 * Updates the database option and clears both object cache
 * and transient cache for that option to ensure consistency.
 *
 * @since 1.0
 * @param string $option_name The option name to update.
 * @param mixed $option_value The new option value.
 * @return bool True on success, false on failure.
 */
function upkeepify_update_setting_cached($option_name, $option_value) {
    $cache_key = 'upkeepify_setting_' . md5($option_name);
    $transient_key = UPKEEPIFY_CACHE_GROUP_SETTINGS . '_' . $cache_key;

    // Update database
    $result = update_option($option_name, $option_value);

    if ($result) {
        // Clear both cache layers
        wp_cache_delete($cache_key, UPKEEPIFY_CACHE_GROUP_SETTINGS);
        delete_transient($transient_key);

        if (WP_DEBUG) {
            error_log(sprintf('Upkeepify Cache CLEARED: %s', $option_name));
        }
    }

    return $result;
}

/**
 * Retrieve taxonomy terms from cache or database.
 *
 * Caches get_terms() results to reduce database queries for
 * frequently accessed taxonomy terms like service providers,
 * task categories, and statuses.
 *
 * @since 1.0
 * @param string|array $taxonomy Taxonomy name or array of names.
 * @param array $args Optional arguments for get_terms().
 * @param int $expiration Cache expiration time in seconds.
 * @return array|WP_Error Array of terms or WP_Error on failure.
 */
function upkeepify_get_terms_cached($taxonomy, $args = array(), $expiration = UPKEEPIFY_CACHE_EXPIRE_LONG) {
    // Generate unique cache key based on taxonomy and arguments
    $cache_key = 'upkeepify_terms_' . md5(serialize(array($taxonomy, $args)));

    // Try object cache first
    $cached_terms = wp_cache_get($cache_key, UPKEEPIFY_CACHE_GROUP_TERMS);

    if ($cached_terms !== false) {
        return $cached_terms;
    }

    // Try transient cache
    $transient_key = UPKEEPIFY_CACHE_GROUP_TERMS . '_' . $cache_key;
    $transient_value = get_transient($transient_key);

    if ($transient_value !== false) {
        wp_cache_set($cache_key, $transient_value, UPKEEPIFY_CACHE_GROUP_TERMS, $expiration);
        return $transient_value;
    }

    // Fetch from database
    $terms = get_terms($taxonomy, $args);

    if (!is_wp_error($terms) && !empty($terms)) {
        // Cache the terms
        wp_cache_set($cache_key, $terms, UPKEEPIFY_CACHE_GROUP_TERMS, $expiration);
        set_transient($transient_key, $terms, $expiration);

        if (WP_DEBUG) {
            error_log(sprintf('Upkeepify Terms Cache MISS: %s', is_array($taxonomy) ? implode(', ', $taxonomy) : $taxonomy));
        }
    }

    return $terms;
}

/**
 * Cache shortcode output based on attributes.
 *
 * Generates HTML output for shortcodes and caches it with a key
 * based on the shortcode name and attributes. Useful for expensive
 * queries or complex rendering logic.
 *
 * @since 1.0
 * @param string $shortcode_name Shortcode name.
 * @param array $atts Shortcode attributes.
 * @param callable $callback Function to generate output.
 * @param int $expiration Cache expiration time in seconds.
 * @return string The shortcode output.
 */
function upkeepify_get_shortcode_output_cached($shortcode_name, $atts, $callback, $expiration = UPKEEPIFY_CACHE_EXPIRE_VERY_LONG) {
    // Generate cache key based on shortcode and attributes
    $cache_key = 'upkeepify_shortcode_' . md5($shortcode_name . serialize($atts));

    // Try object cache
    $cached_output = wp_cache_get($cache_key, UPKEEPIFY_CACHE_GROUP_SHORTCODES);

    if ($cached_output !== false) {
        if (WP_DEBUG) {
            error_log(sprintf('Upkeepify Shortcode Cache HIT: %s', $shortcode_name));
        }
        return $cached_output;
    }

    // Try transient cache
    $transient_key = UPKEEPIFY_CACHE_GROUP_SHORTCODES . '_' . $cache_key;
    $transient_value = get_transient($transient_key);

    if ($transient_value !== false) {
        wp_cache_set($cache_key, $transient_value, UPKEEPIFY_CACHE_GROUP_SHORTCODES, $expiration);
        return $transient_value;
    }

    // Generate output
    $output = call_user_func($callback);

    // Cache the output
    if (!empty($output)) {
        wp_cache_set($cache_key, $output, UPKEEPIFY_CACHE_GROUP_SHORTCODES, $expiration);
        set_transient($transient_key, $output, $expiration);

        if (WP_DEBUG) {
            error_log(sprintf('Upkeepify Shortcode Cache MISS: %s', $shortcode_name));
        }
    }

    return $output;
}

/**
 * Invalidate all caches for a specific group.
 *
 * Clears all cached data for a given cache group. Useful for bulk
 * cache invalidation when data changes.
 *
 * @since 1.0
 * @param string $group Cache group to clear (settings, terms, shortcodes, queries).
 * @return int Number of caches cleared.
 */
function upkeepify_invalidate_cache_group($group) {
    global $wpdb;
    $count = 0;

    switch ($group) {
        case UPKEEPIFY_CACHE_GROUP_SETTINGS:
        case 'settings':
            $prefix = UPKEEPIFY_CACHE_GROUP_SETTINGS . '_';
            $transients = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    '_transient_' . $prefix . '%'
                )
            );
            foreach ($transients as $transient) {
                delete_transient(str_replace('_transient_', '', $transient));
                $count++;
            }
            wp_cache_flush_group(UPKEEPIFY_CACHE_GROUP_SETTINGS);
            break;

        case UPKEEPIFY_CACHE_GROUP_TERMS:
        case 'terms':
            $prefix = UPKEEPIFY_CACHE_GROUP_TERMS . '_';
            $transients = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    '_transient_' . $prefix . '%'
                )
            );
            foreach ($transients as $transient) {
                delete_transient(str_replace('_transient_', '', $transient));
                $count++;
            }
            wp_cache_flush_group(UPKEEPIFY_CACHE_GROUP_TERMS);
            break;

        case UPKEEPIFY_CACHE_GROUP_SHORTCODES:
        case 'shortcodes':
            $prefix = UPKEEPIFY_CACHE_GROUP_SHORTCODES . '_';
            $transients = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    '_transient_' . $prefix . '%'
                )
            );
            foreach ($transients as $transient) {
                delete_transient(str_replace('_transient_', '', $transient));
                $count++;
            }
            wp_cache_flush_group(UPKEEPIFY_CACHE_GROUP_SHORTCODES);
            break;

        case UPKEEPIFY_CACHE_GROUP_QUERIES:
        case 'queries':
            $prefix = UPKEEPIFY_CACHE_GROUP_QUERIES . '_';
            $transients = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    '_transient_' . $prefix . '%'
                )
            );
            foreach ($transients as $transient) {
                delete_transient(str_replace('_transient_', '', $transient));
                $count++;
            }
            wp_cache_flush_group(UPKEEPIFY_CACHE_GROUP_QUERIES);
            break;

        case 'all':
            // Clear all Upkeepify caches
            $count += upkeepify_invalidate_cache_group('settings');
            $count += upkeepify_invalidate_cache_group('terms');
            $count += upkeepify_invalidate_cache_group('shortcodes');
            $count += upkeepify_invalidate_cache_group('queries');
            break;
    }

    if (WP_DEBUG && $count > 0) {
        error_log(sprintf('Upkeepify Cache INVALIDATED: %s (%d items)', $group, $count));
    }

    return $count;
}

/**
 * Invalidate all Upkeepify caches.
 *
 * Clears all cached data across all cache groups.
 *
 * @since 1.0
 * @return int Total number of caches cleared.
 */
function upkeepify_invalidate_all_caches() {
    return upkeepify_invalidate_cache_group('all');
}

/**
 * Cache query results from WP_Query.
 *
 * Caches post query results to reduce database load for
 * frequently executed queries.
 *
 * @since 1.0
 * @param array $query_args WP_Query arguments.
 * @param int $expiration Cache expiration time in seconds.
 * @return array Array of post IDs or empty array.
 */
function upkeepify_get_posts_cached($query_args, $expiration = UPKEEPIFY_CACHE_EXPIRE_SHORT) {
    $cache_key = 'upkeepify_query_' . md5(serialize($query_args));

    // Try object cache
    $cached_posts = wp_cache_get($cache_key, UPKEEPIFY_CACHE_GROUP_QUERIES);

    if ($cached_posts !== false) {
        return $cached_posts;
    }

    // Try transient cache
    $transient_key = UPKEEPIFY_CACHE_GROUP_QUERIES . '_' . $cache_key;
    $transient_value = get_transient($transient_key);

    if ($transient_value !== false) {
        wp_cache_set($cache_key, $transient_value, UPKEEPIFY_CACHE_GROUP_QUERIES, $expiration);
        return $transient_value;
    }

    // Always request only IDs for caching
    $query_args['fields'] = 'ids';
    $query = new WP_Query($query_args);
    $post_ids = $query->posts;

    // Cache the post IDs
    if (!empty($post_ids)) {
        wp_cache_set($cache_key, $post_ids, UPKEEPIFY_CACHE_GROUP_QUERIES, $expiration);
        set_transient($transient_key, $post_ids, $expiration);

        if (WP_DEBUG) {
            error_log(sprintf('Upkeepify Query Cache MISS: %s posts', count($post_ids)));
        }
    }

    return $post_ids;
}

/**
 * Log query performance in debug mode.
 *
 * Records query execution times for performance monitoring
 * when WP_DEBUG is enabled.
 *
 * @since 1.0
 * @param string $query_name Description of the query.
 * @param float $start_time Query start time from microtime(true).
 */
function upkeepify_log_query_performance($query_name, $start_time) {
    if (WP_DEBUG) {
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds

        if ($execution_time > 100) {
            // Log queries that take more than 100ms
            error_log(sprintf('Upkeepify Slow Query: %s took %dms', $query_name, $execution_time));
        } else {
            error_log(sprintf('Upkeepify Query: %s took %dms', $query_name, $execution_time));
        }
    }
}

/**
 * Register cache invalidation hooks.
 *
 * Sets up automatic cache clearing when posts, terms, or options are updated.
 *
 * @since 1.0
 */
function upkeepify_register_cache_invalidation_hooks() {
    // Clear shortcode cache when maintenance tasks are updated
    add_action('save_post_' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, 'upkeepify_invalidate_shortcode_cache_on_post_update', 10, 2);

    // Clear terms cache when taxonomies are modified
    add_action('edited_term', 'upkeepify_invalidate_terms_cache_on_term_update', 10, 3);
    add_action('created_term', 'upkeepify_invalidate_terms_cache_on_term_update', 10, 3);
    add_action('delete_term', 'upkeepify_invalidate_terms_cache_on_term_update', 10, 3);

    // Clear settings cache when options are updated
    add_action('update_option_' . UPKEEPIFY_OPTION_SETTINGS, 'upkeepify_invalidate_settings_cache');
}
add_action('init', 'upkeepify_register_cache_invalidation_hooks');

/**
 * Invalidate shortcode cache when posts are updated.
 *
 * @since 1.0
 * @param int $post_id Post ID.
 * @param WP_Post $post Post object.
 */
function upkeepify_invalidate_shortcode_cache_on_post_update($post_id, $post) {
    if ($post->post_type === UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        // Clear all shortcode caches since tasks changed
        upkeepify_invalidate_cache_group('shortcodes');
        upkeepify_invalidate_cache_group('queries');
    }
}

/**
 * Invalidate terms cache when terms are updated.
 *
 * @since 1.0
 * @param int $term_id Term ID.
 * @param int $tt_id Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function upkeepify_invalidate_terms_cache_on_term_update($term_id, $tt_id, $taxonomy) {
    $upkeepify_taxonomies = array(
        UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        UPKEEPIFY_TAXONOMY_TASK_TYPE,
        UPKEEPIFY_TAXONOMY_TASK_STATUS,
        UPKEEPIFY_TAXONOMY_UNIT,
    );

    if (in_array($taxonomy, $upkeepify_taxonomies)) {
        upkeepify_invalidate_cache_group('terms');
        upkeepify_invalidate_cache_group('shortcodes');
    }
}

/**
 * Invalidate settings cache when settings are updated.
 *
 * @since 1.0
 * @param mixed $old_value Old settings value.
 * @param mixed $new_value New settings value.
 */
function upkeepify_invalidate_settings_cache($old_value, $new_value) {
    upkeepify_invalidate_cache_group('settings');
    upkeepify_invalidate_cache_group('shortcodes');
}
