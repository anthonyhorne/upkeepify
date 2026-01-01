<?php
//Function hooked into save to create response posts per task for each
//eligible service provider. 
function upkeepify_generate_provider_tokens($post_id, $post, $update) {
    // Check if this is a UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS post type
    if ($post->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS) {
        return;
    }

    // Check if the post is being published for the first time
    if (!$update) {
        // Fetch all service providers capable of performing the task
        $service_providers = get_terms(['taxonomy' => UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER, 'hide_empty' => false]);

        foreach ($service_providers as $provider) {
            // Generate a unique token for each provider
            $token = wp_generate_password(20, false);

            // Prepare post data for the provider's response post
            $provider_response_data = [
                'post_title'   => 'Response for Task #' . $post_id . ' - Provider: ' . $provider->name,
                'post_status'  => 'draft', // Start as a draft
                'post_type'    => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES, // Assuming UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES is your custom post type for storing responses
                'meta_input'   => [
                    UPKEEPIFY_META_KEY_RESPONSE_TASK_ID => $post_id, // ID of the maintenance task
                    UPKEEPIFY_META_KEY_PROVIDER_ID => $provider->term_id, // ID of the service provider
                    UPKEEPIFY_META_KEY_RESPONSE_TOKEN => $token, // Unique token for the provider to edit this response
                ],
            ];

            // Insert the provider's response post
            wp_insert_post($provider_response_data);
        }
    }
}

add_action('save_post', 'upkeepify_generate_provider_tokens', 10, 3);
