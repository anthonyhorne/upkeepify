<?php
/**
 * Database Migrations, Backup/Restore, and Health Tools
 *
 * Upkeepify does not create custom database tables; it stores data in WordPress
 * core tables (posts, postmeta, terms, termmeta, options). "Schema" in this
 * plugin refers to:
 * - Custom post types and taxonomies being registered
 * - Option structures and defaults
 * - Supported meta keys and their formats
 *
 * @package Upkeepify
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Get default Upkeepify settings structure.
 *
 * @since 1.0
 * @return array
 */
function upkeepify_get_default_settings() {
    return array(
        UPKEEPIFY_SETTING_SMTP_OPTION => 0,
        UPKEEPIFY_SETTING_SMTP_HOST => '',
        UPKEEPIFY_SETTING_NOTIFY_OPTION => 1,
        UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK => 0,
        UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING => 0,
        UPKEEPIFY_SETTING_OVERRIDE_EMAIL => '',
        UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE => 0,
        UPKEEPIFY_SETTING_NUMBER_OF_UNITS => 10,
        UPKEEPIFY_SETTING_CURRENCY => '$',
        UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE => 0,
        UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL => '',
    );
}

/**
 * Get current stored Upkeepify schema version.
 *
 * @since 1.0
 * @return int
 */
function upkeepify_get_current_db_version() {
    $version = get_option(UPKEEPIFY_OPTION_DB_VERSION, null);

    if ($version === null) {
        return 1;
    }

    return max(0, intval($version));
}

/**
 * Update current stored Upkeepify schema version.
 *
 * @since 1.0
 * @param int $version
 * @return bool
 */
function upkeepify_set_current_db_version($version) {
    return update_option(UPKEEPIFY_OPTION_DB_VERSION, intval($version));
}

/**
 * Migration logger.
 *
 * Stores a short rolling log in the options table and logs to PHP error_log in
 * WP_DEBUG mode.
 *
 * @since 1.0
 * @param string $message
 * @param array $context
 * @return void
 */
function upkeepify_migration_log($message, $context = array()) {
    $line = '[' . current_time('mysql') . '] ' . $message;

    if (!empty($context)) {
        $line .= ' ' . wp_json_encode($context);
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Upkeepify Migration: ' . $line);
    }

    $log = get_option(UPKEEPIFY_OPTION_MIGRATION_LOG, array());
    if (!is_array($log)) {
        $log = array();
    }

    $log[] = $line;

    $max_lines = 200;
    if (count($log) > $max_lines) {
        $log = array_slice($log, -1 * $max_lines);
    }

    update_option(UPKEEPIFY_OPTION_MIGRATION_LOG, $log, false);
}

/**
 * Append migration history entry.
 *
 * @since 1.0
 * @param array $entry
 * @return void
 */
function upkeepify_append_migration_history($entry) {
    $history = get_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY, array());
    if (!is_array($history)) {
        $history = array();
    }

    $history[] = $entry;

    update_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY, $history, false);
}

/**
 * Ensure Upkeepify default terms exist.
 *
 * @since 1.0
 * @return void
 */
function upkeepify_ensure_default_terms() {
    $defaults = array(
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY => array('General Maintenance', 'Electrical', 'Plumbing', 'Landscaping'),
        UPKEEPIFY_TAXONOMY_TASK_TYPE => array('Repair', 'Inspection', 'Installation'),
        UPKEEPIFY_TAXONOMY_TASK_STATUS => array('Open', 'In Progress', 'Completed', 'On Hold'),
    );

    foreach ($defaults as $taxonomy => $terms) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        foreach ($terms as $term_name) {
            if (term_exists($term_name, $taxonomy)) {
                continue;
            }

            $result = wp_insert_term($term_name, $taxonomy);
            if (is_wp_error($result)) {
                upkeepify_migration_log('Failed creating default term', array(
                    'taxonomy' => $taxonomy,
                    'term' => $term_name,
                    'error' => $result->get_error_message(),
                ));
            }
        }
    }
}

/**
 * Ensure Upkeepify options exist with default structures.
 *
 * @since 1.0
 * @return void
 */
function upkeepify_ensure_default_options() {
    $settings = get_option(UPKEEPIFY_OPTION_SETTINGS, null);

    if ($settings === null || !is_array($settings)) {
        $settings = upkeepify_get_default_settings();
    } else {
        $settings = array_merge(upkeepify_get_default_settings(), $settings);
    }

    $validated = upkeepify_validate_settings($settings);
    if (!is_wp_error($validated)) {
        update_option(UPKEEPIFY_OPTION_SETTINGS, $validated, false);
    }

    if (get_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY, null) === null) {
        update_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY, array(), false);
    }

    if (get_option(UPKEEPIFY_OPTION_MIGRATION_LOG, null) === null) {
        update_option(UPKEEPIFY_OPTION_MIGRATION_LOG, array(), false);
    }

    if (get_option(UPKEEPIFY_OPTION_BACKUP_HISTORY, null) === null) {
        update_option(UPKEEPIFY_OPTION_BACKUP_HISTORY, array(), false);
    }

    if (get_option(UPKEEPIFY_OPTION_DB_VERSION, null) === null) {
        update_option(UPKEEPIFY_OPTION_DB_VERSION, 1, false);
    }
}

