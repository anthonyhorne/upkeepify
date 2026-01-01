<?php 
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

// Register Shortcodes
/**
 * Register all Upkeepify shortcodes.
 *
 * Registers the main maintenance tasks shortcode with WordPress.
 *
 * @since 1.0
 * @uses add_shortcode()
 * @hook init
 */
function upkeepify_register_shortcodes() {
    add_shortcode(UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS, 'upkeepify_maintenance_tasks_shortcode');
}
add_action('init', 'upkeepify_register_shortcodes');

/**
 * Shortcode handler function for maintenance tasks display.
 *
 * Displays a list of maintenance tasks with optional limit parameter.
 * Outputs task title and content in a formatted layout.
 *
 * @since 1.0
 * @param array $atts {
 *     Shortcode attributes.
 *
 *     @type int $limit Number of tasks to display (default: 5).
 * }
 * @return string HTML output of maintenance tasks.
 * @uses shortcode_atts()
 * @uses WP_Query
 * @uses WP_Query::have_posts()
 * @uses WP_Query::the_post()
 * @uses wp_reset_postdata()
 */
function upkeepify_maintenance_tasks_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'limit' => 5, // Default limit
    ), $atts, UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS);

    // Use callback with caching
    return upkeepify_get_shortcode_output_cached(
        UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS,
        $atts,
        function() use ($atts) {
            $start_time = microtime(true);

            // Query the maintenance tasks with optimizations
            $query = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'posts_per_page' => intval($atts['limit']),
                'post_status' => 'publish',
                'no_found_rows' => true, // Disable SQL_CALC_FOUND_ROWS for performance
                'update_post_meta_cache' => false, // Skip post meta cache if not needed
                'update_post_term_cache' => false, // Skip term cache if not needed
            ));

            // Log query performance
            upkeepify_log_query_performance('maintenance_tasks_shortcode', $start_time);

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
        },
        UPKEEPIFY_CACHE_EXPIRE_VERY_LONG
    );
}

/**
 * Shortcode to display maintenance tasks in a table format.
 *
 * Outputs all published maintenance tasks in a table with columns
 * for title, rough estimate, category, type, and status.
 *
 * @since 1.0
 * @return string HTML output of tasks table.
 * @uses WP_Query
 * @uses get_post_meta()
 * @uses get_option()
 * @uses wp_get_post_terms()
 * @uses wp_reset_postdata()
 */
function upkeepify_list_tasks_shortcode() {
    ob_start(); // Start output buffering

    $query = new WP_Query([
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ]);

    if ($query->have_posts()) {
        echo '<table class="upkeepify-tasks-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Task Title</th>';
        echo '<th>Rough Estimate</th>';
        echo '<th>Category</th>';
        echo '<th>Type</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Retrieve the rough estimate for this task
            $rough_estimate = get_post_meta($post_id, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, true);
            // Optionally fetch the currency symbol from plugin settings
            $currency_symbol = get_option(UPKEEPIFY_OPTION_SETTINGS)[UPKEEPIFY_SETTING_CURRENCY] ?? '

// Shortcode for Task Submission Form
function upkeepify_task_form_shortcode() {
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT] = $num1 + $num2;

    ob_start();

    echo '<form id="upkeepify-task-form" class="upkeepify-form" action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT, UPKEEPIFY_NONCE_TASK_SUBMIT);

    echo '<p><label for="task_title">Task Title:</label><br />';
    echo '<input type="text" id="task_title" name="task_title" required class="upkeepify-input"></p>';

    echo '<p><label for="task_description">Task Description:</label><br />';
    echo '<textarea id="task_description" name="task_description" required class="upkeepify-textarea"></textarea></p>';

    // Dynamically generated dropdowns for taxonomies associated with UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS
    $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, 'objects');
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_FORM, 'upkeepify_task_form_shortcode');


