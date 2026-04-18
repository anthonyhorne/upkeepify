<?php
/**
 * Adjust admin view based on user capabilities.
 *
 * Shows all post statuses to admin users but only published
 * posts to non-admin users in the admin area.
 *
 * @since 1.0
 * @param WP_Query $query The WP_Query instance (passed by reference).
 * @uses is_admin()
 * @uses WP_Query::is_main_query()
 * @uses WP_Query::set()
 * @uses get_current_screen()
 * @hook pre_get_posts
 */
function upkeepify_adjust_admin_view($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    // Check if we're on the correct post type in admin
    if ($screen && $screen->post_type === UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        // Add query optimizations for admin queries
        $query->set('no_found_rows', false); // Keep found_rows for pagination in admin
        $query->set('posts_per_page', 20); // Reasonable limit for admin listings

        // Admin users see all statuses including pending
        $query->set('post_status', ['publish', 'pending', 'draft']);

        // Filter the task list by lifecycle status terms when selected.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter.
        $lifecycle_status = isset($_GET['upkeepify_lifecycle_status']) ? sanitize_key(wp_unslash($_GET['upkeepify_lifecycle_status'])) : '';
        if ($lifecycle_status && function_exists('upkeepify_get_lifecycle_status_options')) {
            $status_options = upkeepify_get_lifecycle_status_options();
            if (isset($status_options[$lifecycle_status])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
                        'field'    => 'slug',
                        'terms'    => $lifecycle_status,
                    ),
                ));
            }
        }
    } else if ($screen && $screen->post_type === UPKEEPIFY_POST_TYPE_RESPONSES) {
        // Optimize responses list queries
        $query->set('no_found_rows', false);
        $query->set('posts_per_page', 20);
    } else if ($screen && $screen->post_type === UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES) {
        // Optimize provider responses list queries
        $query->set('no_found_rows', false);
        $query->set('posts_per_page', 20);
    }
}
add_action('pre_get_posts', 'upkeepify_adjust_admin_view');

/**
 * Add lifecycle status columns to maintenance task admin lists.
 *
 * @since 1.1
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string> Updated columns.
 */
function upkeepify_add_lifecycle_admin_columns($columns) {
    $updated = array();

    foreach ($columns as $key => $label) {
        $updated[$key] = $label;
        if ($key === 'title') {
            $updated['upkeepify_lifecycle_status'] = __('Lifecycle', 'upkeepify');
        }
    }

    if (!isset($updated['upkeepify_lifecycle_status'])) {
        $updated['upkeepify_lifecycle_status'] = __('Lifecycle', 'upkeepify');
    }

    return $updated;
}
add_filter('manage_' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS . '_posts_columns', 'upkeepify_add_lifecycle_admin_columns');

/**
 * Render lifecycle status column values.
 *
 * @since 1.1
 * @param string $column  Column key.
 * @param int    $post_id Maintenance task post ID.
 * @return void
 */
function upkeepify_render_lifecycle_admin_column($column, $post_id) {
    if ($column !== 'upkeepify_lifecycle_status') {
        return;
    }

    $status = function_exists('upkeepify_get_task_lifecycle_status_name')
        ? upkeepify_get_task_lifecycle_status_name($post_id)
        : '';

    echo $status ? esc_html($status) : '&mdash;';
}
add_action('manage_' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS . '_posts_custom_column', 'upkeepify_render_lifecycle_admin_column', 10, 2);

/**
 * Render lifecycle status filter for maintenance task admin lists.
 *
 * @since 1.1
 * @param string $post_type Current post type.
 * @return void
 */
function upkeepify_render_lifecycle_status_filter($post_type) {
    if ($post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS || !function_exists('upkeepify_get_lifecycle_status_options')) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter.
    $selected = isset($_GET['upkeepify_lifecycle_status']) ? sanitize_key(wp_unslash($_GET['upkeepify_lifecycle_status'])) : '';

    echo '<label class="screen-reader-text" for="upkeepify_lifecycle_status">' . esc_html__('Filter by lifecycle status', 'upkeepify') . '</label>';
    echo '<select name="upkeepify_lifecycle_status" id="upkeepify_lifecycle_status">';
    echo '<option value="">' . esc_html__('All lifecycle statuses', 'upkeepify') . '</option>';
    foreach (upkeepify_get_lifecycle_status_options() as $slug => $label) {
        echo '<option value="' . esc_attr($slug) . '"' . ($selected === $slug ? ' selected="selected"' : '') . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'upkeepify_render_lifecycle_status_filter');

// More PHP code or closing PHP tag if it's the end
