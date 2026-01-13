<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register all Upkeepify shortcodes.
 *
 * @since 1.0
 * @hook init
 */
function upkeepify_register_shortcodes() {
    add_shortcode(UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS, 'upkeepify_maintenance_tasks_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_LIST_TASKS, 'upkeepify_list_tasks_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASK_FORM, 'upkeepify_task_form_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM, 'upkeepify_provider_response_form_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY, 'upkeepify_tasks_by_category_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER, 'upkeepify_tasks_by_provider_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS, 'upkeepify_tasks_by_status_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASK_SUMMARY, 'upkeepify_task_summary_shortcode');
    add_shortcode(UPKEEPIFY_SHORTCODE_TASK_CALENDAR, 'upkeepify_task_calendar_shortcode');
}
add_action('init', 'upkeepify_register_shortcodes');

/**
 * Shortcode handler for a basic list of maintenance tasks.
 *
 * @since 1.0
 * @param array $atts
 * @return string
 */
function upkeepify_maintenance_tasks_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'limit' => 5,
        ),
        $atts,
        UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS
    );

    return upkeepify_get_shortcode_output_cached(
        UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS,
        $atts,
        function () use ($atts) {
            $start_time = microtime(true);

            $query = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'posts_per_page' => intval($atts['limit']),
                'post_status' => 'publish',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ));

            upkeepify_log_query_performance('maintenance_tasks_shortcode', $start_time);

            ob_start();

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
                echo '<p>' . esc_html__('No maintenance tasks found.', 'upkeepify') . '</p>';
            }

            wp_reset_postdata();

            return ob_get_clean();
        },
        UPKEEPIFY_CACHE_EXPIRE_MEDIUM
    );
}

/**
 * Shortcode to display maintenance tasks in a table.
 *
 * @since 1.0
 * @return string
 */
function upkeepify_list_tasks_shortcode() {
    return upkeepify_get_shortcode_output_cached(
        UPKEEPIFY_SHORTCODE_LIST_TASKS,
        array(),
        function () {
            $start_time = microtime(true);

            $query = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'post_status' => 'publish',
                'posts_per_page' => 200,
                'no_found_rows' => true,
            ));

            upkeepify_log_query_performance('list_tasks_shortcode', $start_time);

            $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
            $currency_symbol = isset($settings[UPKEEPIFY_SETTING_CURRENCY]) && $settings[UPKEEPIFY_SETTING_CURRENCY] !== ''
                ? $settings[UPKEEPIFY_SETTING_CURRENCY]
                : '$';

            ob_start();

            if ($query->have_posts()) {
                echo '<table class="upkeepify-tasks-table">';
                echo '<thead><tr>';
                echo '<th>' . esc_html__('Task Title', 'upkeepify') . '</th>';
                echo '<th>' . esc_html__('Rough Estimate', 'upkeepify') . '</th>';
                echo '<th>' . esc_html__('Category', 'upkeepify') . '</th>';
                echo '<th>' . esc_html__('Type', 'upkeepify') . '</th>';
                echo '<th>' . esc_html__('Status', 'upkeepify') . '</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();

                    $rough_estimate = get_post_meta($post_id, UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, true);

                    $category = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_CATEGORY, array('fields' => 'names'));
                    $type = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_TYPE, array('fields' => 'names'));
                    $status = wp_get_post_terms($post_id, UPKEEPIFY_TAXONOMY_TASK_STATUS, array('fields' => 'names'));

                    echo '<tr>';
                    echo '<td>' . esc_html(get_the_title()) . '</td>';
                    echo '<td>' . esc_html($currency_symbol) . esc_html($rough_estimate) . '</td>';
                    echo '<td>' . (!empty($category) ? esc_html($category[0]) : '') . '</td>';
                    echo '<td>' . (!empty($type) ? esc_html($type[0]) : '') . '</td>';
                    echo '<td>' . (!empty($status) ? esc_html($status[0]) : '') . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

            } else {
                echo '<p>' . esc_html__('No maintenance tasks found.', 'upkeepify') . '</p>';
            }

            wp_reset_postdata();

            return ob_get_clean();
        },
        UPKEEPIFY_CACHE_EXPIRE_SHORT
    );
}