/**
 * Create/initialize the logical database schema.
 *
 * For WordPress-based plugins, this means:
 * - register CPTs and taxonomies (for rewrite rules and term inserts)
 * - create default options
 * - create default terms
 *
 * @since 1.0
 * @return void
 */
function upkeepify_setup_database() {
    if (function_exists('upkeepify_register_maintenance_tasks_post_type')) {
        upkeepify_register_maintenance_tasks_post_type();
    }

    if (function_exists('upkeepify_register_response_post_type')) {
        upkeepify_register_response_post_type();
    }

    if (function_exists('upkeepify_register_provider_response_post_type')) {
        upkeepify_register_provider_response_post_type();
    }

    if (function_exists('upkeepify_register_taxonomies')) {
        upkeepify_register_taxonomies();
    }

    upkeepify_ensure_default_options();
    upkeepify_ensure_default_terms();

    flush_rewrite_rules(false);
}

/**
 * Verify current schema state against expectations.
 *
 * @since 1.0
 * @return array{ok:bool,issues:array<int, string>}
 */
function upkeepify_verify_schema() {
    $issues = array();

    $required_post_types = array(
        UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        UPKEEPIFY_POST_TYPE_RESPONSES,
        UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
    );

    foreach ($required_post_types as $pt) {
        if (!post_type_exists($pt)) {
            $issues[] = 'Missing post type registration: ' . $pt;
        }
    }

    $required_taxonomies = array(
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        UPKEEPIFY_TAXONOMY_TASK_TYPE,
        UPKEEPIFY_TAXONOMY_TASK_STATUS,
        UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
        UPKEEPIFY_TAXONOMY_UNIT,
    );

    foreach ($required_taxonomies as $tax) {
        if (!taxonomy_exists($tax)) {
            $issues[] = 'Missing taxonomy registration: ' . $tax;
        }
    }

    $required_options = array(
        UPKEEPIFY_OPTION_SETTINGS,
        UPKEEPIFY_OPTION_DB_VERSION,
        UPKEEPIFY_OPTION_MIGRATION_HISTORY,
        UPKEEPIFY_OPTION_MIGRATION_LOG,
    );

    foreach ($required_options as $opt) {
        if (get_option($opt, null) === null) {
            $issues[] = 'Missing option: ' . $opt;
        }
    }

    return array(
        'ok' => empty($issues),
        'issues' => $issues,
    );
}

/**
 * Attempt to repair the schema.
 *
 * @since 1.0
 * @return array{ok:bool,issues:array<int,string>,repaired:bool}
 */
function upkeepify_repair_schema() {
    $before = upkeepify_verify_schema();

    upkeepify_setup_database();

    $after = upkeepify_verify_schema();

    return array(
        'ok' => $after['ok'],
        'issues' => $after['issues'],
        'repaired' => !$before['ok'] && $after['ok'],
    );
}

/**
 * Reset Upkeepify data.
 *
 * This deletes Upkeepify-related posts, term meta, terms, and options.
 *
 * @since 1.0
 * @return true|WP_Error
 */
function upkeepify_reset_database() {
    if (!current_user_can('manage_options')) {
        return new WP_Error('upkeepify_forbidden', 'Insufficient permissions.');
    }

    $post_types = array(
        UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
        UPKEEPIFY_POST_TYPE_RESPONSES,
        UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
    );

    foreach ($post_types as $post_type) {
        $query = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => 200,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        while (!empty($query->posts)) {
            foreach ($query->posts as $post_id) {
                wp_delete_post($post_id, true);
            }

            $query = new WP_Query(array(
                'post_type' => $post_type,
                'post_status' => 'any',
                'posts_per_page' => 200,
                'fields' => 'ids',
                'no_found_rows' => true,
            ));
        }
    }

    $taxonomies = array(
        UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        UPKEEPIFY_TAXONOMY_TASK_TYPE,
        UPKEEPIFY_TAXONOMY_TASK_STATUS,
        UPKEEPIFY_TAXONOMY_UNIT,
    );

    foreach ($taxonomies as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids',
            'number' => 0,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }

    delete_option(UPKEEPIFY_OPTION_SETTINGS);
    delete_option(UPKEEPIFY_OPTION_NOTIFICATIONS);
    delete_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED);

    delete_option(UPKEEPIFY_OPTION_DB_VERSION);
    delete_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY);
    delete_option(UPKEEPIFY_OPTION_MIGRATION_LOG);
    delete_option(UPKEEPIFY_OPTION_BACKUP_HISTORY);

    upkeepify_migration_log('Database reset completed');

    return true;
}

/**
 * Run pending migrations.
 *
 * @since 1.0
 * @param int|null $target_version
 * @return true|WP_Error
 */
