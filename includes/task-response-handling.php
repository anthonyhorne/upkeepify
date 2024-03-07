<?php
//Function hooked into save to create response posts per task for each
//eligible service provider. 
function upkeepify_generate_provider_tokens($post_id, $post, $update) {
    // Check if this is a 'maintenance_tasks' post type
    if ($post->post_type !== 'maintenance_tasks') {
        return;
    }

    // Check if the post is being published for the first time
    if (!$update) {
        // Fetch all service providers capable of performing the task
        $service_providers = get_terms(['taxonomy' => 'service_provider', 'hide_empty' => false]);

        foreach ($service_providers as $provider) {
            // Generate a unique token for each provider
            $token = wp_generate_password(20, false);

            // Prepare post data for the provider's response post
            $provider_response_data = [
                'post_title'   => 'Response for Task #' . $post_id . ' - Provider: ' . $provider->name,
                'post_status'  => 'draft', // Start as a draft
                'post_type'    => 'provider_responses', // Assuming 'provider_responses' is your custom post type for storing responses
                'meta_input'   => [
                    'response_task_id' => $post_id, // ID of the maintenance task
                    'provider_id' => $provider->term_id, // ID of the service provider
                    'response_token' => $token, // Unique token for the provider to edit this response
                ],
            ];

            // Insert the provider's response post
            wp_insert_post($provider_response_data);
        }
    }
}

add_action('save_post', 'upkeepify_generate_provider_tokens', 10, 3);