/**
 * Shortcode for task submission form.
 *
 * @since 1.0
 * @return string
 */
function upkeepify_task_form_shortcode() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT] = $num1 + $num2;

    $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    $number_of_units = isset($settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS]) ? intval($settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS]) : 10;
    $number_of_units = max(1, $number_of_units);

    ob_start();

    echo '<form id="upkeepify-task-form" class="upkeepify-form" action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT, UPKEEPIFY_NONCE_TASK_SUBMIT);
    echo '<input type="hidden" name="upkeepify_upload" value="1">';

    echo '<p><label for="task_title">' . esc_html__('Task Title:', 'upkeepify') . '</label><br />';
    echo '<input type="text" id="task_title" name="task_title" required class="upkeepify-input"></p>';

    echo '<p><label for="task_description">' . esc_html__('Task Description:', 'upkeepify') . '</label><br />';
    echo '<textarea id="task_description" name="task_description" required class="upkeepify-textarea"></textarea></p>';

    echo '<p><label for="nearest_unit">' . esc_html__('Nearest Unit:', 'upkeepify') . '</label><br />';
    echo '<select id="nearest_unit" name="nearest_unit" class="upkeepify-select">';
    for ($i = 1; $i <= $number_of_units; $i++) {
        echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
    }
    echo '</select></p>';

    $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, 'objects');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy->name, 'hide_empty' => false));
        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        echo '<p><label for="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->label) . ':</label><br />';
        echo '<select id="' . esc_attr($taxonomy->name) . '" name="' . esc_attr($taxonomy->name) . '" class="upkeepify-select">';
        foreach ($terms as $term) {
            echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
        }
        echo '</select></p>';
    }

    echo '<p><label for="task_photo">' . esc_html__('Upload Photo:', 'upkeepify') . '</label><br />';
    echo '<input type="file" id="task_photo" name="task_photo" accept="image/*" capture="environment" class="upkeepify-file-input"></p>';

    echo '<p><label for="gps_latitude">' . esc_html__('Latitude:', 'upkeepify') . '</label><br />';
    echo '<input type="text" id="gps_latitude" name="gps_latitude" required class="upkeepify-input"></p>';

    echo '<p><label for="gps_longitude">' . esc_html__('Longitude:', 'upkeepify') . '</label><br />';
    echo '<input type="text" id="gps_longitude" name="gps_longitude" required class="upkeepify-input"></p>';

    echo '<p><label for="math">' . esc_html(sprintf('What is %d + %d? (For spam prevention)', $num1, $num2)) . '</label><br />';
    echo '<input type="text" id="math" name="math" required class="upkeepify-input"></p>';

    echo '<p><input type="submit" name="upkeepify_task_submit" value="' . esc_attr__('Submit Task', 'upkeepify') . '" class="upkeepify-submit-button"></p>';

    echo '</form>';

    return ob_get_clean();
}

/**
 * Handle front-end task form submission.
 *
 * @since 1.0
 * @hook init
 */