function upkeepify_run_migrations($target_version = null) {
    $target_version = $target_version === null ? UPKEEPIFY_DB_VERSION : intval($target_version);
    $current = upkeepify_get_current_db_version();

    if ($current === $target_version) {
        return true;
    }

    if ($current > $target_version) {
        return new WP_Error('upkeepify_migration_direction', 'Current DB version is newer than plugin DB version.');
    }

    for ($version = $current; $version < $target_version; $version++) {
        $from = $version;
        $to = $version + 1;
        $fn = 'upkeepify_migrate_v' . $from . '_to_v' . $to;

        if (!function_exists($fn)) {
            return new WP_Error('upkeepify_missing_migration', 'Missing migration function: ' . $fn);
        }

        upkeepify_migration_log('Starting migration', array('from' => $from, 'to' => $to));

        $result = call_user_func($fn);

        if (is_wp_error($result)) {
            upkeepify_append_migration_history(array(
                'timestamp' => time(),
                'from' => $from,
                'to' => $to,
                'status' => 'failed',
                'message' => $result->get_error_message(),
            ));

            upkeepify_migration_log('Migration failed', array(
                'from' => $from,
                'to' => $to,
                'error' => $result->get_error_message(),
            ));

            return $result;
        }

        upkeepify_set_current_db_version($to);

        upkeepify_append_migration_history(array(
            'timestamp' => time(),
            'from' => $from,
            'to' => $to,
            'status' => 'success',
            'message' => 'OK',
        ));

        upkeepify_migration_log('Migration complete', array('from' => $from, 'to' => $to));
    }

    return true;
}

/**
 * Roll back the most recent migration step (if supported).
 *
 * @since 1.0
 * @return true|WP_Error
 */
function upkeepify_rollback_last_migration() {
    $current = upkeepify_get_current_db_version();

    if ($current <= 1) {
        return new WP_Error('upkeepify_no_rollback', 'No migrations to roll back.');
    }

    $from = $current;
    $to = $current - 1;
    $fn = 'upkeepify_rollback_v' . $from . '_to_v' . $to;

    if (!function_exists($fn)) {
        return new WP_Error('upkeepify_missing_rollback', 'No rollback available for v' . $from . ' -> v' . $to);
    }

    upkeepify_migration_log('Starting rollback', array('from' => $from, 'to' => $to));

    $result = call_user_func($fn);

    if (is_wp_error($result)) {
        upkeepify_migration_log('Rollback failed', array(
            'from' => $from,
            'to' => $to,
            'error' => $result->get_error_message(),
        ));

        return $result;
    }

    upkeepify_set_current_db_version($to);

    upkeepify_append_migration_history(array(
        'timestamp' => time(),
        'from' => $from,
        'to' => $to,
        'status' => 'rolled_back',
        'message' => 'OK',
    ));

    upkeepify_migration_log('Rollback complete', array('from' => $from, 'to' => $to));

    return true;
}

/**
 * Migration v1 -> v2
 *
 * Adds schema version tracking, migration history/log options, ensures defaults.
 *
 * @since 1.0
 * @return true|WP_Error
 */
function upkeepify_migrate_v1_to_v2() {
    upkeepify_ensure_default_options();
    upkeepify_ensure_default_terms();

    return true;
}

/**
 * Rollback v2 -> v1
 *
 * This rollback is intentionally minimal and does not delete user content.
 * It only sets the version number back.
 *
 * @since 1.0
 * @return true
 */
function upkeepify_rollback_v2_to_v1() {
    return true;
}

/**
 * Export settings as JSON.
 *
 * @since 1.0
 * @return string JSON
 */
function upkeepify_export_settings() {
    $settings = get_option(UPKEEPIFY_OPTION_SETTINGS, array());
    $payload = array(
        'exported_at' => current_time('mysql'),
        'plugin' => 'upkeepify',
        'schema_version' => upkeepify_get_current_db_version(),
        'settings' => $settings,
    );

    return wp_json_encode($payload, JSON_PRETTY_PRINT);
}

/**
 * Export all Upkeepify data as JSON.
 *
 * Includes:
 * - tasks
 * - provider terms + term meta
 * - all relevant taxonomies
 * - settings
 *
 * @since 1.0
 * @return string JSON
 */