// Handle Form Submission for Task Creation
function upkeepify_handle_task_form_submission() {
    // Ensure this code only runs when the form is submitted
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify the CAPTCHA
        $user_answer = filter_input(INPUT_POST, 'math', FILTER_SANITIZE_NUMBER_INT);
        if ($user_answer == $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) {
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
                'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'meta_input'   => [
                    UPKEEPIFY_META_KEY_NEAREST_UNIT => $nearest_unit, // Save nearest unit as post meta
                    UPKEEPIFY_META_KEY_GPS_LATITUDE => $latitude, // Save GPS latitude
                    UPKEEPIFY_META_KEY_GPS_LONGITUDE => $longitude, // Save GPS longitude
                ],
            ]);

            if ($task_id && !is_wp_error($task_id)) {
                // Process taxonomies
                $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
                foreach ($taxonomies as $taxonomy) {
                    if (isset($_POST[$taxonomy])) {
                        wp_set_object_terms($task_id, [intval($_POST[$taxonomy])], $taxonomy);
                    }
                }

                // Save nearest unit as post meta
                update_post_meta($task_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, $nearest_unit);

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
        'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES, // Replace with your custom post type for provider responses
        'meta_query' => [
            [
                'key' => UPKEEPIFY_META_KEY_RESPONSE_TOKEN,
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
            $task_id = get_post_meta(get_the_ID(), UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true);
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
add_shortcode(UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM, 'upkeepify_provider_response_form_shortcode');

// Shortcode to display tasks by category
function upkeepify_tasks_by_category_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY, 'upkeepify_tasks_by_category_shortcode');

// Shortcode to display tasks assigned to a specific service provider
function upkeepify_tasks_by_provider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'provider' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'meta_query' => array(
            array(
                'key'     => UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER, 'upkeepify_tasks_by_provider_shortcode');

// Shortcode to display tasks with a specific status
function upkeepify_tasks_by_status_shortcode($atts) {
    $atts = shortcode_atts(array(
        'status' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS, 'upkeepify_tasks_by_status_shortcode');

// Shortcode to display a summary of tasks, including counts of tasks by status
function upkeepify_task_summary_shortcode() {
    $statuses = get_terms(array(
        'taxonomy'   => UPKEEPIFY_TAXONOMY_TASK_STATUS,
        'hide_empty' => false,
    ));

    ob_start();

    if (!empty($statuses)) {
        echo '<ul class="upkeepify-task-summary">';
        foreach ($statuses as $status) {
            $count = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'tax_query' => array(
                    array(
                        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_SUMMARY, 'upkeepify_task_summary_shortcode');

// Shortcode to display a calendar view of tasks, showing due dates and deadlines
function upkeepify_task_calendar_shortcode() {
    $tasks = new WP_Query(array(
        'post_type'      => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_CALENDAR, 'upkeepify_task_calendar_shortcode');
;

            // Retrieve the category, type, and status terms
            $category = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_CATEGORY, array('fields' => 'names'));
            $type = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_TYPE, array('fields' => 'names'));
            $status = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_STATUS, array('fields' => 'names'));

            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . esc_html($currency_symbol) . esc_html($rough_estimate) . '</td>';
            echo '<td>' . (!empty($category) ? esc_html($category[0]) : '') . '</td>';
            echo '<td>' . (!empty($type) ? esc_html($type[0]) : '') . '</td>';
            echo '<td>' . (!empty($status) ? esc_html($status[0]) : '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No maintenance tasks found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean(); // Return the buffered output
}
add_shortcode(UPKEEPIFY_SHORTCODE_LIST_TASKS, 'upkeepify_list_tasks_shortcode');

// Shortcode for Task Submission Form
function upkeepify_task_form_shortcode() {
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT] = $num1 + $num2;

    ob_start();

    echo '<form id="upkeepify-task-form" class="upkeepify-form" action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT, UPKEEPIFY_NONCE_TASK_SUBMIT);

    echo '<p><label for="task_title">Task Title:</label><br />';
    echo '<input type="text" id="task_title" name="task_title" required class="upkeepify-input"></p>';

    echo '<p><label for="task_description">Task Description:</label><br />';
    echo '<textarea id="task_description" name="task_description" required class="upkeepify-textarea"></textarea></p>';

    // Dynamically generated dropdowns for taxonomies associated with UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS
    $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, 'objects');
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_FORM, 'upkeepify_task_form_shortcode');


// Handle Form Submission for Task Creation
function upkeepify_handle_task_form_submission() {
    // Ensure this code only runs when the form is submitted
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['upkeepify_task_submit'], $_POST['math'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify the CAPTCHA
        $user_answer = filter_input(INPUT_POST, 'math', FILTER_SANITIZE_NUMBER_INT);
        if ($user_answer == $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) {
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
                'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'meta_input'   => [
                    UPKEEPIFY_META_KEY_NEAREST_UNIT => $nearest_unit, // Save nearest unit as post meta
                    UPKEEPIFY_META_KEY_GPS_LATITUDE => $latitude, // Save GPS latitude
                    UPKEEPIFY_META_KEY_GPS_LONGITUDE => $longitude, // Save GPS longitude
                ],
            ]);

            if ($task_id && !is_wp_error($task_id)) {
                // Process taxonomies
                $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
                foreach ($taxonomies as $taxonomy) {
                    if (isset($_POST[$taxonomy])) {
                        wp_set_object_terms($task_id, [intval($_POST[$taxonomy])], $taxonomy);
                    }
                }

                // Save nearest unit as post meta
                update_post_meta($task_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, $nearest_unit);

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
        'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES, // Replace with your custom post type for provider responses
        'meta_query' => [
            [
                'key' => UPKEEPIFY_META_KEY_RESPONSE_TOKEN,
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
            $task_id = get_post_meta(get_the_ID(), UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true);
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
add_shortcode(UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM, 'upkeepify_provider_response_form_shortcode');

// Shortcode to display tasks by category
function upkeepify_tasks_by_category_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY, 'upkeepify_tasks_by_category_shortcode');

// Shortcode to display tasks assigned to a specific service provider
function upkeepify_tasks_by_provider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'provider' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'meta_query' => array(
            array(
                'key'     => UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER, 'upkeepify_tasks_by_provider_shortcode');

// Shortcode to display tasks with a specific status
function upkeepify_tasks_by_status_shortcode($atts) {
    $atts = shortcode_atts(array(
        'status' => '',
    ), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS, 'upkeepify_tasks_by_status_shortcode');

// Shortcode to display a summary of tasks, including counts of tasks by status
function upkeepify_task_summary_shortcode() {
    $statuses = get_terms(array(
        'taxonomy'   => UPKEEPIFY_TAXONOMY_TASK_STATUS,
        'hide_empty' => false,
    ));

    ob_start();

    if (!empty($statuses)) {
        echo '<ul class="upkeepify-task-summary">';
        foreach ($statuses as $status) {
            $count = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'tax_query' => array(
                    array(
                        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_SUMMARY, 'upkeepify_task_summary_shortcode');

// Shortcode to display a calendar view of tasks, showing due dates and deadlines
function upkeepify_task_calendar_shortcode() {
    $tasks = new WP_Query(array(
        'post_type'      => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
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
add_shortcode(UPKEEPIFY_SHORTCODE_TASK_CALENDAR, 'upkeepify_task_calendar_shortcode');
