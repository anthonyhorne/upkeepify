<?php 
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

// Register Custom Post Types
function upkeepify_register_custom_post_types() {
    // Register the Maintenance Tasks CPT
    $args_maintenance_tasks = array(
        'public' => true,
        'label'  => __('Maintenance Tasks', 'upkeepify'),
        'supports' => array('title', 'editor', 'custom-fields', 'comments'),
        'show_in_rest' => true, // Enables Gutenberg support
        'menu_icon' => 'dashicons-hammer', // Custom dashicon for the menu
        'has_archive' => true,
        'rewrite' => array('slug' => 'maintenance-tasks'), // Custom slug for this CPT
    );
    $result = register_post_type('maintenance_tasks', $args_maintenance_tasks);

    // The $result variable holds the result of the register_post_type function, which returns a WP_Error object on failure or a boolean true on success.
    // Check if the post type registration was successful
    if ($result) {
        // Add a success notification
        //upkeepify_add_notification(__('Maintenance Tasks custom post type registered successfully.', 'upkeepify'), 'success');
    } else {
        // Add an error notification
        upkeepify_add_notification(__('Failed to register Maintenance Tasks custom post type.', 'upkeepify'), 'error');
    }
}
add_action('init', 'upkeepify_register_custom_post_types');

// Register Shortcodes
function upkeepify_register_shortcodes() {
    add_shortcode('maintenance_tasks', 'upkeepify_maintenance_tasks_shortcode');
}
add_action('init', 'upkeepify_register_shortcodes');

// Shortcode handler function
function upkeepify_maintenance_tasks_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'limit' => 5, // Default limit
    ), $atts, 'maintenance_tasks');

    // Query the maintenance tasks
    $query = new WP_Query(array(
        'post_type' => 'maintenance_tasks',
        'posts_per_page' => intval($atts['limit']),
    ));

    // Start output buffering
    ob_start();

    // Check if there are any posts
    if ($query->have_posts()) {
        echo '<div class="maintenance-tasks">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="maintenance-task">';
            echo '<h2>' . esc_html(get_the_title()) . '</h2>';
            echo '<div class="content">' . wp_kses_post(get_the_content()) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>' . __('No maintenance tasks found.', 'upkeepify') . '</p>';
    }

    // Reset post data
    wp_reset_postdata();

    // Return the output
    return ob_get_clean();
}

// Shortcode to Display Maintenance Tasks
function upkeepify_list_tasks_shortcode() {
    ob_start(); // Start output buffering

    $query = new WP_Query([
        'post_type' => 'maintenance_tasks',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ]);

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Retrieve the rough estimate for this task
            $rough_estimate = get_post_meta($post_id, 'upkeepify_rough_estimate', true);
            // Optionally fetch the currency symbol from plugin settings
            $currency_symbol = get_option('upkeepify_settings')['upkeepify_currency'] ?? '$';

            echo '<li class="upkeepify-task-item"><strong>' . get_the_title() . '</strong>';
            echo 'Rough Estimate: ' . esc_html($currency_symbol) . esc_html($rough_estimate);
            // Optionally display more details here (e.g., status, category)
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No maintenance tasks found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean(); // Return the buffered output
}
add_shortcode('upkeepify_list_tasks', 'upkeepify_list_tasks_shortcode');

// Shortcode for Task Submission Form
function upkeepify_task_form_shortcode() {
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['upkeepify_math_result'] = $num1 + $num2;

    ob_start();

    echo '<form id="upkeepify-task-form" class="upkeepify-form" action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">';
    wp_nonce_field('upkeepify_task_submit_action', 'upkeepify_task_submit_nonce');

    echo '<p><label for="task_title">Task Title:</label><br />';
    echo '<input type="text" id="task_title" name="task_title" required class="upkeepify-input"></p>';

    echo '<p><label for="task_description">Task Description:</label><br />';
    echo '<textarea id="task_description" name="task_description" required class="upkeepify-textarea"></textarea></p>';

    // Dynamically generated dropdowns for taxonomies associated with 'maintenance_tasks'
    $taxonomies = get_object_taxonomies('maintenance_tasks', 'objects');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy->name, 'hide_empty' => false));
        if (!empty($terms)) {
            echo '<p><label for="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->label) . ':</label><br />';
            echo '<select id="' . esc_attr($taxonomy->name) . '" name="' . esc_attr($taxonomy->name) . '" class="upkeepify-select">';
            foreach ($terms as $term) {
                echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
            }
            echo '</select></p>';
        }
    }

    echo '<p><label for="task_photo">Upload Photo:</label><br />';
    echo '<input type="file" id="task_photo" name="task_photo" accept="image/*" capture="environment" class="upkeepify-file-input"></p>';

    echo '<p><label for="math">What is ' . $num1 . ' + ' . $num2 . '? (For spam prevention)</label><br />';
    echo '<input type="text" id="math" name="math" required class="upkeepify-input"></p>';

    echo '<p><input type="submit" value="Submit Task" class="upkeepify-submit-button"></p>';

    echo '</form>';

    // Thank you message and New Task button, hidden by default
    echo '<div id="thank-you-message" style="display: none; margin-top: 20px;">';
    echo '<p style="color: green;">Thank you for your submission. Your task is pending review.</p>';
    echo '<button id="new-task-button" class="upkeepify-button">Create a New Task</button>';
    echo '</div>';

    // JavaScript to handle form interaction
