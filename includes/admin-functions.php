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

    // Check if the current user is an admin or has the capability to edit posts
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        // Check if we're on the correct post type in admin
        if ($screen && $screen->post_type === $post_type) {
        // Check if we're on the correct post type in admin
        if ($screen && $screen->post_type === $post_type) {
            // Admin users see all statuses including pending
            $query->set('post_status', ['publish', 'pending', 'draft']);
        }
    } else {
        // Non-admin users (in the admin area, for any potential cases) see only published posts
        $query->set('post_status', 'publish');
    }
}
add_action('pre_get_posts', 'upkeepify_adjust_admin_view');


// More PHP code or closing PHP tag if it's the end