function upkeepify_export_all_data() {
    $registry = upkeepify_get_meta_field_registry();

    $tasks = array();

    $page = 1;
    do {
        $task_query = new WP_Query(array(
            'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
            'post_status' => 'any',
            'posts_per_page' => 200,
            'paged' => $page,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        if (!empty($task_query->posts)) {
            foreach ($task_query->posts as $task_id) {
                $meta = array();
                foreach ($registry as $meta_key => $def) {
                    $meta[$meta_key] = get_post_meta($task_id, $meta_key, true);
                }

                $tax_data = array();
                $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($task_id, $taxonomy, array('fields' => 'slugs'));
                    $tax_data[$taxonomy] = is_wp_error($terms) ? array() : $terms;
                }

                $post = get_post($task_id);

                $tasks[] = array(
                    'ID' => $task_id,
                    'post_title' => $post ? $post->post_title : '',
                    'post_content' => $post ? $post->post_content : '',
                    'post_status' => $post ? $post->post_status : '',
                    'post_date_gmt' => $post ? $post->post_date_gmt : '',
                    'meta' => $meta,
                    'taxonomies' => $tax_data,
                );
            }
        }

        $page++;
    } while (!empty($task_query->posts));

    $taxonomies_to_export = array(
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        UPKEEPIFY_TAXONOMY_TASK_TYPE,
        UPKEEPIFY_TAXONOMY_TASK_STATUS,
        UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
        UPKEEPIFY_TAXONOMY_UNIT,
    );

    $terms_data = array();

    foreach ($taxonomies_to_export as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            $terms_data[$taxonomy] = array();
            continue;
        }

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            $terms_data[$taxonomy] = array();
            continue;
        }

        $terms_data[$taxonomy] = array();
        foreach ($terms as $term) {
            $term_entry = array(
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent,
            );

            if ($taxonomy === UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER) {
                $term_entry['meta'] = array(
                    UPKEEPIFY_TERM_META_PROVIDER_PHONE => get_term_meta($term->term_id, UPKEEPIFY_TERM_META_PROVIDER_PHONE, true),
                    UPKEEPIFY_TERM_META_PROVIDER_EMAIL => get_term_meta($term->term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, true),
                    UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES => get_term_meta($term->term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES, true),
                );
            }

            $terms_data[$taxonomy][] = $term_entry;
        }
    }

    $payload = array(
        'exported_at' => current_time('mysql'),
        'plugin' => 'upkeepify',
        'schema_version' => upkeepify_get_current_db_version(),
        'settings' => get_option(UPKEEPIFY_OPTION_SETTINGS, array()),
        'terms' => $terms_data,
        'tasks' => $tasks,
    );

    return wp_json_encode($payload, JSON_PRETTY_PRINT);
}

/**
 * Import all Upkeepify data from a JSON payload.
 *
 * @since 1.0
 * @param string $json
 * @return true|WP_Error
 */
function upkeepify_import_all_data($json) {
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return new WP_Error('upkeepify_invalid_import', 'Invalid JSON payload.');
    }

    if (isset($data['settings']) && is_array($data['settings'])) {
        $validated_settings = upkeepify_validate_settings($data['settings']);
        if (is_wp_error($validated_settings)) {
            return $validated_settings;
        }
        update_option(UPKEEPIFY_OPTION_SETTINGS, $validated_settings, false);
    }

    $taxonomy_term_map = array();

    if (isset($data['terms']) && is_array($data['terms'])) {
        foreach ($data['terms'] as $taxonomy => $terms) {
            if (!taxonomy_exists($taxonomy) || !is_array($terms)) {
                continue;
            }

            foreach ($terms as $term_entry) {
                if (!is_array($term_entry) || empty($term_entry['name'])) {
                    continue;
                }

                $slug = isset($term_entry['slug']) ? sanitize_title($term_entry['slug']) : '';
                $existing = $slug ? term_exists($slug, $taxonomy) : term_exists($term_entry['name'], $taxonomy);

                if ($existing && is_array($existing) && isset($existing['term_id'])) {
                    $term_id = intval($existing['term_id']);
                } else {
                    $insert = wp_insert_term(
                        sanitize_text_field($term_entry['name']),
                        $taxonomy,
                        array(
                            'slug' => $slug,
                            'description' => isset($term_entry['description']) ? sanitize_textarea_field($term_entry['description']) : '',
                            'parent' => isset($term_entry['parent']) ? intval($term_entry['parent']) : 0,
                        )
                    );

                    if (is_wp_error($insert)) {
                        upkeepify_migration_log('Import: failed term insert', array(
                            'taxonomy' => $taxonomy,
                            'term' => $term_entry,
                            'error' => $insert->get_error_message(),
                        ));
                        continue;
                    }

                    $term_id = intval($insert['term_id']);
                }

                $taxonomy_term_map[$taxonomy][$slug ?: sanitize_title($term_entry['name'])] = $term_id;

                if ($taxonomy === UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER && isset($term_entry['meta']) && is_array($term_entry['meta'])) {
                    $provider_validation = upkeepify_validate_provider(array(
                        'email' => isset($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_EMAIL]) ? $term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_EMAIL] : '',
                        'phone' => isset($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_PHONE]) ? $term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_PHONE] : '',
                        'associated_categories' => isset($term_entry['meta'][UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES]) ? $term_entry['meta'][UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES] : array(),
                    ));

                    if (!is_wp_error($provider_validation)) {
                        if (isset($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_PHONE])) {
                            update_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_PHONE, sanitize_text_field($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_PHONE]));
                        }

                        if (isset($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_EMAIL]) && is_email($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_EMAIL])) {
                            update_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, sanitize_email($term_entry['meta'][UPKEEPIFY_TERM_META_PROVIDER_EMAIL]));
                        }

                        if (isset($term_entry['meta'][UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES]) && is_array($term_entry['meta'][UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES])) {
                            update_term_meta($term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES, array_map('intval', $term_entry['meta'][UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES]));
                        }
                    }
                }
            }
        }
    }

    if (isset($data['tasks']) && is_array($data['tasks'])) {
        foreach ($data['tasks'] as $task_entry) {
            if (!is_array($task_entry)) {
                continue;
            }

            $task_data = array(
                'post_title' => isset($task_entry['post_title']) ? sanitize_text_field($task_entry['post_title']) : '',
                'post_content' => isset($task_entry['post_content']) ? sanitize_textarea_field($task_entry['post_content']) : '',
                'meta' => isset($task_entry['meta']) && is_array($task_entry['meta']) ? $task_entry['meta'] : array(),
            );

            $validated_task = upkeepify_validate_maintenance_task($task_data);
            if (is_wp_error($validated_task)) {
                upkeepify_migration_log('Import: task validation failed', array('error' => $validated_task->get_error_message()));
                continue;
            }

            $task_id = wp_insert_post(array(
                'post_title' => $task_data['post_title'],
                'post_content' => $task_data['post_content'],
                'post_status' => isset($task_entry['post_status']) ? sanitize_key($task_entry['post_status']) : 'publish',
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
            ), true);

            if (is_wp_error($task_id)) {
                upkeepify_migration_log('Import: failed inserting task', array('error' => $task_id->get_error_message()));
                continue;
            }

            if (!empty($task_entry['meta']) && is_array($task_entry['meta'])) {
                foreach ($task_entry['meta'] as $meta_key => $meta_value) {
                    if (upkeepify_get_meta_field_definition($meta_key) === null) {
                        continue;
                    }

                    $meta_validation = upkeepify_validate_task_meta($meta_key, $meta_value);
                    if (is_wp_error($meta_validation)) {
                        continue;
                    }

                    update_post_meta($task_id, $meta_key, $meta_value);
                }
            }

            if (isset($task_entry['taxonomies']) && is_array($task_entry['taxonomies'])) {
                foreach ($task_entry['taxonomies'] as $taxonomy => $term_slugs) {
                    if (!taxonomy_exists($taxonomy) || !is_array($term_slugs)) {
                        continue;
                    }

                    $term_ids = array();
                    foreach ($term_slugs as $term_slug) {
                        $term_slug = sanitize_title($term_slug);
                        $term = term_exists($term_slug, $taxonomy);
                        if ($term && is_array($term) && isset($term['term_id'])) {
                            $term_ids[] = intval($term['term_id']);
                        }
                    }

                    if (!empty($term_ids)) {
                        wp_set_object_terms($task_id, $term_ids, $taxonomy);
                    }
                }
            }
        }
    }

    upkeepify_migration_log('Import completed');

    return true;
}