echo "<script>
    document.getElementById('upkeepify-task-form').addEventListener('submit', function(event) {
        event.preventDefault();
        // Simulate form submission here for demonstration. In production, you would handle form data properly.

        var form = document.getElementById('upkeepify-task-form');
        var thankYouMessage = document.getElementById('thank-you-message');
        var gpsLatitude = document.getElementById('gps_latitude');
        var gpsLongitude = document.getElementById('gps_longitude');

        form.style.display = 'none'; // Hide the form
        gpsLatitude.style.display = 'none'; // Hide GPS latitude
        gpsLongitude.style.display = 'none'; // Hide GPS longitude
        thankYouMessage.style.display = 'block'; // Show thank you message
    });

    document.getElementById('new-task-button').addEventListener('click', function() {
        var form = document.getElementById('upkeepify-task-form');
        var thankYouMessage = document.getElementById('thank-you-message');
        var gpsLatitude = document.getElementById('gps_latitude');
        var gpsLongitude = document.getElementById('gps_longitude');

        form.style.display = 'block'; // Show the form again
        gpsLatitude.style.display = 'block'; // Show GPS latitude
        gpsLongitude.style.display = 'block'; // Show GPS longitude
        thankYouMessage.style.display = 'none'; // Hide thank you message
        form.reset(); // Reset form fields
    });
</script>";

    // Optional: Script to auto-fill coordinates if user permits
    echo '<script>
    function fillGPSLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById("gps_latitude").value = position.coords.latitude;
                document.getElementById("gps_longitude").value = position.coords.longitude;
            });
        }
    }
    </script>';

    // Fields for GPS coordinates
    echo '<label for="gps_latitude">Latitude:</label><br />';
    echo '<input type="text" id="gps_latitude" name="gps_latitude" required onclick="fillGPSLocation();" class="upkeepify-input"><br />';
    echo '<label for="gps_longitude">Longitude:</label><br />';
    echo '<input type="text" id="gps_longitude" name="gps_longitude" required onclick="fillGPSLocation();" class="upkeepify-input"><br />';

    return ob_get_clean();
}
add_shortcode('upkeepify_task_form', 'upkeepify_task_form_shortcode');


// Handle Form Submission for Task Creation
function upkeepify_handle_task_form_submission() {
    // Ensure this code only runs when the form is submitted
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify the CAPTCHA
        $user_answer = filter_input(INPUT_POST, 'math', FILTER_SANITIZE_NUMBER_INT);
        if ($user_answer == $_SESSION['upkeepify_math_result']) {
            // Proceed if math answer is correct
            $task_title = sanitize_text_field($_POST['task_title']);
            $task_description = sanitize_textarea_field($_POST['task_description']);
            $nearest_unit = isset($_POST['nearest_unit']) ? intval($_POST['nearest_unit']) : 1; // Default to 1 if not set
            $latitude = sanitize_text_field($_POST['gps_latitude']);
            $longitude = sanitize_text_field($_POST['gps_longitude']);

            // Insert the new task
            $task_id = wp_insert_post([
                'post_title'   => $task_title,
                'post_content' => $task_description,
                'post_status'  => 'publish',
                'post_type'    => 'maintenance_tasks',
                'meta_input'   => [
                    'upkeepify_nearest_unit' => $nearest_unit, // Save nearest unit as post meta
                    'upkeepify_gps_latitude' => $latitude, // Save GPS latitude
                    'upkeepify_gps_longitude' => $longitude, // Save GPS longitude
                ],
            ]);

            if ($task_id && !is_wp_error($task_id)) {
                // Process taxonomies
                $taxonomies = get_object_taxonomies('maintenance_tasks');
                foreach ($taxonomies as $taxonomy) {
                    if (isset($_POST[$taxonomy])) {
                        wp_set_object_terms($task_id, [intval($_POST[$taxonomy])], $taxonomy);
                    }
                }

                // Save nearest unit as post meta
                update_post_meta($task_id, 'upkeepify_nearest_unit', $nearest_unit);

                // Thank you message (output as part of the response to the form submission)
                echo '<div id="upkeepify-thank-you-message" style="color: green;">Thank you for your submission.</div>';
                // Clear the form fields (this requires custom JavaScript to clear form fields after submission)
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('upkeepify-task-form').reset();
                        // Optionally, remove the thank you message after a few seconds
                        setTimeout(function() {
                            var thankYouMessage = document.getElementById('upkeepify-thank-you-message');
                            if (thankYouMessage) thankYouMessage.style.display = 'none';
                        }, 5000);
                    });
                </script>
                <?php 
            } else {
                echo '<p>Failed to create task. Please try again.</p>';
            }
        } else {
            echo '<p>Incorrect answer to the security question. Please try again.</p>';
        }
    }
}
add_action('init', 'upkeepify_handle_task_form_submission');

