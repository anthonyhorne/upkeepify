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


// More PHP code or closing PHP tag if it's the end