function upkeepify_handle_task_form_submission() {
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
        return;
    }

    if (!isset($_POST['upkeepify_task_submit'], $_POST['math'], $_POST['upkeepify_upload'])) {
        return;
    }

    if (!isset($_POST[UPKEEPIFY_NONCE_TASK_SUBMIT]) || !wp_verify_nonce($_POST[UPKEEPIFY_NONCE_TASK_SUBMIT], UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT)) {
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_answer = filter_input(INPUT_POST, 'math', FILTER_SANITIZE_NUMBER_INT);
    if (!isset($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) || intval($user_answer) !== intval($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT])) {
        return;
    }

    $task_title = sanitize_text_field($_POST['task_title']);
    $task_description = sanitize_textarea_field($_POST['task_description']);

    $nearest_unit = isset($_POST['nearest_unit']) ? intval($_POST['nearest_unit']) : 1;
    $latitude = isset($_POST['gps_latitude']) ? sanitize_text_field($_POST['gps_latitude']) : '';
    $longitude = isset($_POST['gps_longitude']) ? sanitize_text_field($_POST['gps_longitude']) : '';

    // Handle file upload with scoped validation
    $photo_attachment_id = 0;
    if (isset($_FILES['task_photo']) && !empty($_FILES['task_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Validate upload using scoped validation
        $validation = upkeepify_validate_upload($_FILES['task_photo']);
        if (is_wp_error($validation)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Validation Error: ' . $validation->get_error_message());
            }
            return;
        }

        // Handle the upload
        $upload_result = wp_handle_upload($_FILES['task_photo'], array('test_form' => true));
        if (isset($upload_result['error'])) {
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Error: ' . $upload_result['error']);
            }
            return;
        }

        // Prepare file data for media handling
        $file_data = array(
            'name'     => $_FILES['task_photo']['name'],
            'type'     => $upload_result['type'],
            'tmp_name' => $upload_result['file'],
            'error'    => $_FILES['task_photo']['error'],
            'size'     => $_FILES['task_photo']['size'],
        );

        // Sideload the file into the media library
        $attachment_id = media_handle_sideload($file_data, 0);

        if (is_wp_error($attachment_id)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Media Sideload Error: ' . $attachment_id->get_error_message());
            }
            return;
        }

        $photo_attachment_id = $attachment_id;
    }

    $meta = array(
        UPKEEPIFY_META_KEY_NEAREST_UNIT => $nearest_unit,
        UPKEEPIFY_META_KEY_GPS_LATITUDE => $latitude,
        UPKEEPIFY_META_KEY_GPS_LONGITUDE => $longitude,
    );

    $validation = upkeepify_validate_maintenance_task(array(
        'post_title' => $task_title,
        'post_content' => $task_description,
        'meta' => $meta,
    ));

    if (is_wp_error($validation)) {
        if (WP_DEBUG) {
            error_log('Upkeepify Task Validation: ' . $validation->get_error_message());
        }
        return;
    }

    $task_id = wp_insert_post(
        array(
            'post_title' => $task_title,
            'post_content' => $task_description,
            'post_status' => 'publish',
            'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
            'meta_input' => $meta,
        ),
        true
    );

    if (is_wp_error($task_id)) {
        if (WP_DEBUG) {
            error_log('Upkeepify Task Submission Error: ' . $task_id->get_error_message());
        }
        return;
    }

    // Set the photo as the featured image if uploaded
    if ($photo_attachment_id > 0) {
        set_post_thumbnail($task_id, $photo_attachment_id);
    }

    $taxonomies = get_object_taxonomies(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS);
    foreach ($taxonomies as $taxonomy) {
        if (isset($_POST[$taxonomy]) && is_numeric($_POST[$taxonomy])) {
            wp_set_object_terms($task_id, array(intval($_POST[$taxonomy])), $taxonomy);
        }
    }
}
add_action('init', 'upkeepify_handle_task_form_submission');

/**
 * Provider response form (token-gated).
 *
 * @since 1.0
 * @param array $atts
 * @return string
 */
function upkeepify_provider_response_form_shortcode($atts) {
    ob_start();

    $attributes = shortcode_atts(array('token' => ''), $atts, UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM);
    $token = sanitize_text_field($attributes['token']);

    if ($token === '') {
        echo '<p>' . esc_html__('Missing response token.', 'upkeepify') . '</p>';
        return ob_get_clean();
    }

    $validation = upkeepify_validate_task_meta(UPKEEPIFY_META_KEY_RESPONSE_TOKEN, $token);
    if (is_wp_error($validation)) {
        echo '<p>' . esc_html__('Invalid token.', 'upkeepify') . '</p>';
        return ob_get_clean();
    }

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
        'meta_query' => array(
            array(
                'key' => UPKEEPIFY_META_KEY_RESPONSE_TOKEN,
                'value' => $token,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
        'no_found_rows' => true,
    ));

    if ($query->have_posts()) {
        $query->the_post();
        $response_id = get_the_ID();

        $task_id = intval(get_post_meta($response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true));
        $task_post = $task_id ? get_post($task_id) : null;

        if ($task_post) {
            echo '<h3>' . esc_html__('Task Details', 'upkeepify') . '</h3>';
            echo '<p>' . esc_html__('Title:', 'upkeepify') . ' ' . esc_html($task_post->post_title) . '</p>';
            echo '<p>' . esc_html__('Description:', 'upkeepify') . ' ' . esc_html($task_post->post_content) . '</p>';
        }

        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        echo '<input type="hidden" name="action" value="' . esc_attr(UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT) . '">';
        echo '<input type="hidden" name="response_id" value="' . esc_attr($response_id) . '">';
        echo '<textarea name="provider_response" placeholder="' . esc_attr__('Your response', 'upkeepify') . '" class="upkeepify-textarea"></textarea>';
        echo '<input type="submit" value="' . esc_attr__('Submit Response', 'upkeepify') . '" class="upkeepify-submit-button">';
        echo '</form>';

        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__('Invalid token or response not found.', 'upkeepify') . '</p>';
    }

    return ob_get_clean();
}