//Section for Responses
function upkeepify_provider_response_form_shortcode($atts) {
    // Start output buffering
    ob_start();

    // Check if a token is provided
    $attributes = shortcode_atts(['token' => ''], $atts);
    $token = sanitize_text_field($attributes['token']);

    // Find the provider response post with this token
    $query_args = [
        'post_type' => 'provider_responses', // Replace with your custom post type for provider responses
        'meta_query' => [
            [
                'key' => 'response_token',
                'value' => $token,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
    ];
    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $task_id = get_post_meta(get_the_ID(), 'response_task_id', true);
            $task_post = get_post($task_id);

            // Display task details (readonly)
            echo '<h3>Task Details</h3>';
            echo '<p>Title: ' . esc_html($task_post->post_title) . '</p>';
            echo '<p>Description: ' . esc_html($task_post->post_content) . '</p>';

            // Form for provider to submit response
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
            echo '<input type="hidden" name="action" value="upkeepify_provider_response_submit">';
            echo '<input type="hidden" name="response_id" value="' . get_the_ID() . '">';
            // Additional fields for provider's response here
            echo '<textarea name="provider_response" placeholder="Your response" class="upkeepify-textarea"></textarea>';
            echo '<input type="submit" value="Submit Response" class="upkeepify-submit-button">';
            echo '</form>';
        }
    } else {
        echo '<p>Invalid token or response not found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('upkeepify_provider_response_form', 'upkeepify_provider_response_form_shortcode');

// Shortcode to display tasks by category
function upkeepify_tasks_by_category_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
    ), $atts, 'upkeepify_tasks_by_category');

    $query = new WP_Query(array(
        'post_type' => 'maintenance_tasks',
        'tax_query' => array(
            array(
                'taxonomy' => 'task_category',
                'field'    => 'slug',
                'terms'    => $atts['category'],
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-category">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . get_the_title() . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No tasks found in this category.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('upkeepify_tasks_by_category', 'upkeepify_tasks_by_category_shortcode');

// Shortcode to display tasks assigned to a specific service provider
function upkeepify_tasks_by_provider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'provider' => '',
    ), $atts, 'upkeepify_tasks_by_provider');

    $query = new WP_Query(array(
        'post_type' => 'maintenance_tasks',
        'meta_query' => array(
            array(
                'key'     => 'assigned_service_provider',
                'value'   => $atts['provider'],
                'compare' => '=',
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-provider">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . get_the_title() . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No tasks found for this service provider.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('upkeepify_tasks_by_provider', 'upkeepify_tasks_by_provider_shortcode');

// Shortcode to display tasks with a specific status
function upkeepify_tasks_by_status_shortcode($atts) {
    $atts = shortcode_atts(array(
        'status' => '',
    ), $atts, 'upkeepify_tasks_by_status');

    $query = new WP_Query(array(
        'post_type' => 'maintenance_tasks',
        'tax_query' => array(
            array(
                'taxonomy' => 'task_status',
                'field'    => 'slug',
                'terms'    => $atts['status'],
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-status">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . get_the_title() . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No tasks found with this status.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('upkeepify_tasks_by_status', 'upkeepify_tasks_by_status_shortcode');

// Shortcode to display a summary of tasks, including counts of tasks by status
function upkeepify_task_summary_shortcode() {
    $statuses = get_terms(array(
        'taxonomy'   => 'task_status',
        'hide_empty' => false,
    ));

    ob_start();

    if (!empty($statuses)) {
        echo '<ul class="upkeepify-task-summary">';
        foreach ($statuses as $status) {
            $count = new WP_Query(array(
                'post_type' => 'maintenance_tasks',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'task_status',
                        'field'    => 'slug',
                        'terms'    => $status->slug,
                    ),
                ),
            ));
            echo '<li>' . esc_html($status->name) . ': ' . $count->found_posts . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No task statuses found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('upkeepify_task_summary', 'upkeepify_task_summary_shortcode');

// Shortcode to display a calendar view of tasks, showing due dates and deadlines
function upkeepify_task_calendar_shortcode() {
    $tasks = new WP_Query(array(
        'post_type'      => 'maintenance_tasks',
        'posts_per_page' => -1,
        'meta_key'       => 'due_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    ));

    ob_start();

    if ($tasks->have_posts()) {
        echo '<div id="upkeepify-task-calendar">';
        while ($tasks->have_posts()) {
            $tasks->the_post();
            $due_date = get_post_meta(get_the_ID(), 'due_date', true);
            echo '<div class="task">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<p>Due Date: ' . esc_html($due_date) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No tasks found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('upkeepify_task_calendar', 'upkeepify_task_calendar_shortcode');