/**
 * Create a timestamped backup file in uploads.
 *
 * @since 1.0
 * @return array{file:string,url:string}|WP_Error
 */
function upkeepify_backup_database() {
    $uploads = wp_upload_dir();

    if (empty($uploads['basedir']) || empty($uploads['baseurl'])) {
        return new WP_Error('upkeepify_backup_failed', 'Unable to determine uploads directory.');
    }

    $dir = trailingslashit($uploads['basedir']) . 'upkeepify-backups';
    if (!wp_mkdir_p($dir)) {
        return new WP_Error('upkeepify_backup_failed', 'Unable to create backup directory.');
    }

    $timestamp = gmdate('Ymd-His');
    $filename = 'upkeepify-backup-' . $timestamp . '.json';
    $path = trailingslashit($dir) . $filename;

    $json = upkeepify_export_all_data();
    $written = file_put_contents($path, $json);

    if ($written === false) {
        return new WP_Error('upkeepify_backup_failed', 'Unable to write backup file.');
    }

    $url = trailingslashit($uploads['baseurl']) . 'upkeepify-backups/' . $filename;

    $history = get_option(UPKEEPIFY_OPTION_BACKUP_HISTORY, array());
    if (!is_array($history)) {
        $history = array();
    }

    $history[] = array(
        'created_at' => current_time('mysql'),
        'file' => $path,
        'url' => $url,
        'size' => filesize($path),
    );

    if (count($history) > 50) {
        $history = array_slice($history, -50);
    }

    update_option(UPKEEPIFY_OPTION_BACKUP_HISTORY, $history, false);

    return array('file' => $path, 'url' => $url);
}

/**
 * Add admin notice when migrations are pending.
 *
 * @since 1.0
 * @return void
 */
function upkeepify_admin_notice_pending_migrations() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $current = upkeepify_get_current_db_version();
    if ($current >= UPKEEPIFY_DB_VERSION) {
        return;
    }

    $url = wp_nonce_url(
        admin_url('admin-post.php?action=' . UPKEEPIFY_ADMIN_ACTION_RUN_MIGRATIONS),
        UPKEEPIFY_NONCE_ACTION_RUN_MIGRATIONS,
        UPKEEPIFY_NONCE_RUN_MIGRATIONS
    );

    echo '<div class="notice notice-warning"><p>';
    echo esc_html(sprintf('Upkeepify database schema is out of date (current: v%d, required: v%d).', $current, UPKEEPIFY_DB_VERSION));
    echo ' ';
    echo '<a class="button button-primary" href="' . esc_url($url) . '">Run Migrations</a>';
    echo '</p></div>';
}
add_action('admin_notices', 'upkeepify_admin_notice_pending_migrations');

/**
 * Add the Database Health submenu.
 *
 * @since 1.0
 */
