<?php
// Other plugin functions or code

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