/**
 * Tasks by category shortcode.
 *
 * @since 1.0
 * @param array $atts
 * @return string
 */
function upkeepify_tasks_by_category_shortcode($atts) {
    $atts = shortcode_atts(array('category' => ''), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_CATEGORY);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'posts_per_page' => 50,
        'no_found_rows' => true,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
                'field' => 'slug',
                'terms' => sanitize_title($atts['category']),
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-category">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . esc_html(get_the_title()) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . esc_html__('No tasks found in this category.', 'upkeepify') . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Tasks by provider shortcode.
 *
 * @since 1.0
 * @param array $atts
 * @return string
 */
function upkeepify_tasks_by_provider_shortcode($atts) {
    $atts = shortcode_atts(array('provider' => ''), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_PROVIDER);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'posts_per_page' => 50,
        'no_found_rows' => true,
        'meta_query' => array(
            array(
                'key' => UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
                'value' => sanitize_text_field($atts['provider']),
                'compare' => '=',
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-provider">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . esc_html(get_the_title()) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . esc_html__('No tasks found for this service provider.', 'upkeepify') . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Tasks by status shortcode.
 *
 * @since 1.0
 * @param array $atts
 * @return string
 */
function upkeepify_tasks_by_status_shortcode($atts) {
    $atts = shortcode_atts(array('status' => ''), $atts, UPKEEPIFY_SHORTCODE_TASKS_BY_STATUS);

    $query = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'posts_per_page' => 50,
        'no_found_rows' => true,
        'tax_query' => array(
            array(
                'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
                'field' => 'slug',
                'terms' => sanitize_title($atts['status']),
            ),
        ),
    ));

    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="upkeepify-tasks-by-status">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . esc_html(get_the_title()) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . esc_html__('No tasks found with this status.', 'upkeepify') . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Task summary shortcode.
 *
 * @since 1.0
 * @return string
 */
function upkeepify_task_summary_shortcode() {
    $statuses = get_terms(array(
        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
        'hide_empty' => false,
    ));

    ob_start();

    if (!is_wp_error($statuses) && !empty($statuses)) {
        echo '<ul class="upkeepify-task-summary">';
        foreach ($statuses as $status) {
            $count = new WP_Query(array(
                'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
                'posts_per_page' => 1,
                'no_found_rows' => false,
                'tax_query' => array(
                    array(
                        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
                        'field' => 'slug',
                        'terms' => $status->slug,
                    ),
                ),
            ));
            echo '<li>' . esc_html($status->name) . ': ' . esc_html($count->found_posts) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . esc_html__('No task statuses found.', 'upkeepify') . '</p>';
    }

    return ob_get_clean();
}

/**
 * Task calendar shortcode.
 *
 * @since 1.0
 * @return string
 */
function upkeepify_task_calendar_shortcode() {
    $tasks = new WP_Query(array(
        'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'posts_per_page' => 200,
        'no_found_rows' => true,
        'meta_key' => UPKEEPIFY_META_KEY_DUE_DATE,
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ));

    ob_start();

    if ($tasks->have_posts()) {
        echo '<div id="upkeepify-task-calendar">';
        while ($tasks->have_posts()) {
            $tasks->the_post();
            $due_date = get_post_meta(get_the_ID(), UPKEEPIFY_META_KEY_DUE_DATE, true);
            echo '<div class="task">';
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<p>' . esc_html__('Due Date:', 'upkeepify') . ' ' . esc_html($due_date) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>' . esc_html__('No tasks found.', 'upkeepify') . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