function upkeepify_add_db_health_menu() {
    add_submenu_page(
        'edit.php?post_type=' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'Upkeepify Database Health',
        'Database Health',
        'manage_options',
        UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'upkeepify_db_health_page'
    );
}
add_action('admin_menu', 'upkeepify_add_db_health_menu');

/**
 * Get meta usage statistics for maintenance tasks.
 *
 * @since 1.0
 * @return array<string,int>
 */
function upkeepify_get_meta_usage_stats() {
    global $wpdb;

    $stats = array();
    $registry = upkeepify_get_meta_field_registry();

    foreach ($registry as $meta_key => $def) {
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type = %s
                AND pm.meta_key = %s",
                UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                $meta_key
            )
        );

        $stats[$meta_key] = intval($count);
    }

    return $stats;
}

/**
 * Find orphaned meta keys on maintenance tasks (keys not in registry).
 *
 * @since 1.0
 * @return array<int, string>
 */
function upkeepify_find_orphaned_task_meta_keys() {
    global $wpdb;

    $registry = upkeepify_get_meta_field_registry();
    $known_keys = array_keys($registry);

    $rows = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s",
            UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS
        )
    );

    if (!is_array($rows)) {
        return array();
    }

    $orphans = array();
    foreach ($rows as $meta_key) {
        if (in_array($meta_key, $known_keys, true)) {
            continue;
        }

        if (strpos($meta_key, '_') === 0) {
            continue;
        }

        $orphans[] = $meta_key;
    }

    sort($orphans);

    return $orphans;
}

/**
 * Render database health page.
 *
 * @since 1.0
 */
function upkeepify_db_health_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $schema = upkeepify_verify_schema();
    $current_version = upkeepify_get_current_db_version();

    $maintenance_counts = wp_count_posts(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
    $responses_counts = wp_count_posts(UPKEEPIFY_POST_TYPE_RESPONSES);
    $provider_responses_counts = wp_count_posts(UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES);

    $taxonomies = array(
        UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        UPKEEPIFY_TAXONOMY_TASK_TYPE,
        UPKEEPIFY_TAXONOMY_TASK_STATUS,
        UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER,
        UPKEEPIFY_TAXONOMY_UNIT,
    );

    $taxonomy_counts = array();
    foreach ($taxonomies as $taxonomy) {
        $taxonomy_counts[$taxonomy] = taxonomy_exists($taxonomy)
            ? intval(wp_count_terms($taxonomy, array('hide_empty' => false)))
            : 0;
    }

    $meta_stats = upkeepify_get_meta_usage_stats();
    $orphaned_meta = upkeepify_find_orphaned_task_meta_keys();

    $run_migrations_url = wp_nonce_url(
        admin_url('admin-post.php?action=' . UPKEEPIFY_ADMIN_ACTION_RUN_MIGRATIONS),
        UPKEEPIFY_NONCE_ACTION_RUN_MIGRATIONS,
        UPKEEPIFY_NONCE_RUN_MIGRATIONS
    );

    $db_tools_nonce = wp_create_nonce(UPKEEPIFY_NONCE_ACTION_DB_TOOLS);

    ?>
    <div class="wrap">
        <h1>Upkeepify Database Health</h1>

        <h2>Schema Version</h2>
        <p>
            <strong>Stored:</strong> v<?php echo esc_html($current_version); ?>
            &nbsp;|&nbsp;
            <strong>Plugin:</strong> v<?php echo esc_html(UPKEEPIFY_DB_VERSION); ?>
        </p>

        <?php if ($current_version < UPKEEPIFY_DB_VERSION) : ?>
            <p>
                <a class="button button-primary" href="<?php echo esc_url($run_migrations_url); ?>">Run Migrations</a>
            </p>
        <?php endif; ?>

        <h2>Schema Verification</h2>
        <?php if ($schema['ok']) : ?>
            <p><span style="color: #0a7; font-weight: 600;">OK</span></p>
        <?php else : ?>
            <p><span style="color: #c00; font-weight: 600;">Issues detected</span></p>
            <ul>
                <?php foreach ($schema['issues'] as $issue) : ?>
                    <li><?php echo esc_html($issue); ?></li>
                <?php endforeach; ?>
            </ul>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(UPKEEPIFY_ADMIN_ACTION_REPAIR_SCHEMA); ?>">
                <input type="hidden" name="<?php echo esc_attr(UPKEEPIFY_NONCE_DB_TOOLS); ?>" value="<?php echo esc_attr($db_tools_nonce); ?>">
                <?php submit_button('Repair Schema', 'secondary'); ?>
            </form>
        <?php endif; ?>

        <h2>Counts</h2>
        <table class="widefat striped">
            <tbody>
                <tr><th>Maintenance Tasks</th><td><?php echo esc_html(isset($maintenance_counts->publish) ? $maintenance_counts->publish : 0); ?></td></tr>
                <tr><th>Responses</th><td><?php echo esc_html(isset($responses_counts->draft) ? $responses_counts->draft : 0); ?></td></tr>
                <tr><th>Provider Responses</th><td><?php echo esc_html(isset($provider_responses_counts->draft) ? $provider_responses_counts->draft : 0); ?></td></tr>
            </tbody>
        </table>

        <h2>Taxonomies</h2>
        <table class="widefat striped">
            <thead><tr><th>Taxonomy</th><th>Term Count</th></tr></thead>
            <tbody>
                <?php foreach ($taxonomy_counts as $taxonomy => $count) : ?>
                    <tr>
                        <td><?php echo esc_html($taxonomy); ?></td>
                        <td><?php echo esc_html($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Options</h2>
        <table class="widefat striped">
            <thead><tr><th>Option</th><th>Exists</th></tr></thead>
            <tbody>
                <?php
                $options = array(
                    UPKEEPIFY_OPTION_SETTINGS,
                    UPKEEPIFY_OPTION_NOTIFICATIONS,
                    UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED,
                    UPKEEPIFY_OPTION_DB_VERSION,
                    UPKEEPIFY_OPTION_MIGRATION_HISTORY,
                    UPKEEPIFY_OPTION_MIGRATION_LOG,
                    UPKEEPIFY_OPTION_BACKUP_HISTORY,
                );
                foreach ($options as $opt) :
                    $exists = get_option($opt, null) !== null;
                    ?>
                    <tr>
                        <td><?php echo esc_html($opt); ?></td>
                        <td><?php echo esc_html($exists ? 'Yes' : 'No'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Meta Field Usage</h2>
        <table class="widefat striped">
            <thead><tr><th>Meta Key</th><th>Posts With Value</th></tr></thead>
            <tbody>
                <?php foreach ($meta_stats as $meta_key => $count) : ?>
                    <tr>
                        <td><code><?php echo esc_html($meta_key); ?></code></td>
                        <td><?php echo esc_html($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Orphaned Meta Keys</h2>
        <?php if (empty($orphaned_meta)) : ?>
            <p>None detected.</p>
        <?php else : ?>
            <ul>
                <?php foreach ($orphaned_meta as $meta_key) : ?>
                    <li><code><?php echo esc_html($meta_key); ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Backup / Export / Import</h2>
        <p>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . UPKEEPIFY_ADMIN_ACTION_EXPORT_ALL_DATA), UPKEEPIFY_NONCE_ACTION_DB_TOOLS, UPKEEPIFY_NONCE_DB_TOOLS)); ?>">Export All Data (JSON)</a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . UPKEEPIFY_ADMIN_ACTION_EXPORT_SETTINGS), UPKEEPIFY_NONCE_ACTION_DB_TOOLS, UPKEEPIFY_NONCE_DB_TOOLS)); ?>">Export Settings (JSON)</a>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo esc_attr(UPKEEPIFY_ADMIN_ACTION_IMPORT_ALL_DATA); ?>">
            <input type="hidden" name="<?php echo esc_attr(UPKEEPIFY_NONCE_DB_TOOLS); ?>" value="<?php echo esc_attr($db_tools_nonce); ?>">
            <input type="file" name="upkeepify_import_file" accept="application/json">
            <?php submit_button('Import All Data', 'secondary'); ?>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(UPKEEPIFY_ADMIN_ACTION_BACKUP_DATABASE); ?>">
            <input type="hidden" name="<?php echo esc_attr(UPKEEPIFY_NONCE_DB_TOOLS); ?>" value="<?php echo esc_attr($db_tools_nonce); ?>">
            <?php submit_button('Create Backup File', 'secondary'); ?>
        </form>

        <h2>Danger Zone</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('This will permanently delete all Upkeepify data. Continue?');">
            <input type="hidden" name="action" value="<?php echo esc_attr(UPKEEPIFY_ADMIN_ACTION_RESET_DATABASE); ?>">
            <input type="hidden" name="<?php echo esc_attr(UPKEEPIFY_NONCE_DB_TOOLS); ?>" value="<?php echo esc_attr($db_tools_nonce); ?>">
            <?php submit_button('Reset Database (Delete All Plugin Data)', 'delete'); ?>
        </form>

        <h2>Migration Log</h2>
        <?php
        $log = get_option(UPKEEPIFY_OPTION_MIGRATION_LOG, array());
        if (!is_array($log) || empty($log)) {
            echo '<p>No migration log entries yet.</p>';
        } else {
            echo '<pre style="max-height: 300px; overflow: auto; background: #fff; border: 1px solid #ddd; padding: 12px;">' . esc_html(implode("\n", $log)) . '</pre>';
        }
        ?>

        <h2>Migration History</h2>
        <?php
        $history = get_option(UPKEEPIFY_OPTION_MIGRATION_HISTORY, array());
        if (!is_array($history) || empty($history)) {
            echo '<p>No migrations recorded yet.</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>Time</th><th>From</th><th>To</th><th>Status</th><th>Message</th></tr></thead><tbody>';
            foreach (array_reverse($history) as $entry) {
                echo '<tr>';
                echo '<td>' . esc_html(isset($entry['timestamp']) ? date('Y-m-d H:i:s', intval($entry['timestamp'])) : '') . '</td>';
                echo '<td>' . esc_html(isset($entry['from']) ? $entry['from'] : '') . '</td>';
                echo '<td>' . esc_html(isset($entry['to']) ? $entry['to'] : '') . '</td>';
                echo '<td>' . esc_html(isset($entry['status']) ? $entry['status'] : '') . '</td>';
                echo '<td>' . esc_html(isset($entry['message']) ? $entry['message'] : '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>

        <p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Attempt rollback of last migration step?');">
                <input type="hidden" name="action" value="<?php echo esc_attr(UPKEEPIFY_ADMIN_ACTION_ROLLBACK_LAST_MIGRATION); ?>">
                <input type="hidden" name="<?php echo esc_attr(UPKEEPIFY_NONCE_DB_TOOLS); ?>" value="<?php echo esc_attr($db_tools_nonce); ?>">
                <?php submit_button('Rollback Last Migration', 'secondary'); ?>
            </form>
        </p>

    </div>
    <?php
}

/**
 * Admin-post handler: run migrations.
 *
 * @since 1.0
 */
function upkeepify_admin_post_run_migrations() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_GET[UPKEEPIFY_NONCE_RUN_MIGRATIONS]) || !wp_verify_nonce($_GET[UPKEEPIFY_NONCE_RUN_MIGRATIONS], UPKEEPIFY_NONCE_ACTION_RUN_MIGRATIONS)) {
        wp_die('Invalid nonce');
    }

    upkeepify_setup_database();
    $result = upkeepify_run_migrations();

    $redirect = add_query_arg(
        array(
            'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
            'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
            'upkeepify_migrations' => is_wp_error($result) ? 'failed' : 'success',
        ),
        admin_url('edit.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_RUN_MIGRATIONS, 'upkeepify_admin_post_run_migrations');

/**
 * Admin-post handler: repair schema.
 *
 * @since 1.0
 */
function upkeepify_admin_post_repair_schema() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    upkeepify_repair_schema();

    wp_safe_redirect(add_query_arg(array(
        'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
    ), admin_url('edit.php')));
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_REPAIR_SCHEMA, 'upkeepify_admin_post_repair_schema');

/**
 * Admin-post handler: reset database.
 *
 * @since 1.0
 */
function upkeepify_admin_post_reset_database() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    $result = upkeepify_reset_database();

    $redirect = add_query_arg(array(
        'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'upkeepify_reset' => is_wp_error($result) ? 'failed' : 'success',
    ), admin_url('edit.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_RESET_DATABASE, 'upkeepify_admin_post_reset_database');

/**
 * Admin-post handler: backup database.
 *
 * @since 1.0
 */
function upkeepify_admin_post_backup_database() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    $result = upkeepify_backup_database();

    $redirect_args = array(
        'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'upkeepify_backup' => is_wp_error($result) ? 'failed' : 'success',
    );

    if (!is_wp_error($result) && isset($result['url'])) {
        $redirect_args['upkeepify_backup_url'] = rawurlencode($result['url']);
    }

    wp_safe_redirect(add_query_arg($redirect_args, admin_url('edit.php')));
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_BACKUP_DATABASE, 'upkeepify_admin_post_backup_database');

/**
 * Admin-post handler: export all data.
 *
 * @since 1.0
 */
function upkeepify_admin_post_export_all_data() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_GET[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_GET[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    $json = upkeepify_export_all_data();

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=upkeepify-export-all-' . gmdate('Ymd-His') . '.json');

    echo $json;
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_EXPORT_ALL_DATA, 'upkeepify_admin_post_export_all_data');

/**
 * Admin-post handler: export settings.
 *
 * @since 1.0
 */
function upkeepify_admin_post_export_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_GET[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_GET[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    $json = upkeepify_export_settings();

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=upkeepify-export-settings-' . gmdate('Ymd-His') . '.json');

    echo $json;
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_EXPORT_SETTINGS, 'upkeepify_admin_post_export_settings');

/**
 * Admin-post handler: import all data.
 *
 * @since 1.0
 */
function upkeepify_admin_post_import_all_data() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    if (!isset($_FILES['upkeepify_import_file']) || !isset($_FILES['upkeepify_import_file']['tmp_name'])) {
        wp_die('No file uploaded');
    }

    $contents = file_get_contents($_FILES['upkeepify_import_file']['tmp_name']);
    if ($contents === false) {
        wp_die('Unable to read uploaded file');
    }

    $result = upkeepify_import_all_data($contents);

    $redirect = add_query_arg(array(
        'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'upkeepify_import' => is_wp_error($result) ? 'failed' : 'success',
    ), admin_url('edit.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_IMPORT_ALL_DATA, 'upkeepify_admin_post_import_all_data');

/**
 * Admin-post handler: rollback last migration.
 *
 * @since 1.0
 */
function upkeepify_admin_post_rollback_last_migration() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_DB_TOOLS]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_DB_TOOLS], UPKEEPIFY_NONCE_ACTION_DB_TOOLS)) {
        wp_die('Invalid nonce');
    }

    $result = upkeepify_rollback_last_migration();

    $redirect = add_query_arg(array(
        'page' => UPKEEPIFY_MENU_DB_HEALTH_PAGE,
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'upkeepify_rollback' => is_wp_error($result) ? 'failed' : 'success',
    ), admin_url('edit.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_' . UPKEEPIFY_ADMIN_ACTION_ROLLBACK_LAST_MIGRATION, 'upkeepify_admin_post_rollback_last_migration');
