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
    add_shortcode(UPKEEPIFY_SHORTCODE_RESIDENT_CONFIRMATION_FORM, 'upkeepify_resident_confirmation_form_shortcode');
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
 * Pick a compact icon for resident-facing task choices.
 *
 * Uses HTML entities so source files remain ASCII while the UI can still show
 * recognizable tap targets.
 *
 * @since 1.0
 * @param string $label Choice label.
 * @return string HTML entity for the icon.
 */
function upkeepify_get_task_choice_icon($label) {
    $text = strtolower($label);

    if (strpos($text, 'plumb') !== false || strpos($text, 'water') !== false || strpos($text, 'leak') !== false || strpos($text, 'drain') !== false) {
        return '&#128167;';
    }

    if (strpos($text, 'electric') !== false || strpos($text, 'light') !== false || strpos($text, 'power') !== false) {
        return '&#9889;';
    }

    if (strpos($text, 'garden') !== false || strpos($text, 'landscap') !== false || strpos($text, 'tree') !== false) {
        return '&#127807;';
    }

    if (strpos($text, 'security') !== false || strpos($text, 'gate') !== false || strpos($text, 'lock') !== false) {
        return '&#128274;';
    }

    if (strpos($text, 'clean') !== false || strpos($text, 'bin') !== false || strpos($text, 'rubbish') !== false) {
        return '&#10024;';
    }

    if (strpos($text, 'inspect') !== false) {
        return '&#128269;';
    }

    if (strpos($text, 'install') !== false) {
        return '&#128296;';
    }

    return '&#128295;';
}

/**
 * Render taxonomy choices as tap-friendly chips.
 *
 * @since 1.0
 * @param string $taxonomy_slug Taxonomy slug.
 * @param string $legend Fieldset legend.
 * @return void
 */
function upkeepify_render_taxonomy_choice_chips($taxonomy_slug, $legend) {
    $terms = get_terms(array('taxonomy' => $taxonomy_slug, 'hide_empty' => false));
    if (is_wp_error($terms) || empty($terms)) {
        return;
    }

    echo '<fieldset class="upkeepify-choice-group">';
    echo '<legend>' . esc_html($legend) . '</legend>';
    echo '<div class="upkeepify-choice-grid">';
    foreach ($terms as $term) {
        echo '<label class="upkeepify-choice-chip">';
        echo '<input type="radio" name="' . esc_attr($taxonomy_slug) . '" value="' . esc_attr($term->term_id) . '" required>';
        echo '<span class="upkeepify-choice-card">';
        echo '<span class="upkeepify-choice-icon" aria-hidden="true">' . upkeepify_get_task_choice_icon($term->name) . '</span>';
        echo '<span class="upkeepify-choice-label">' . esc_html($term->name) . '</span>';
        echo '</span>';
        echo '</label>';
    }
    echo '</div>';
    echo '</fieldset>';
}

/**
 * Render unit selection as chips grouped into small ranges.
 *
 * @since 1.0
 * @param int $number_of_units Number of units configured for the complex.
 * @return void
 */
function upkeepify_render_unit_picker($number_of_units) {
    $number_of_units = max(1, intval($number_of_units));
    $range_size = 5;

    echo '<fieldset class="upkeepify-choice-group upkeepify-unit-picker">';
    echo '<legend>' . esc_html__('Where is it?', 'upkeepify') . '</legend>';

    if ($number_of_units > 20) {
        echo '<div class="upkeepify-range-toolbar" aria-label="' . esc_attr__('Unit ranges', 'upkeepify') . '">';
        for ($start = 1; $start <= $number_of_units; $start += $range_size) {
            $end = min($number_of_units, $start + $range_size - 1);
            $range_id = 'upkeepify-unit-range-' . $start . '-' . $end;
            $active = (1 === $start) ? ' is-active' : '';
            echo '<button type="button" class="upkeepify-range-button' . esc_attr($active) . '" data-upkeepify-range="' . esc_attr($range_id) . '">';
            echo esc_html($start . '-' . $end);
            echo '</button>';
        }
        echo '</div>';
    }

    for ($start = 1; $start <= $number_of_units; $start += $range_size) {
        $end = min($number_of_units, $start + $range_size - 1);
        $range_id = 'upkeepify-unit-range-' . $start . '-' . $end;
        $active = ($number_of_units <= 20 || 1 === $start) ? ' is-active' : '';
        echo '<div class="upkeepify-unit-group' . esc_attr($active) . '" id="' . esc_attr($range_id) . '">';
        if ($number_of_units > 20) {
            echo '<span class="upkeepify-unit-range-label">' . esc_html(sprintf(__('Units %1$d-%2$d', 'upkeepify'), $start, $end)) . '</span>';
        }
        echo '<div class="upkeepify-unit-grid">';
        for ($unit = $start; $unit <= $end; $unit++) {
            echo '<label class="upkeepify-unit-chip">';
            echo '<input type="radio" name="nearest_unit" value="' . esc_attr($unit) . '" required>';
            echo '<span>' . esc_html($unit) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '</div>';
    }

    echo '</fieldset>';
}

/**
 * Build a short, readable title from the resident submission.
 *
 * @since 1.0
 * @param string $description Task description.
 * @param string $category_name Selected category name.
 * @param string $type_name Selected type name.
 * @param int    $nearest_unit Nearest unit number.
 * @return string Inferred task title.
 */
function upkeepify_infer_task_title($description, $category_name = '', $type_name = '', $nearest_unit = 0) {
    $category_name = trim(wp_strip_all_tags((string) $category_name));
    $type_name = trim(wp_strip_all_tags((string) $type_name));
    $nearest_unit = intval($nearest_unit);

    $generic_categories = array('general', 'general maintenance', 'other');
    $generic_types = array('', 'repair', 'inspection', 'installation', 'other');

    $category_key = strtolower($category_name);
    $type_key = strtolower($type_name);
    $base = '';

    if ($category_name !== '' && !in_array($category_key, $generic_categories, true)) {
        $base = $category_name;
        if ($type_name !== '' && !in_array($type_key, $generic_types, true)) {
            $base .= ' ' . strtolower($type_name);
        } elseif ('repair' === $type_key) {
            $base .= ' repair';
        } else {
            $base .= ' issue';
        }
    } elseif ($type_name !== '' && !in_array($type_key, $generic_types, true)) {
        $base = $type_name;
    } else {
        $base = upkeepify_summarize_task_description($description);
    }

    if ($base === '') {
        $base = __('Maintenance request', 'upkeepify');
    }

    if ($nearest_unit > 0) {
        $base .= ' near Unit ' . $nearest_unit;
    }

    return sanitize_text_field($base);
}

/**
 * Summarize the first useful words from a resident description.
 *
 * @since 1.0
 * @param string $description Task description.
 * @return string Short summary.
 */
function upkeepify_summarize_task_description($description) {
    $text = preg_replace('/\s+/', ' ', trim(wp_strip_all_tags((string) $description)));
    if ($text === '') {
        return '';
    }

    $parts = preg_split('/[.!?\r\n]+/', $text);
    $summary = isset($parts[0]) ? trim($parts[0]) : $text;
    $summary = preg_replace('/^(please|the|a|an|there is|there are|we have|i have)\s+/i', '', $summary);
    $summary = wp_trim_words($summary, 8, '');
    $summary = trim($summary, " \t\n\r\0\x0B,.;:-");

    if ($summary === '') {
        return '';
    }

    return ucfirst($summary);
}

/**
 * Shortcode for task submission form.
 *
 * @since 1.0
 * @return string
 */
function upkeepify_get_task_form_redirect_url() {
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
        $redirect    = home_url( $request_uri );
    }

    return remove_query_arg( array( 'upkeepify_task_status', 'upkeepify_task_error' ), $redirect );
}

/**
 * Redirect back to the public task form with a visible status message.
 *
 * @since 1.1
 * @param string $status Submission outcome.
 * @param string $error  Optional error code.
 * @return void
 */
function upkeepify_redirect_task_form_status( $status, $error = '' ) {
    $args = array(
        'upkeepify_task_status' => sanitize_key( $status ),
    );

    if ( '' !== $error ) {
        $args['upkeepify_task_error'] = sanitize_key( $error );
    }

    wp_safe_redirect( add_query_arg( $args, upkeepify_get_task_form_redirect_url() ) );
    exit;
}

/**
 * Render the public task form submission notice when redirected back.
 *
 * @since 1.1
 * @return void
 */
function upkeepify_render_task_form_notice() {
    $status = isset( $_GET['upkeepify_task_status'] ) ? sanitize_key( wp_unslash( $_GET['upkeepify_task_status'] ) ) : '';
    $error  = isset( $_GET['upkeepify_task_error'] ) ? sanitize_key( wp_unslash( $_GET['upkeepify_task_error'] ) ) : '';

    if ( 'success' === $status ) {
        echo '<p class="upkeepify-notice upkeepify-notice--success">' . esc_html__( 'Thanks, your request was received and is awaiting trustee review.', 'upkeepify' ) . '</p>';
        return;
    }

    if ( 'error' !== $status ) {
        return;
    }

    $messages = array(
        'public_disabled'   => __( 'Public task submission is not available at this time.', 'upkeepify' ),
        'security_failed'   => __( 'We could not verify your submission. Please refresh the page and try again.', 'upkeepify' ),
        'captcha_failed'    => __( 'The security answer was incorrect or expired. Please try again.', 'upkeepify' ),
        'upload_invalid'    => __( 'The photo could not be uploaded. Please check the file and try again.', 'upkeepify' ),
        'validation_failed' => __( 'Please complete the required task details and try again.', 'upkeepify' ),
        'save_failed'       => __( 'We could not save your request just now. Please try again.', 'upkeepify' ),
    );

    $message = isset( $messages[ $error ] )
        ? $messages[ $error ]
        : __( 'We could not submit your request. Please try again.', 'upkeepify' );

    echo '<p class="upkeepify-notice upkeepify-notice--error">' . esc_html( $message ) . '</p>';
}

function upkeepify_task_form_shortcode() {
    $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());

    // Enforce the Allow Public Task Logging setting.
    if ( empty( $settings[ UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING ] ) ) {
        return '<p>' . esc_html__( 'Public task submission is not available at this time.', 'upkeepify' ) . '</p>';
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION[UPKEEPIFY_SESSION_MATH_RESULT] = $num1 + $num2;

    $number_of_units = isset($settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS]) ? intval($settings[UPKEEPIFY_SETTING_NUMBER_OF_UNITS]) : 10;
    $number_of_units = max(1, $number_of_units);

    ob_start();

    $form_action = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    upkeepify_render_task_form_notice();
    echo '<form id="upkeepify-task-form" class="upkeepify-form upkeepify-request-form" action="' . esc_url($form_action) . '" method="post" enctype="multipart/form-data">';
    wp_nonce_field(UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT, UPKEEPIFY_NONCE_TASK_SUBMIT);
    echo '<input type="hidden" name="upkeepify_upload" value="1">';
    echo '<input type="hidden" id="task_title" name="task_title" value="">';

    echo '<div class="upkeepify-form-intro">';
    echo '<h2>' . esc_html__('Report an issue', 'upkeepify') . '</h2>';
    echo '<p>' . esc_html__('Tap what fits, add the details, and send it through.', 'upkeepify') . '</p>';
    echo '<p class="upkeepify-form-version">' . sprintf( esc_html__( 'Version %s', 'upkeepify' ), esc_html( UPKEEPIFY_VERSION ) ) . '</p>';
    echo '</div>';

    upkeepify_render_taxonomy_choice_chips(UPKEEPIFY_TAXONOMY_TASK_CATEGORY, __('What needs attention?', 'upkeepify'));
    upkeepify_render_taxonomy_choice_chips(UPKEEPIFY_TAXONOMY_TASK_TYPE, __('What kind of work?', 'upkeepify'));
    upkeepify_render_unit_picker($number_of_units);

    echo '<div class="upkeepify-field">';
    echo '<label for="task_description">' . esc_html__('What is happening?', 'upkeepify') . '</label>';
    echo '<textarea id="task_description" name="task_description" required class="upkeepify-textarea" rows="5" placeholder="' . esc_attr__('A short note is enough.', 'upkeepify') . '"></textarea>';
    echo '</div>';

    echo '<div class="upkeepify-field upkeepify-photo-field">';
    echo '<label for="task_photo">' . esc_html__('Add a photo', 'upkeepify') . '</label>';
    echo '<input type="file" id="task_photo" name="task_photo" accept="image/jpeg,image/png,image/gif,image/webp" capture="environment" class="upkeepify-file-input">';
    echo '</div>';

    echo '<div class="upkeepify-field upkeepify-geo-field">';
    echo '<input type="hidden" id="gps_latitude" name="gps_latitude" class="upkeepify-input">';
    echo '<input type="hidden" id="gps_longitude" name="gps_longitude" class="upkeepify-input">';
    echo '<button type="button" class="upkeepify-geo-button">' . esc_html__('Use my current location', 'upkeepify') . '</button>';
    echo '<span class="upkeepify-geo-status" role="status">' . esc_html__('Optional, useful for common areas.', 'upkeepify') . '</span>';
    echo '</div>';

    echo '<div class="upkeepify-field">';
    echo '<label for="submitter_email">' . esc_html__('Your email', 'upkeepify') . '</label>';
    echo '<input type="email" id="submitter_email" name="submitter_email" class="upkeepify-input" placeholder="' . esc_attr__('Optional', 'upkeepify') . '">';
    echo '<span class="upkeepify-field-hint">' . esc_html__('We can send you a link to confirm the job is done.', 'upkeepify') . '</span>';
    echo '</div>';

    echo '<div class="upkeepify-field upkeepify-math-field">';
    echo '<label for="math">' . esc_html(sprintf(__('What is %1$d + %2$d?', 'upkeepify'), $num1, $num2)) . '</label>';
    echo '<input type="text" id="math" name="math" required class="upkeepify-input" inputmode="numeric">';
    echo '</div>';

    echo '<p><input type="submit" name="upkeepify_task_submit" value="' . esc_attr__('Send request', 'upkeepify') . '" class="upkeepify-submit-button"></p>';

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
    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
    if ('POST' !== $request_method) {
        return;
    }

    if (!isset($_POST['math'], $_POST['upkeepify_upload'])) {
        return;
    }

    // Enforce the Allow Public Task Logging setting.
    $settings = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    if ( empty( $settings[ UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING ] ) ) {
        upkeepify_redirect_task_form_status( 'error', 'public_disabled' );
    }

    $task_submit_nonce = isset($_POST[UPKEEPIFY_NONCE_TASK_SUBMIT]) ? sanitize_text_field( wp_unslash( $_POST[UPKEEPIFY_NONCE_TASK_SUBMIT] ) ) : '';
    if ( ! $task_submit_nonce || ! wp_verify_nonce($task_submit_nonce, UPKEEPIFY_NONCE_ACTION_TASK_SUBMIT)) {
        upkeepify_redirect_task_form_status( 'error', 'security_failed' );
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_answer = isset($_POST['math']) ? sanitize_text_field( wp_unslash( $_POST['math'] ) ) : '';
    if (!isset($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT]) || intval($user_answer) !== intval($_SESSION[UPKEEPIFY_SESSION_MATH_RESULT])) {
        upkeepify_redirect_task_form_status( 'error', 'captcha_failed' );
    }

    $task_description = isset($_POST['task_description']) ? sanitize_textarea_field( wp_unslash( $_POST['task_description'] ) ) : '';

    $nearest_unit = isset($_POST['nearest_unit']) ? intval( wp_unslash( $_POST['nearest_unit'] ) ) : 1;
    $latitude = isset($_POST['gps_latitude']) ? sanitize_text_field( wp_unslash( $_POST['gps_latitude'] ) ) : '';
    $longitude = isset($_POST['gps_longitude']) ? sanitize_text_field( wp_unslash( $_POST['gps_longitude'] ) ) : '';

    $public_taxonomies = array( UPKEEPIFY_TAXONOMY_TASK_CATEGORY, UPKEEPIFY_TAXONOMY_TASK_TYPE );
    $submitted_terms = array();
    foreach ($public_taxonomies as $taxonomy) {
        $taxonomy_term = isset($_POST[$taxonomy]) ? sanitize_text_field( wp_unslash( $_POST[$taxonomy] ) ) : '';
        if (is_numeric($taxonomy_term)) {
            $term = get_term(intval($taxonomy_term), $taxonomy);
            if ($term && !is_wp_error($term) && isset($term->name)) {
                $submitted_terms[$taxonomy] = $term->name;
            }
        }
    }

    $task_title = isset($_POST['task_title']) ? sanitize_text_field( wp_unslash( $_POST['task_title'] ) ) : '';
    if ('' === $task_title) {
        $task_title = upkeepify_infer_task_title(
            $task_description,
            isset($submitted_terms[UPKEEPIFY_TAXONOMY_TASK_CATEGORY]) ? $submitted_terms[UPKEEPIFY_TAXONOMY_TASK_CATEGORY] : '',
            isset($submitted_terms[UPKEEPIFY_TAXONOMY_TASK_TYPE]) ? $submitted_terms[UPKEEPIFY_TAXONOMY_TASK_TYPE] : '',
            $nearest_unit
        );
    }

    // Handle file upload with scoped validation
    $photo_attachment_id = 0;
    if (isset($_FILES['task_photo']) && !empty($_FILES['task_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $task_photo_raw = wp_unslash( $_FILES['task_photo'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload data is sanitized below and validated before use.
        $task_photo     = array(
            'name'     => isset( $task_photo_raw['name'] ) ? sanitize_file_name( $task_photo_raw['name'] ) : '',
            'type'     => isset( $task_photo_raw['type'] ) ? sanitize_mime_type( $task_photo_raw['type'] ) : '',
            'tmp_name' => isset( $task_photo_raw['tmp_name'] ) ? sanitize_text_field( $task_photo_raw['tmp_name'] ) : '',
            'error'    => isset( $task_photo_raw['error'] ) ? intval( $task_photo_raw['error'] ) : UPLOAD_ERR_NO_FILE,
            'size'     => isset( $task_photo_raw['size'] ) ? absint( $task_photo_raw['size'] ) : 0,
        );

        // Validate upload using scoped validation
        $validation = upkeepify_validate_upload($task_photo);
        if (is_wp_error($validation)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Validation Error: ' . $validation->get_error_message());
            }
            upkeepify_redirect_task_form_status( 'error', 'upload_invalid' );
        }

        // Handle the upload
        $upload_result = wp_handle_upload($task_photo, array('test_form' => false));
        if (isset($upload_result['error'])) {
            if (WP_DEBUG) {
                error_log('Upkeepify Upload Error: ' . $upload_result['error']);
            }
            upkeepify_redirect_task_form_status( 'error', 'upload_invalid' );
        }

        // Prepare file data for media handling
        $file_data = array(
            'name'     => $task_photo['name'],
            'type'     => $upload_result['type'],
            'tmp_name' => $upload_result['file'],
            'error'    => $task_photo['error'],
            'size'     => $task_photo['size'],
        );

        // Sideload the file into the media library
        $attachment_id = media_handle_sideload($file_data, 0);

        if (is_wp_error($attachment_id)) {
            if (WP_DEBUG) {
                error_log('Upkeepify Media Sideload Error: ' . $attachment_id->get_error_message());
            }
            upkeepify_redirect_task_form_status( 'error', 'upload_invalid' );
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
        upkeepify_redirect_task_form_status( 'error', 'validation_failed' );
    }

    // Create as pending so an admin must review before provider invites fire.
    $task_id = wp_insert_post(
        array(
            'post_title' => $task_title,
            'post_content' => $task_description,
            'post_status' => 'pending',
            'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
            'meta_input' => $meta,
        ),
        true
    );

    if (is_wp_error($task_id)) {
        if (WP_DEBUG) {
            error_log('Upkeepify Task Submission Error: ' . $task_id->get_error_message());
        }
        upkeepify_redirect_task_form_status( 'error', 'save_failed' );
    }

    // Set the photo as the featured image if uploaded
    if ($photo_attachment_id > 0) {
        set_post_thumbnail($task_id, $photo_attachment_id);
    }

    // Only accept resident-facing taxonomies; never allow status or provider from public input.
    foreach ($public_taxonomies as $taxonomy) {
        $taxonomy_term = isset($_POST[$taxonomy]) ? sanitize_text_field( wp_unslash( $_POST[$taxonomy] ) ) : '';
        if (is_numeric($taxonomy_term)) {
            wp_set_object_terms($task_id, array(intval( $taxonomy_term )), $taxonomy);
        }
    }

    // Automatically assign the "Open" task status for all public submissions.
    $open_term = get_term_by( 'name', 'Open', UPKEEPIFY_TAXONOMY_TASK_STATUS );
    if ( $open_term && ! is_wp_error( $open_term ) ) {
        wp_set_object_terms( $task_id, array( $open_term->term_id ), UPKEEPIFY_TAXONOMY_TASK_STATUS );
    }

    // Save submitter email and generate resident confirmation token.
    $submitter_email = isset($_POST['submitter_email']) ? sanitize_email( wp_unslash( $_POST['submitter_email'] ) ) : '';
    if ( is_email( $submitter_email ) ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL, $submitter_email );
        $resident_token = wp_generate_password( 20, false );
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN, $resident_token );
    }

    upkeepify_redirect_task_form_status( 'success' );
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

    // Emailed invite links pass the token as a query parameter rather than a shortcode attribute.
    if ($token === '' && isset($_GET[UPKEEPIFY_QUERY_VAR_TOKEN])) {
        $token = sanitize_text_field(wp_unslash($_GET[UPKEEPIFY_QUERY_VAR_TOKEN]));
    }

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

        if (function_exists('upkeepify_is_provider_response_token_revoked') && upkeepify_is_provider_response_token_revoked($response_id)) {
            wp_reset_postdata();
            echo '<p>' . esc_html__('This response link has been revoked. Please contact the property manager for a new link.', 'upkeepify') . '</p>';
            return ob_get_clean();
        }

        $task_id  = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true ) );
        $task_post = $task_id ? get_post( $task_id ) : null;

        // Determine lifecycle state.
        $decision     = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION,      true );
        $formal_quote = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE,  true );
        $completed_at = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT,  true );
        $followup_status      = $task_id ? get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true ) : '';
        $followup_response_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, true ) ) : 0;
        $followup_for_this_response = (
            $followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE
            && $followup_response_id === intval( $response_id )
        );
        $followup_submitted_for_this_response = (
            $followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED
            && $followup_response_id === intval( $response_id )
        );

        // Currency symbol.
        $settings = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
        $currency = ! empty( $settings[ UPKEEPIFY_SETTING_CURRENCY ] ) ? $settings[ UPKEEPIFY_SETTING_CURRENCY ] : '$';

        // Redirect-back notice (shown after a successful form submission).
        if ( isset( $_GET['upkeepify_response'] ) ) {
            $resp_status = sanitize_key( $_GET['upkeepify_response'] );
            $notices = array(
                'submitted' => __( 'Your estimate has been submitted.', 'upkeepify' ),
                'quoted'    => __( 'Your formal quote has been submitted.', 'upkeepify' ),
                'completed' => __( 'Job marked as complete. The property manager will notify the resident.', 'upkeepify' ),
                'followup_submitted' => __( 'Your follow-up has been sent to the property manager for review.', 'upkeepify' ),
                'declined'  => __( 'You have declined this job. No further action needed.', 'upkeepify' ),
            );
            if ( isset( $notices[ $resp_status ] ) ) {
                $cls = ( $resp_status === 'declined' ) ? 'upkeepify-notice--info' : 'upkeepify-notice--success';
                echo '<p class="upkeepify-notice ' . esc_attr( $cls ) . '">' . esc_html( $notices[ $resp_status ] ) . '</p>';
            }
        }

        // ── Task detail card (shown on all active steps) ──────────────────────
        if ( $task_post && $decision !== 'decline' ) {
            echo '<div class="upkeepify-task-details">';
            echo '<h3>' . esc_html__( 'Job Details', 'upkeepify' ) . '</h3>';
            echo '<p><strong>' . esc_html( $task_post->post_title ) . '</strong></p>';
            echo '<p>' . nl2br( esc_html( wp_strip_all_tags( $task_post->post_content ) ) ) . '</p>';
            $thumb_id = get_post_thumbnail_id( $task_post->ID );
            if ( $thumb_id ) {
                echo '<p>' . wp_get_attachment_image( $thumb_id, 'medium' ) . '</p>';
            }
            $lat = get_post_meta( $task_post->ID, UPKEEPIFY_META_KEY_GPS_LATITUDE,  true );
            $lng = get_post_meta( $task_post->ID, UPKEEPIFY_META_KEY_GPS_LONGITUDE, true );
            if ( $lat && $lng ) {
                echo '<p><small>' . esc_html__( 'Location:', 'upkeepify' ) . ' ' . esc_html( $lat ) . ', ' . esc_html( $lng ) . '</small></p>';
            }
            echo '</div>';
        }

        // ── STATE MACHINE ─────────────────────────────────────────────────────

        if ( empty( $decision ) ) {

            // ── Step 2: initial estimate form ─────────────────────────────────
            // Expiry only checked on first visit (token not yet consumed).
            $expires = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES, true );
            if ( $expires && time() > intval( $expires ) ) {
                wp_reset_postdata();
                echo '<p>' . esc_html__( 'This invitation link has expired. Please contact the property manager to request a new one.', 'upkeepify' ) . '</p>';
                return ob_get_clean();
            }

            echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" class="upkeepify-estimate-form" id="upkeepify-estimate-form">';
            echo '<input type="hidden" name="action" value="' . esc_attr( UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT ) . '">';
            echo '<input type="hidden" name="response_id" value="' . esc_attr( $response_id ) . '">';
            echo '<input type="hidden" name="' . esc_attr( UPKEEPIFY_QUERY_VAR_TOKEN ) . '" value="' . esc_attr( $token ) . '">';
            wp_nonce_field( UPKEEPIFY_NONCE_ACTION_PROVIDER_RESPONSE, UPKEEPIFY_NONCE_PROVIDER_RESPONSE );

            echo '<fieldset class="upkeepify-fieldset">';
            echo '<legend>' . esc_html__( 'Can you take this job?', 'upkeepify' ) . '</legend>';
            echo '<label class="upkeepify-radio-label"><input type="radio" name="decision" value="accept" required id="upkeepify-decision-accept"> ' . esc_html__( 'Yes, I can take this job', 'upkeepify' ) . '</label>';
            echo '<label class="upkeepify-radio-label"><input type="radio" name="decision" value="decline" required id="upkeepify-decision-decline"> ' . esc_html__( 'No, pass on this one', 'upkeepify' ) . '</label>';
            echo '</fieldset>';

            echo '<div class="upkeepify-estimate-fields" id="upkeepify-estimate-fields">';

            echo '<p class="upkeepify-field">';
            echo '<label for="upkeepify-estimate"><strong>' . esc_html__( 'Ballpark estimate', 'upkeepify' ) . '</strong> <span class="upkeepify-required">*</span></label><br>';
            echo '<span class="upkeepify-currency-wrap"><span class="upkeepify-currency-symbol">' . esc_html( $currency ) . '</span>';
            echo '<input type="number" id="upkeepify-estimate" name="estimate" min="0" step="0.01" class="upkeepify-input upkeepify-input--currency" placeholder="0.00"></span>';
            echo '<br><small>' . esc_html__( 'Rough figure — not a formal quote. Your formal quote comes later.', 'upkeepify' ) . '</small>';
            echo '</p>';

            echo '<p class="upkeepify-field"><label>' . esc_html__( 'Estimate range (optional)', 'upkeepify' ) . '</label><br>';
            echo '<span class="upkeepify-range-wrap"><span class="upkeepify-currency-symbol">' . esc_html( $currency ) . '</span><input type="number" name="estimate_low" min="0" step="0.01" class="upkeepify-input upkeepify-input--range" placeholder="' . esc_attr__( 'Low', 'upkeepify' ) . '"></span>';
            echo ' &ndash; ';
            echo '<span class="upkeepify-range-wrap"><span class="upkeepify-currency-symbol">' . esc_html( $currency ) . '</span><input type="number" name="estimate_high" min="0" step="0.01" class="upkeepify-input upkeepify-input--range" placeholder="' . esc_attr__( 'High', 'upkeepify' ) . '"></span>';
            echo '</p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-confidence">' . esc_html__( 'Confidence in estimate (optional)', 'upkeepify' ) . '</label><br>';
            echo '<select name="estimate_confidence" id="upkeepify-confidence" class="upkeepify-select">';
            echo '<option value="">' . esc_html__( '— select —', 'upkeepify' ) . '</option>';
            echo '<option value="low">'    . esc_html__( 'Low — need to see it first',       'upkeepify' ) . '</option>';
            echo '<option value="medium">' . esc_html__( 'Medium — fairly confident',         'upkeepify' ) . '</option>';
            echo '<option value="high">'   . esc_html__( 'High — this is my standard rate',   'upkeepify' ) . '</option>';
            echo '</select></p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-availability">' . esc_html__( 'Earliest availability (optional)', 'upkeepify' ) . '</label><br>';
            echo '<input type="date" name="availability" id="upkeepify-availability" class="upkeepify-input" min="' . esc_attr( gmdate( 'Y-m-d' ) ) . '"></p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-note">' . esc_html__( 'Short note (optional)', 'upkeepify' ) . '</label><br>';
            echo '<textarea name="note" id="upkeepify-note" maxlength="500" rows="3" class="upkeepify-textarea" placeholder="' . esc_attr__( 'Any questions, conditions, or context for the property manager.', 'upkeepify' ) . '"></textarea>';
            echo '<br><small class="upkeepify-charcount" data-target="upkeepify-note" data-max="500">500 ' . esc_html__( 'characters remaining', 'upkeepify' ) . '</small></p>';

            echo '</div>'; // .upkeepify-estimate-fields

            echo '<p class="upkeepify-field"><input type="submit" value="' . esc_attr__( 'Submit response', 'upkeepify' ) . '" class="upkeepify-submit-button"></p>';
            echo '</form>';

            ?>
<script>
(function () {
    var form     = document.getElementById('upkeepify-estimate-form');
    var fields   = document.getElementById('upkeepify-estimate-fields');
    var estimate = document.getElementById('upkeepify-estimate');
    if (!form || !fields) return;
    function updateVisibility() {
        var accept = form.querySelector('input[name="decision"][value="accept"]');
        var show   = accept && accept.checked;
        fields.style.display = show ? '' : 'none';
        if (estimate) estimate.required = show;
    }
    form.querySelectorAll('input[name="decision"]').forEach(function (r) { r.addEventListener('change', updateVisibility); });
    upkeepifyCharCounters();
    updateVisibility();
}());
</script>
            <?php

        } elseif ( $decision === 'decline' ) {

            // ── Declined ──────────────────────────────────────────────────────
            echo '<p>' . esc_html__( 'You declined this job. Contact the property manager if you have changed your mind.', 'upkeepify' ) . '</p>';

        } elseif ( $formal_quote === '' ) {

            // ── Step 3a: formal quote ─────────────────────────────────────────
            $ballpark = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true );
            $approved_estimate_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true ) ) : 0;
            echo '<p class="upkeepify-step-label">' . esc_html__( 'Step 2 of 3 — Formal Quote', 'upkeepify' ) . '</p>';
            if ( $approved_estimate_id !== intval( $response_id ) ) {
                if ( $approved_estimate_id ) {
                    echo '<p class="upkeepify-notice upkeepify-notice--info">' . esc_html__( 'Another contractor estimate has been approved for this job. Contact the property manager if you think this is incorrect.', 'upkeepify' ) . '</p>';
                } else {
                    echo '<p class="upkeepify-notice upkeepify-notice--info">' . esc_html__( 'Your estimate has been submitted. The property manager needs to approve it before you can submit a formal quote.', 'upkeepify' ) . '</p>';
                }
                wp_reset_postdata();
                return ob_get_clean();
            }

            if ( $ballpark !== '' ) {
                echo '<p><small>' . sprintf(
                    /* translators: 1: currency, 2: ballpark amount */
                    esc_html__( 'Your ballpark estimate was %1$s%2$s. Your formal quote should be a firm figure you are prepared to stand behind.', 'upkeepify' ),
                    esc_html( $currency ),
                    esc_html( number_format( (float) $ballpark, 2 ) )
                ) . '</small></p>';
            }

            echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" enctype="multipart/form-data" class="upkeepify-estimate-form">';
            echo '<input type="hidden" name="action" value="' . esc_attr( UPKEEPIFY_ADMIN_ACTION_PROVIDER_QUOTE_SUBMIT ) . '">';
            echo '<input type="hidden" name="response_id" value="' . esc_attr( $response_id ) . '">';
            echo '<input type="hidden" name="' . esc_attr( UPKEEPIFY_QUERY_VAR_TOKEN ) . '" value="' . esc_attr( $token ) . '">';
            wp_nonce_field( UPKEEPIFY_NONCE_ACTION_PROVIDER_QUOTE, UPKEEPIFY_NONCE_PROVIDER_QUOTE );

            echo '<p class="upkeepify-field"><label for="upkeepify-quote"><strong>' . esc_html__( 'Formal quote', 'upkeepify' ) . '</strong> <span class="upkeepify-required">*</span></label><br>';
            echo '<span class="upkeepify-currency-wrap"><span class="upkeepify-currency-symbol">' . esc_html( $currency ) . '</span>';
            echo '<input type="number" id="upkeepify-quote" name="formal_quote" min="0" step="0.01" required class="upkeepify-input upkeepify-input--currency" placeholder="0.00"></span></p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-quote-note">' . esc_html__( 'Conditions or scope notes (optional)', 'upkeepify' ) . '</label><br>';
            echo '<textarea name="quote_note" id="upkeepify-quote-note" maxlength="500" rows="3" class="upkeepify-textarea" placeholder="' . esc_attr__( 'Any conditions, exclusions, or scope clarifications.', 'upkeepify' ) . '"></textarea>';
            echo '<br><small class="upkeepify-charcount" data-target="upkeepify-quote-note" data-max="500">500 ' . esc_html__( 'characters remaining', 'upkeepify' ) . '</small></p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-quote-documents">' . esc_html__( 'Quote document (optional)', 'upkeepify' ) . '</label><br>';
            echo '<input type="file" id="upkeepify-quote-documents" name="quote_documents[]" accept="application/pdf,image/jpeg,image/png,image/gif" multiple class="upkeepify-file-input">';
            echo '<br><small>' . esc_html__( 'PDF, JPG, PNG or GIF, up to 3 files, max 5 MB each. Uploaded quote files are included in the approval audit pack.', 'upkeepify' ) . '</small></p>';

            echo '<p class="upkeepify-field"><input type="submit" value="' . esc_attr__( 'Submit formal quote', 'upkeepify' ) . '" class="upkeepify-submit-button"></p>';
            echo '</form>';
            ?><script>upkeepifyCharCounters();</script><?php

        } elseif ( $completed_at === '' || $followup_for_this_response ) {

            // ── Step 3b: completion proof ─────────────────────────────────────
            $quote_val = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true );
            $approved_quote_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, true ) ) : 0;
            echo '<p class="upkeepify-step-label">' . ( $followup_for_this_response
                ? esc_html__( 'Follow-up Requested', 'upkeepify' )
                : esc_html__( 'Step 3 of 3 — Mark Job Complete', 'upkeepify' ) ) . '</p>';
            if ( $followup_for_this_response ) {
                $resident_note = $task_id ? get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, true ) : '';
                echo '<p class="upkeepify-notice upkeepify-notice--info">' . esc_html__( 'The resident reported an issue after completion. Submit what you changed or clarified for trustee review.', 'upkeepify' ) . '</p>';
                if ( $resident_note ) {
                    echo '<p><strong>' . esc_html__( 'Resident comment:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $resident_note ) ) . '</p>';
                }
            }
            if ( $quote_val !== '' ) {
                echo '<p><small>' . sprintf(
                    /* translators: 1: currency, 2: quote amount */
                    esc_html__( 'Your formal quote was %1$s%2$s.', 'upkeepify' ),
                    esc_html( $currency ),
                    esc_html( number_format( (float) $quote_val, 2 ) )
                ) . '</small></p>';
            }

            if ( ! $followup_for_this_response && $approved_quote_id !== intval( $response_id ) ) {
                if ( $approved_quote_id ) {
                    echo '<p class="upkeepify-notice upkeepify-notice--info">' . esc_html__( 'Another formal quote has been approved for this job. Contact the property manager if you think this is incorrect.', 'upkeepify' ) . '</p>';
                } else {
                    echo '<p class="upkeepify-notice upkeepify-notice--info">' . esc_html__( 'Your formal quote has been submitted. The property manager needs to approve it before you can mark the job complete.', 'upkeepify' ) . '</p>';
                }
                wp_reset_postdata();
                return ob_get_clean();
            }

            echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" enctype="multipart/form-data" class="upkeepify-estimate-form">';
            echo '<input type="hidden" name="action" value="' . esc_attr( UPKEEPIFY_ADMIN_ACTION_PROVIDER_COMPLETION_SUBMIT ) . '">';
            echo '<input type="hidden" name="response_id" value="' . esc_attr( $response_id ) . '">';
            echo '<input type="hidden" name="' . esc_attr( UPKEEPIFY_QUERY_VAR_TOKEN ) . '" value="' . esc_attr( $token ) . '">';
            wp_nonce_field( UPKEEPIFY_NONCE_ACTION_PROVIDER_COMPLETION, UPKEEPIFY_NONCE_PROVIDER_COMPLETION );

            echo '<p class="upkeepify-field"><label for="upkeepify-completion-photos">' . ( $followup_for_this_response
                ? esc_html__( 'Follow-up photos (optional, up to 3)', 'upkeepify' )
                : esc_html__( 'Completion photos (optional, up to 3)', 'upkeepify' ) ) . '</label><br>';
            echo '<input type="file" id="upkeepify-completion-photos" name="completion_photos[]" accept="image/*" capture="environment" multiple class="upkeepify-file-input">';
            echo '<br><small>' . esc_html__( 'JPG, PNG or GIF, max 2 MB each.', 'upkeepify' ) . '</small></p>';

            echo '<p class="upkeepify-field"><label for="upkeepify-completion-note">' . ( $followup_for_this_response
                ? esc_html__( 'Follow-up note (optional)', 'upkeepify' )
                : esc_html__( 'Completion note (optional)', 'upkeepify' ) ) . '</label><br>';
            echo '<textarea name="completion_note" id="upkeepify-completion-note" maxlength="500" rows="3" class="upkeepify-textarea" placeholder="' . ( $followup_for_this_response
                ? esc_attr__( 'What was fixed, checked, or clarified for the resident and trustee.', 'upkeepify' )
                : esc_attr__( 'Brief description of work completed, any follow-up needed, etc.', 'upkeepify' ) ) . '"></textarea>';
            echo '<br><small class="upkeepify-charcount" data-target="upkeepify-completion-note" data-max="500">500 ' . esc_html__( 'characters remaining', 'upkeepify' ) . '</small></p>';

            echo '<p class="upkeepify-field"><input type="submit" value="' . ( $followup_for_this_response
                ? esc_attr__( 'Submit follow-up', 'upkeepify' )
                : esc_attr__( 'Mark job as complete', 'upkeepify' ) ) . '" class="upkeepify-submit-button"></p>';
            echo '</form>';
            ?><script>upkeepifyCharCounters();</script><?php

        } elseif ( $followup_submitted_for_this_response ) {

            echo '<p class="upkeepify-notice upkeepify-notice--success">' . esc_html__( 'Your follow-up has been sent to the property manager. They will review the resident issue before closing the job.', 'upkeepify' ) . '</p>';

        } else {

            // ── All done — awaiting resident confirmation ──────────────────────
            echo '<p class="upkeepify-notice upkeepify-notice--success">' . esc_html__( 'Job marked as complete. The resident will be asked to confirm and the property manager has been notified. Thank you.', 'upkeepify' ) . '</p>';

        }

        // Shared char-counter utility (called per-form above).
        ?>
<script>
function upkeepifyCharCounters() {
    document.querySelectorAll('.upkeepify-charcount[data-target]').forEach(function (el) {
        var target = document.getElementById(el.getAttribute('data-target'));
        var max    = parseInt(el.getAttribute('data-max'), 10);
        if (!target || isNaN(max)) return;
        target.addEventListener('input', function () {
            var remaining = max - target.value.length;
            el.textContent = remaining + ' <?php echo esc_js( __( 'characters remaining', 'upkeepify' ) ); ?>';
            el.style.color = remaining < 50 ? '#c00' : '';
        });
    });
}
</script>
        <?php

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

/**
 * Admin-post handler: submit provider response.
 *
 * Processes provider response form submissions with CSRF protection via nonce verification.
 *
 * @since 1.0
 * @uses wp_verify_nonce()
 * @uses get_post_meta()
 * @uses update_post_meta()
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT}
 */
function upkeepify_admin_post_provider_response_submit() {
    // CSRF check.
    $provider_response_nonce = isset( $_POST[ UPKEEPIFY_NONCE_PROVIDER_RESPONSE ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_NONCE_PROVIDER_RESPONSE ] ) ) : '';
    if ( ! $provider_response_nonce ||
        ! wp_verify_nonce( $provider_response_nonce, UPKEEPIFY_NONCE_ACTION_PROVIDER_RESPONSE ) ) {
        wp_die( esc_html__( 'Security check failed.', 'upkeepify' ) );
    }

    if ( ! isset( $_POST['response_id'], $_POST['decision'] ) ) {
        wp_die( esc_html__( 'Missing required fields.', 'upkeepify' ) );
    }

    $response_id = isset( $_POST['response_id'] ) ? absint( wp_unslash( $_POST['response_id'] ) ) : 0;
    $decision    = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';

    if ( ! in_array( $decision, array( 'accept', 'decline' ), true ) ) {
        wp_die( esc_html__( 'Invalid decision value.', 'upkeepify' ) );
    }

    // Verify the response post.
    $response_post = get_post( $response_id );
    if ( ! $response_post || $response_post->post_type !== UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ) {
        wp_die( esc_html__( 'Invalid response post.', 'upkeepify' ) );
    }

    // Prevent double-submission.
    $existing = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, true );
    if ( ! empty( $existing ) ) {
        wp_die( esc_html__( 'This response has already been submitted.', 'upkeepify' ) );
    }

    // Verify the token matches (prevents response_id tampering).
    $stored_token = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true );
    $posted_token = isset( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ) : '';
    $token_matches = function_exists( 'upkeepify_provider_response_token_matches' )
        ? upkeepify_provider_response_token_matches( $response_id, $posted_token )
        : ( ! empty( $stored_token ) && hash_equals( $stored_token, $posted_token ) );
    if ( ! $token_matches ) {
        wp_die( esc_html__( 'Token mismatch. Please use the original invitation link.', 'upkeepify' ) );
    }

    // Check expiry.
    $expires = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES, true );
    if ( $expires && time() > intval( $expires ) ) {
        wp_die( esc_html__( 'This invitation link has expired.', 'upkeepify' ) );
    }

    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION, $decision );

    if ( $decision === 'decline' ) {
        // Record the decline and close; no estimate data needed.
        wp_update_post( array(
            'ID'          => $response_id,
            'post_status' => 'publish',
        ) );

        $redirect = add_query_arg(
            array( 'upkeepify_response' => 'declined' ),
            wp_get_referer() ?: home_url()
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    // ── Accept path: validate and save estimate fields ────────────────────────

    if ( ! isset( $_POST['estimate'] ) || $_POST['estimate'] === '' ) {
        wp_die( esc_html__( 'A ballpark estimate is required when accepting a job.', 'upkeepify' ) );
    }

    $estimate = filter_input( INPUT_POST, 'estimate', FILTER_VALIDATE_FLOAT );
    if ( $estimate === false || $estimate < 0 ) {
        wp_die( esc_html__( 'Please enter a valid estimate amount.', 'upkeepify' ) );
    }

    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, $estimate );

    // Optional range.
    $est_low  = filter_input( INPUT_POST, 'estimate_low',  FILTER_VALIDATE_FLOAT );
    $est_high = filter_input( INPUT_POST, 'estimate_high', FILTER_VALIDATE_FLOAT );
    if ( $est_low !== false && $est_low !== null && $est_low >= 0 ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_LOW, $est_low );
    }
    if ( $est_high !== false && $est_high !== null && $est_high >= 0 ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_HIGH, $est_high );
    }

    // Optional confidence.
    $confidence = isset( $_POST['estimate_confidence'] ) ? sanitize_key( wp_unslash( $_POST['estimate_confidence'] ) ) : '';
    if ( in_array( $confidence, array( 'low', 'medium', 'high' ), true ) ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE_CONFIDENCE, $confidence );
    }

    // Optional availability date (Y-m-d).
    $availability = isset( $_POST['availability'] ) ? sanitize_text_field( wp_unslash( $_POST['availability'] ) ) : '';
    if ( $availability && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $availability ) ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_AVAILABILITY, $availability );
    }

    // Optional note (max 500 chars).
    $note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
    if ( $note !== '' ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_NOTE, substr( $note, 0, 500 ) );
    }

    wp_update_post( array(
        'ID'          => $response_id,
        'post_status' => 'publish',
    ) );

    $task_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true ) );
    $task_post = $task_id ? get_post( $task_id ) : null;
    if ( $task_post ) {
        if ( function_exists( 'upkeepify_sync_task_lifecycle_status' ) ) {
            upkeepify_sync_task_lifecycle_status( $task_id );
        }

        $settings      = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
        $currency      = ! empty( $settings[ UPKEEPIFY_SETTING_CURRENCY ] ) ? $settings[ UPKEEPIFY_SETTING_CURRENCY ] : '$';
        $recipient     = ! empty( $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] ) ? $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] : get_option( 'admin_email' );
        $provider_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
        $provider_term = $provider_id ? get_term( $provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ) : null;
        $provider_name = ( $provider_term && ! is_wp_error( $provider_term ) ) ? $provider_term->name : __( 'A contractor', 'upkeepify' );
        $review_url    = admin_url( 'post.php?post=' . intval( $task_id ) . '&action=edit' );

        $subject = sprintf( __( '[%s] Estimate received — %s', 'upkeepify' ), get_bloginfo( 'name' ), $task_post->post_title );
        $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
        $body   .= '<h2>' . esc_html__( 'Estimate Received', 'upkeepify' ) . '</h2>';
        $body   .= '<p>' . sprintf( esc_html__( '%s has submitted an estimate for "%s".', 'upkeepify' ), esc_html( $provider_name ), esc_html( $task_post->post_title ) ) . '</p>';
        $body   .= '<p><strong>' . sprintf( esc_html__( 'Ballpark estimate: %1$s%2$s', 'upkeepify' ), esc_html( $currency ), esc_html( number_format( (float) $estimate, 2 ) ) ) . '</strong></p>';
        if ( $note !== '' ) {
            $body .= '<p><strong>' . esc_html__( 'Contractor note:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
        }
        $body .= '<p>' . esc_html__( 'Review the Trustee Lifecycle panel and approve the estimate before the contractor can submit a formal quote.', 'upkeepify' ) . '</p>';
        $body .= '<p><a href="' . esc_url( $review_url ) . '">' . esc_html__( 'Review estimate', 'upkeepify' ) . '</a></p>';
        $body .= '</div>';

        wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    $redirect = add_query_arg(
        array( 'upkeepify_response' => 'submitted' ),
        wp_get_referer() ?: home_url()
    );
    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_'        . UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT, 'upkeepify_admin_post_provider_response_submit' );
add_action( 'admin_post_nopriv_' . UPKEEPIFY_ADMIN_ACTION_PROVIDER_RESPONSE_SUBMIT, 'upkeepify_admin_post_provider_response_submit' );

// ─── Step 3a: Formal quote submission ────────────────────────────────────────

/**
 * Handle optional formal quote document uploads.
 *
 * @since 1.1
 * @param int $response_id Provider response post ID to attach documents to.
 * @return int[]|WP_Error Attachment IDs, or WP_Error on upload failure.
 */
function upkeepify_handle_quote_document_uploads( $response_id ) {
    if ( empty( $_FILES['quote_documents'] ) || empty( $_FILES['quote_documents']['name'] ) ) {
        return array();
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $files = wp_unslash( $_FILES['quote_documents'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload data is normalized per item below and validated before use.
    if ( ! is_array( $files['name'] ) ) {
        $files = array(
            'name'     => array( $files['name'] ),
            'type'     => array( isset( $files['type'] ) ? $files['type'] : '' ),
            'tmp_name' => array( isset( $files['tmp_name'] ) ? $files['tmp_name'] : '' ),
            'error'    => array( isset( $files['error'] ) ? $files['error'] : UPLOAD_ERR_NO_FILE ),
            'size'     => array( isset( $files['size'] ) ? $files['size'] : 0 ),
        );
    }

    $attachment_ids = array();
    $count          = min( count( $files['name'] ), 3 );

    for ( $i = 0; $i < $count; $i++ ) {
        if ( empty( $files['name'][ $i ] ) && intval( $files['error'][ $i ] ) === UPLOAD_ERR_NO_FILE ) {
            continue;
        }

        $single_file = array(
            'name'     => sanitize_file_name( $files['name'][ $i ] ),
            'type'     => sanitize_mime_type( $files['type'][ $i ] ),
            'tmp_name' => sanitize_text_field( $files['tmp_name'][ $i ] ),
            'error'    => intval( $files['error'][ $i ] ),
            'size'     => absint( $files['size'][ $i ] ),
        );

        $validation = upkeepify_validate_quote_document_upload( $single_file );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $upload = wp_handle_upload( $single_file, array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'quote_upload_failed', $upload['error'] );
        }

        $sideload_data = array(
            'name'     => $single_file['name'],
            'type'     => $upload['type'],
            'tmp_name' => $upload['file'],
            'error'    => UPLOAD_ERR_OK,
            'size'     => $single_file['size'],
        );

        $attachment_id = media_handle_sideload( $sideload_data, $response_id );
        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        $attachment_ids[] = intval( $attachment_id );
    }

    return $attachment_ids;
}

/**
 * Handle contractor formal quote form submission.
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_QUOTE_SUBMIT}
 * @hook admin_post_nopriv_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_QUOTE_SUBMIT}
 */
function upkeepify_admin_post_provider_quote_submit() {
    $provider_quote_nonce = isset( $_POST[ UPKEEPIFY_NONCE_PROVIDER_QUOTE ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_NONCE_PROVIDER_QUOTE ] ) ) : '';
    if ( ! $provider_quote_nonce ||
        ! wp_verify_nonce( $provider_quote_nonce, UPKEEPIFY_NONCE_ACTION_PROVIDER_QUOTE ) ) {
        wp_die( esc_html__( 'Security check failed.', 'upkeepify' ) );
    }

    if ( ! isset( $_POST['response_id'], $_POST['formal_quote'] ) ) {
        wp_die( esc_html__( 'Missing required fields.', 'upkeepify' ) );
    }

    $response_id  = isset( $_POST['response_id'] ) ? absint( wp_unslash( $_POST['response_id'] ) ) : 0;
    $response_post = get_post( $response_id );

    if ( ! $response_post || $response_post->post_type !== UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ) {
        wp_die( esc_html__( 'Invalid response post.', 'upkeepify' ) );
    }

    // Token verification.
    $stored_token = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true );
    $posted_token = isset( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ) : '';
    $token_matches = function_exists( 'upkeepify_provider_response_token_matches' )
        ? upkeepify_provider_response_token_matches( $response_id, $posted_token )
        : ( ! empty( $stored_token ) && hash_equals( $stored_token, $posted_token ) );
    if ( ! $token_matches ) {
        wp_die( esc_html__( 'Token mismatch.', 'upkeepify' ) );
    }

    // Must have accepted in Step 2 and not yet submitted a quote.
    $decision     = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION,     true );
    $formal_quote = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true );
    if ( $decision !== 'accept' ) {
        wp_die( esc_html__( 'This job was not accepted.', 'upkeepify' ) );
    }
    if ( $formal_quote !== '' ) {
        wp_die( esc_html__( 'A formal quote has already been submitted.', 'upkeepify' ) );
    }

    $task_id = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true ) );
    $approved_estimate_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true ) ) : 0;
    if ( $approved_estimate_id !== intval( $response_id ) ) {
        wp_die( esc_html__( 'Your estimate must be approved before you can submit a formal quote.', 'upkeepify' ) );
    }

    $quote = filter_input( INPUT_POST, 'formal_quote', FILTER_VALIDATE_FLOAT );
    if ( $quote === false || $quote < 0 ) {
        wp_die( esc_html__( 'Please enter a valid quote amount.', 'upkeepify' ) );
    }

    $quote_attachment_ids = upkeepify_handle_quote_document_uploads( $response_id );
    if ( is_wp_error( $quote_attachment_ids ) ) {
        wp_die( esc_html( $quote_attachment_ids->get_error_message() ) );
    }

    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, $quote );
    if ( ! empty( $quote_attachment_ids ) ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_ATTACHMENTS, $quote_attachment_ids );
    }

    $quote_note = isset( $_POST['quote_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['quote_note'] ) ) : '';
    if ( $quote_note !== '' ) {
        update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_QUOTE_NOTE, substr( $quote_note, 0, 500 ) );
    }

    // Notify trustee of the formal quote.
    $task_post = $task_id ? get_post( $task_id ) : null;
    if ( $task_post ) {
        if ( function_exists( 'upkeepify_sync_task_lifecycle_status' ) ) {
            upkeepify_sync_task_lifecycle_status( $task_id );
        }

        $settings  = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
        $currency  = ! empty( $settings[ UPKEEPIFY_SETTING_CURRENCY ] ) ? $settings[ UPKEEPIFY_SETTING_CURRENCY ] : '$';
        $recipient = ! empty( $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] ) ? $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] : get_option( 'admin_email' );
        $provider_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
        $provider_term = $provider_id ? get_term( $provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ) : null;
        $provider_name = ( $provider_term && ! is_wp_error( $provider_term ) ) ? $provider_term->name : __( 'A contractor', 'upkeepify' );

        $ballpark = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE, true );

        $subject = sprintf( __( '[%s] Formal quote received — %s', 'upkeepify' ), get_bloginfo( 'name' ), $task_post->post_title );
        $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
        $body   .= '<h2>' . esc_html__( 'Formal Quote Received', 'upkeepify' ) . '</h2>';
        $body   .= '<p>' . sprintf( esc_html__( '%s has submitted a formal quote for "%s".', 'upkeepify' ), esc_html( $provider_name ), esc_html( $task_post->post_title ) ) . '</p>';
        if ( $ballpark !== '' ) {
            $body .= '<p>' . sprintf( esc_html__( 'Ballpark estimate: %1$s%2$s', 'upkeepify' ), esc_html( $currency ), esc_html( number_format( (float) $ballpark, 2 ) ) ) . '</p>';
        }
        $body .= '<p><strong>' . sprintf( esc_html__( 'Formal quote: %1$s%2$s', 'upkeepify' ), esc_html( $currency ), esc_html( number_format( $quote, 2 ) ) ) . '</strong></p>';
        if ( $quote_note ) {
            $body .= '<p>' . esc_html__( 'Notes:', 'upkeepify' ) . ' ' . nl2br( esc_html( $quote_note ) ) . '</p>';
        }
        if ( ! empty( $quote_attachment_ids ) ) {
            $body .= '<p>' . sprintf( esc_html__( '%d quote document(s) uploaded for audit.', 'upkeepify' ), count( $quote_attachment_ids ) ) . '</p>';
        }
        $body .= '<p>' . esc_html__( 'Review this quote in the WordPress admin.', 'upkeepify' ) . '</p>';
        $body .= '</div>';

        wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    wp_safe_redirect( add_query_arg( 'upkeepify_response', 'quoted', wp_get_referer() ?: home_url() ) );
    exit;
}
add_action( 'admin_post_'        . UPKEEPIFY_ADMIN_ACTION_PROVIDER_QUOTE_SUBMIT, 'upkeepify_admin_post_provider_quote_submit' );
add_action( 'admin_post_nopriv_' . UPKEEPIFY_ADMIN_ACTION_PROVIDER_QUOTE_SUBMIT, 'upkeepify_admin_post_provider_quote_submit' );

// ─── Step 3b: Completion proof submission ─────────────────────────────────────

/**
 * Handle contractor completion proof form submission.
 *
 * Saves optional completion photos and note, timestamps the completion,
 * and notifies the trustee so they can trigger resident confirmation (Step 4).
 *
 * @since 1.1
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_COMPLETION_SUBMIT}
 * @hook admin_post_nopriv_{UPKEEPIFY_ADMIN_ACTION_PROVIDER_COMPLETION_SUBMIT}
 */
function upkeepify_admin_post_provider_completion_submit() {
    $provider_completion_nonce = isset( $_POST[ UPKEEPIFY_NONCE_PROVIDER_COMPLETION ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_NONCE_PROVIDER_COMPLETION ] ) ) : '';
    if ( ! $provider_completion_nonce ||
        ! wp_verify_nonce( $provider_completion_nonce, UPKEEPIFY_NONCE_ACTION_PROVIDER_COMPLETION ) ) {
        wp_die( esc_html__( 'Security check failed.', 'upkeepify' ) );
    }

    if ( ! isset( $_POST['response_id'] ) ) {
        wp_die( esc_html__( 'Missing required fields.', 'upkeepify' ) );
    }

    $response_id   = isset( $_POST['response_id'] ) ? absint( wp_unslash( $_POST['response_id'] ) ) : 0;
    $response_post = get_post( $response_id );

    if ( ! $response_post || $response_post->post_type !== UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ) {
        wp_die( esc_html__( 'Invalid response post.', 'upkeepify' ) );
    }

    // Token verification.
    $stored_token = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true );
    $posted_token = isset( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_QUERY_VAR_TOKEN ] ) ) : '';
    $token_matches = function_exists( 'upkeepify_provider_response_token_matches' )
        ? upkeepify_provider_response_token_matches( $response_id, $posted_token )
        : ( ! empty( $stored_token ) && hash_equals( $stored_token, $posted_token ) );
    if ( ! $token_matches ) {
        wp_die( esc_html__( 'Token mismatch.', 'upkeepify' ) );
    }

    // Must be in quoted state.
    $decision     = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_DECISION,     true );
    $formal_quote = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, true );
    $completed_at = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT, true );
    $task_id      = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true ) );
    $followup_status      = $task_id ? get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true ) : '';
    $followup_response_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, true ) ) : 0;
    $is_followup_completion = (
        $completed_at !== ''
        && $followup_status === UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE
        && $followup_response_id === intval( $response_id )
    );

    if ( $decision !== 'accept' || $formal_quote === '' ) {
        wp_die( esc_html__( 'Please submit your formal quote before marking the job complete.', 'upkeepify' ) );
    }
    if ( ! $is_followup_completion ) {
        $approved_quote_id = $task_id ? intval( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, true ) ) : 0;
        if ( $approved_quote_id !== intval( $response_id ) ) {
            wp_die( esc_html__( 'Your formal quote must be approved before you can mark the job complete.', 'upkeepify' ) );
        }
    }
    if ( $completed_at !== '' && ! $is_followup_completion ) {
        wp_die( esc_html__( 'This job has already been marked as complete.', 'upkeepify' ) );
    }
    if ( $is_followup_completion && get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT, true ) !== '' ) {
        wp_die( esc_html__( 'A follow-up has already been submitted for this resident issue.', 'upkeepify' ) );
    }

    // Process completion photos (up to 3).
    $attachment_ids = array();
    if ( ! empty( $_FILES['completion_photos']['name'][0] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $files = wp_unslash( $_FILES['completion_photos'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload data is normalized per item below and validated before use.
        $count = min( count( $files['name'] ), 3 ); // cap at 3

        for ( $i = 0; $i < $count; $i++ ) {
            if ( empty( $files['name'][ $i ] ) || $files['error'][ $i ] !== UPLOAD_ERR_OK ) {
                continue;
            }

            $single_file = array(
                'name'     => sanitize_file_name( $files['name'][ $i ] ),
                'type'     => sanitize_mime_type( $files['type'][ $i ] ),
                'tmp_name' => sanitize_text_field( $files['tmp_name'][ $i ] ),
                'error'    => intval( $files['error'][ $i ] ),
                'size'     => absint( $files['size'][ $i ] ),
            );

            $validation = upkeepify_validate_upload( $single_file );
            if ( is_wp_error( $validation ) ) {
                if ( WP_DEBUG ) {
                    error_log( 'Upkeepify Completion Upload: ' . $validation->get_error_message() );
                }
                continue;
            }

            $upload = wp_handle_upload( $single_file, array( 'test_form' => false ) );
            if ( isset( $upload['error'] ) ) {
                if ( WP_DEBUG ) {
                    error_log( 'Upkeepify Completion Upload Error: ' . $upload['error'] );
                }
                continue;
            }

            $sideload_data = array(
                'name'     => $single_file['name'],
                'type'     => $upload['type'],
                'tmp_name' => $upload['file'],
                'error'    => UPLOAD_ERR_OK,
                'size'     => $single_file['size'],
            );

            $attachment_id = media_handle_sideload( $sideload_data, $response_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                $attachment_ids[] = $attachment_id;
            }
        }
    }

    $completion_note = isset( $_POST['completion_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['completion_note'] ) ) : '';

    $photos_meta_key    = $is_followup_completion ? UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_PHOTOS : UPKEEPIFY_META_KEY_RESPONSE_COMPLETION_PHOTOS;
    $note_meta_key      = $is_followup_completion ? UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_NOTE : UPKEEPIFY_META_KEY_RESPONSE_COMPLETION_NOTE;
    $completed_meta_key = $is_followup_completion ? UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT : UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT;

    if ( ! empty( $attachment_ids ) ) {
        update_post_meta( $response_id, $photos_meta_key, $attachment_ids );
    }

    if ( $completion_note !== '' ) {
        update_post_meta( $response_id, $note_meta_key, substr( $completion_note, 0, 500 ) );
    }

    update_post_meta( $response_id, $completed_meta_key, time() );
    if ( $is_followup_completion ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED );
    }
    if ( $task_id && function_exists( 'upkeepify_sync_task_lifecycle_status' ) ) {
        upkeepify_sync_task_lifecycle_status( $task_id );
    }

    // Notify trustee — resident confirmation (Step 4) will be triggered from admin.
    $task_post = $task_id ? get_post( $task_id ) : null;
    if ( $task_post ) {
        $settings      = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
        $recipient     = ! empty( $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] ) ? $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] : get_option( 'admin_email' );
        $provider_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
        $provider_term = $provider_id ? get_term( $provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ) : null;
        $provider_name = ( $provider_term && ! is_wp_error( $provider_term ) ) ? $provider_term->name : __( 'A contractor', 'upkeepify' );

        if ( $is_followup_completion ) {
            $resident_note = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, true );
            $subject = sprintf( __( '[%s] Contractor follow-up submitted: %s', 'upkeepify' ), get_bloginfo( 'name' ), $task_post->post_title );
            $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
            $body   .= '<h2>' . esc_html__( 'Contractor Follow-up Submitted', 'upkeepify' ) . '</h2>';
            $body   .= '<p>' . sprintf( esc_html__( '%s has submitted a follow-up for "%s" after the resident reported an issue.', 'upkeepify' ), esc_html( $provider_name ), esc_html( $task_post->post_title ) ) . '</p>';
            if ( $resident_note ) {
                $body .= '<p><strong>' . esc_html__( 'Resident comment:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $resident_note ) ) . '</p>';
            }
            if ( $completion_note ) {
                $body .= '<p><strong>' . esc_html__( 'Contractor follow-up:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $completion_note ) ) . '</p>';
            }
            if ( ! empty( $attachment_ids ) ) {
                $body .= '<p>' . sprintf( esc_html__( '%d follow-up photo(s) uploaded.', 'upkeepify' ), count( $attachment_ids ) ) . '</p>';
            }
            $body .= '<p>' . esc_html__( 'Review the resident issue and contractor follow-up before closing the job or requesting confirmation again.', 'upkeepify' ) . '</p>';
            $body .= '</div>';
        } else {
            $subject = sprintf( __( '[%s] Job complete — resident confirmation needed: %s', 'upkeepify' ), get_bloginfo( 'name' ), $task_post->post_title );
            $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
            $body   .= '<h2>' . esc_html__( 'Job Marked Complete', 'upkeepify' ) . '</h2>';
            $body   .= '<p>' . sprintf( esc_html__( '%s has marked "%s" as complete.', 'upkeepify' ), esc_html( $provider_name ), esc_html( $task_post->post_title ) ) . '</p>';
            if ( $completion_note ) {
                $body .= '<p>' . esc_html__( 'Completion note:', 'upkeepify' ) . ' ' . nl2br( esc_html( $completion_note ) ) . '</p>';
            }
            if ( ! empty( $attachment_ids ) ) {
                $body .= '<p>' . sprintf( esc_html__( '%d completion photo(s) uploaded.', 'upkeepify' ), count( $attachment_ids ) ) . '</p>';
            }
            // Include the resident confirmation link in the trustee email if available.
            $confirmation_url = upkeepify_get_resident_confirmation_url( $task_id );
            if ( $confirmation_url ) {
                $body .= '<p><strong>' . esc_html__( 'Next step:', 'upkeepify' ) . '</strong> ';
                $body .= esc_html__( 'The resident confirmation email has been sent automatically.', 'upkeepify' ) . '</p>';
                $body .= '<p>' . esc_html__( 'Resident confirmation link (for reference):', 'upkeepify' ) . '<br>';
                $body .= '<a href="' . esc_url( $confirmation_url ) . '">' . esc_url( $confirmation_url ) . '</a></p>';
            } else {
                $body .= '<p><strong>' . esc_html__( 'Next step:', 'upkeepify' ) . '</strong> ' . esc_html__( 'No resident email on file — confirmation link could not be sent automatically.', 'upkeepify' ) . '</p>';
            }
            $body .= '<p>' . esc_html__( 'Review the completed job in WordPress admin.', 'upkeepify' ) . '</p>';
            $body .= '</div>';
        }

        wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

        if ( ! $is_followup_completion ) {
            // Auto-send resident confirmation email if we have their address and token.
            upkeepify_send_resident_confirmation_email( $task_id, $task_post );
        }
    }

    wp_safe_redirect( add_query_arg( 'upkeepify_response', $is_followup_completion ? 'followup_submitted' : 'completed', wp_get_referer() ?: home_url() ) );
    exit;
}
add_action( 'admin_post_'        . UPKEEPIFY_ADMIN_ACTION_PROVIDER_COMPLETION_SUBMIT, 'upkeepify_admin_post_provider_completion_submit' );
add_action( 'admin_post_nopriv_' . UPKEEPIFY_ADMIN_ACTION_PROVIDER_COMPLETION_SUBMIT, 'upkeepify_admin_post_provider_completion_submit' );

// ─────────────────────────────────────────────────────────────────────────────
// Step 4 — Resident confirmation helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Build the tokenized resident confirmation URL for a task.
 *
 * Returns null if the task has no resident token (email not supplied at
 * submission time) or if the confirmation page URL is not configured.
 *
 * @param int $task_id Maintenance task post ID.
 * @return string|null Full URL or null.
 */
function upkeepify_get_resident_confirmation_url( $task_id ) {
    $token = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN, true );
    if ( empty( $token ) ) {
        return null;
    }

    $settings          = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $confirmation_page = isset( $settings[ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] )
        ? trailingslashit( $settings[ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] )
        : '';

    if ( empty( $confirmation_page ) ) {
        return null;
    }

    return add_query_arg( UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN, rawurlencode( $token ), untrailingslashit( $confirmation_page ) );
}

/**
 * Build the tokenized provider response URL for a response post.
 *
 * @param int $response_id Provider response post ID.
 * @return string|null Full URL or null.
 */
function upkeepify_get_provider_response_url( $response_id ) {
    $token = get_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_TOKEN, true );
    if ( empty( $token ) ) {
        return null;
    }

    $settings      = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $response_page = isset( $settings[ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] )
        ? trailingslashit( $settings[ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] )
        : '';

    if ( empty( $response_page ) ) {
        return null;
    }

    return add_query_arg( UPKEEPIFY_QUERY_VAR_TOKEN, rawurlencode( $token ), untrailingslashit( $response_page ) );
}

/**
 * Find the most recently completed provider response for a task.
 *
 * @param int $task_id Maintenance task post ID.
 * @return WP_Post|null Provider response post or null.
 */
function upkeepify_get_latest_completed_response_for_task( $task_id ) {
    $responses = get_posts( array(
        'post_type'      => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_key'       => UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT,
        'meta_query'     => array(
            array(
                'key'     => UPKEEPIFY_META_KEY_RESPONSE_TASK_ID,
                'value'   => intval( $task_id ),
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT,
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    return ! empty( $responses ) ? $responses[0] : null;
}

/**
 * Notify the contractor that a resident reported a completion issue.
 *
 * @param int     $task_id     Maintenance task post ID.
 * @param WP_Post $task_post   Maintenance task post object.
 * @param int     $response_id Provider response post ID.
 * @param string  $note        Resident note.
 * @return bool True when sent.
 */
function upkeepify_send_contractor_resident_issue_email( $task_id, $task_post, $response_id, $note ) {
    $provider_id   = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
    $provider_term = $provider_id ? get_term( $provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ) : null;
    $provider_name = ( $provider_term && ! is_wp_error( $provider_term ) ) ? $provider_term->name : __( 'Contractor', 'upkeepify' );
    $provider_email = $provider_id ? get_term_meta( $provider_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, true ) : '';

    if ( ! is_email( $provider_email ) ) {
        return false;
    }

    $response_url = upkeepify_get_provider_response_url( $response_id );
    if ( ! $response_url ) {
        return false;
    }

    $subject = sprintf( __( '[%s] Resident reported an issue: %s', 'upkeepify' ), get_bloginfo( 'name' ), $task_post->post_title );
    $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
    $body   .= '<h2>' . esc_html__( 'Resident Follow-up Requested', 'upkeepify' ) . '</h2>';
    $body   .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'upkeepify' ), esc_html( $provider_name ) ) . '</p>';
    $body   .= '<p>' . sprintf( esc_html__( 'The resident was not satisfied with "%s". The trustee has also been notified and will review the job.', 'upkeepify' ), esc_html( $task_post->post_title ) ) . '</p>';
    if ( $note ) {
        $body .= '<p><strong>' . esc_html__( 'Resident comment:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
    }
    $body .= '<p>' . esc_html__( 'You can submit a follow-up note or photos using your job link. This does not close the issue; it gives the trustee more information while they review.', 'upkeepify' ) . '</p>';
    $body .= '<p style="margin:24px 0;">';
    $body .= '<a href="' . esc_url( $response_url ) . '" style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">';
    $body .= esc_html__( 'Submit follow-up', 'upkeepify' );
    $body .= '</a></p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__( 'Or copy this link:', 'upkeepify' ) . '<br>';
    $body .= '<code style="word-break:break-all;">' . esc_url( $response_url ) . '</code></p>';
    $body .= '</div>';

    $sent = wp_mail( $provider_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    if ( $sent ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_CONTRACTOR_NOTIFIED_AT, time() );
    }

    return $sent;
}

/**
 * Determine whether contractors should be notified about resident issues.
 *
 * Missing settings use the product default: notify the contractor.
 *
 * @param array $settings Plugin settings.
 * @return bool
 */
function upkeepify_should_notify_contractor_on_resident_issue( $settings ) {
    $settings = is_array( $settings ) ? $settings : array();

    return array_key_exists( UPKEEPIFY_SETTING_NOTIFY_CONTRACTOR_ON_RESIDENT_ISSUE, $settings )
        ? ! empty( $settings[ UPKEEPIFY_SETTING_NOTIFY_CONTRACTOR_ON_RESIDENT_ISSUE ] )
        : true;
}

/**
 * Open the follow-up state when a resident is not satisfied.
 *
 * @param int     $task_id   Maintenance task post ID.
 * @param WP_Post $task_post Maintenance task post object.
 * @param array   $settings  Plugin settings.
 * @param string  $note      Resident note.
 * @return WP_Post|null Completed response selected for follow-up.
 */
function upkeepify_open_resident_issue_followup( $task_id, $task_post, $settings, $note ) {
    $completed_response = upkeepify_get_latest_completed_response_for_task( $task_id );
    if ( ! $completed_response ) {
        return null;
    }

    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE );
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, intval( $completed_response->ID ) );
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_REPORTED_AT, time() );
    if ( function_exists( 'upkeepify_sync_task_lifecycle_status' ) ) {
        upkeepify_sync_task_lifecycle_status( $task_id );
    }

    if ( upkeepify_should_notify_contractor_on_resident_issue( $settings ) ) {
        upkeepify_send_contractor_resident_issue_email( $task_id, $task_post, intval( $completed_response->ID ), $note );
    }

    return $completed_response;
}

/**
 * Clear any resident issue follow-up state from a task.
 *
 * @param int $task_id Maintenance task post ID.
 */
function upkeepify_clear_resident_issue_followup( $task_id ) {
    delete_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS );
    delete_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID );
}

/**
 * Send the resident their job-completion confirmation email.
 *
 * Called automatically when a contractor marks a job complete. Does nothing
 * if the task has no submitter email on file or if already confirmed.
 *
 * @param int     $task_id   Maintenance task post ID.
 * @param WP_Post $task_post The task post object.
 * @return bool True when email was sent.
 */
function upkeepify_send_resident_confirmation_email( $task_id, $task_post ) {
    $resident_email = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL, true );
    if ( ! is_email( $resident_email ) ) {
        return false;
    }

    // Don't resend if already confirmed.
    $confirmed_at = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, true );
    if ( $confirmed_at ) {
        return false;
    }

    $confirmation_url = upkeepify_get_resident_confirmation_url( $task_id );
    if ( ! $confirmation_url ) {
        if ( WP_DEBUG ) {
            error_log( 'Upkeepify Step 4: Resident confirmation page not configured — email not sent for task ID ' . $task_id );
        }
        return false;
    }

    $site_name = get_bloginfo( 'name' );
    $subject   = sprintf(
        /* translators: %s: site name */
        __( '[%s] Your maintenance job has been completed', 'upkeepify' ),
        $site_name
    );

    $body  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
    $body .= '<h2 style="color:#333;">' . esc_html__( 'Your Job Is Complete', 'upkeepify' ) . '</h2>';
    $body .= '<p>' . sprintf( esc_html__( 'The maintenance job you reported — "%s" — has been marked as complete by the contractor.', 'upkeepify' ), esc_html( $task_post->post_title ) ) . '</p>';
    $body .= '<p>' . esc_html__( 'Please let us know whether you are satisfied with the work by clicking the button below.', 'upkeepify' ) . '</p>';
    $body .= '<p style="margin:24px 0;">';
    $body .= '<a href="' . esc_url( $confirmation_url ) . '" style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">';
    $body .= esc_html__( 'Confirm or report an issue', 'upkeepify' );
    $body .= '</a></p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__( 'This link is unique to you. If you have any questions, contact your property manager directly.', 'upkeepify' ) . '</p>';
    $body .= '<p style="color:#999;font-size:12px;">' . esc_html__( 'Or copy this link:', 'upkeepify' ) . '<br>';
    $body .= '<code style="word-break:break-all;">' . esc_url( $confirmation_url ) . '</code></p>';
    $body .= '</div>';

    $sent = wp_mail( $resident_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

    if ( WP_DEBUG ) {
        error_log( 'Upkeepify Step 4: Resident confirmation email ' . ( $sent ? 'sent' : 'FAILED' ) . ' to ' . $resident_email . ' for task ID ' . $task_id );
    }

    return (bool) $sent;
}

// ─────────────────────────────────────────────────────────────────────────────
// Step 4 — [upkeepify_resident_confirmation_form] shortcode
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Resident confirmation form shortcode.
 *
 * Token-gated via `upkeepify_resident_token` query var. Shows the task
 * title and any completion photos from the most-recently-completed response,
 * then presents a satisfied / not satisfied choice with an optional note.
 *
 * Usage: [upkeepify_resident_confirmation_form]
 *
 * @since 1.4
 * @return string HTML output.
 */
function upkeepify_resident_confirmation_form_shortcode() {
    $token = isset( $_GET[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] )
        ? sanitize_text_field( wp_unslash( $_GET[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] ) )
        : '';

    ob_start();

    if ( empty( $token ) ) {
        echo '<div class="upkeepify-notice upkeepify-notice-error">';
        echo '<p>' . esc_html__( 'No confirmation token provided. Please use the link from your email.', 'upkeepify' ) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    // Look up the task by resident token.
    $tasks = get_posts( array(
        'post_type'      => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'meta_query'     => array(
            array(
                'key'   => UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN,
                'value' => $token,
            ),
        ),
    ) );

    if ( empty( $tasks ) ) {
        echo '<div class="upkeepify-notice upkeepify-notice-error">';
        echo '<p>' . esc_html__( 'Invalid or expired confirmation link.', 'upkeepify' ) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    $task      = $tasks[0];
    $task_id   = $task->ID;

    // Already confirmed?
    $confirmed_at = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, true );
    if ( $confirmed_at ) {
        $was_satisfied = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, true );
        $resolved_at   = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT, true );
        echo '<div class="upkeepify-notice upkeepify-notice-success">';
        echo '<p>' . sprintf(
            esc_html__( 'You already submitted your feedback for "%s". Thank you.', 'upkeepify' ),
            esc_html( $task->post_title )
        ) . '</p>';
        if ( $resolved_at && $was_satisfied === '0' ) {
            echo '<p>' . esc_html__( 'The property manager has reviewed and closed the issue you reported.', 'upkeepify' ) . '</p>';
        } else {
            echo '<p>' . ( $was_satisfied === '1'
                ? esc_html__( 'You marked this job as: Satisfied.', 'upkeepify' )
                : esc_html__( 'You marked this job as: Not satisfied. The property manager has been asked to review it.', 'upkeepify' ) ) . '</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    $completion_photos = array();
    $provider_name     = '';
    $completed_response = upkeepify_get_latest_completed_response_for_task( $task_id );
    if ( $completed_response ) {
        $resp_id           = $completed_response->ID;
        $completion_photos = get_post_meta( $resp_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETION_PHOTOS, true );
        $completion_photos = is_array( $completion_photos ) ? $completion_photos : array();
        $provider_id       = intval( get_post_meta( $resp_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
        if ( $provider_id ) {
            $provider_term = get_term( $provider_id, UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER );
            if ( $provider_term && ! is_wp_error( $provider_term ) ) {
                $provider_name = $provider_term->name;
            }
        }
    }

    // Show a notice if redirected back after submission attempt (shouldn't normally happen,
    // but handles double-back-button edge cases).
    if ( isset( $_GET['upkeepify_resident_response'] ) ) {
        $status = sanitize_key( $_GET['upkeepify_resident_response'] );
        if ( $status === 'confirmed' ) {
            echo '<div class="upkeepify-notice upkeepify-notice-success">';
            echo '<p>' . esc_html__( 'Thank you — your feedback has been recorded.', 'upkeepify' ) . '</p>';
            echo '</div>';
            return ob_get_clean();
        }
    }

    // Render the form.
    echo '<div class="upkeepify-resident-confirmation">';
    echo '<div class="upkeepify-task-card">';
    echo '<h2 class="upkeepify-task-title">' . esc_html( $task->post_title ) . '</h2>';
    if ( $provider_name ) {
        echo '<p class="upkeepify-task-meta">' . sprintf( esc_html__( 'Completed by: %s', 'upkeepify' ), '<strong>' . esc_html( $provider_name ) . '</strong>' ) . '</p>';
    }
    echo '</div>';

    if ( ! empty( $completion_photos ) ) {
        echo '<div class="upkeepify-completion-photos">';
        echo '<h3>' . esc_html__( 'Completion photos', 'upkeepify' ) . '</h3>';
        echo '<div class="upkeepify-photo-grid">';
        foreach ( $completion_photos as $att_id ) {
            $img = wp_get_attachment_image( intval( $att_id ), 'medium', false, array( 'class' => 'upkeepify-completion-photo' ) );
            if ( $img ) {
                echo '<div class="upkeepify-photo-item">' . wp_kses_post( $img ) . '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
    }

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="upkeepify-confirmation-form">';
    wp_nonce_field( UPKEEPIFY_NONCE_ACTION_RESIDENT_CONFIRM, UPKEEPIFY_NONCE_RESIDENT_CONFIRM );
    echo '<input type="hidden" name="action" value="' . esc_attr( UPKEEPIFY_ADMIN_ACTION_RESIDENT_CONFIRM_SUBMIT ) . '">';
    echo '<input type="hidden" name="' . esc_attr( UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ) . '" value="' . esc_attr( $token ) . '">';
    echo '<input type="hidden" name="task_id" value="' . esc_attr( $task_id ) . '">';

    echo '<fieldset class="upkeepify-confirmation-choice">';
    echo '<legend>' . esc_html__( 'Are you satisfied with the completed work?', 'upkeepify' ) . '</legend>';

    echo '<label class="upkeepify-choice-label upkeepify-choice-yes">';
    echo '<input type="radio" name="resident_satisfied" value="1" required>';
    echo '<span class="upkeepify-choice-icon">&#10003;</span> ' . esc_html__( 'Yes, satisfied', 'upkeepify' );
    echo '</label>';

    echo '<label class="upkeepify-choice-label upkeepify-choice-no">';
    echo '<input type="radio" name="resident_satisfied" value="0">';
    echo '<span class="upkeepify-choice-icon">&#10007;</span> ' . esc_html__( 'No, there is an issue', 'upkeepify' );
    echo '</label>';
    echo '</fieldset>';

    echo '<p><label for="resident_confirm_note">' . esc_html__( 'Additional comments (optional):', 'upkeepify' ) . '</label><br>';
    echo '<textarea id="resident_confirm_note" name="resident_confirm_note" maxlength="500" rows="4" class="upkeepify-textarea"></textarea>';
    echo '<span class="upkeepify-char-counter"><span id="upkeepify-resident-note-count">0</span>/500</span></p>';

    echo '<p><button type="submit" class="upkeepify-submit-button">' . esc_html__( 'Submit feedback', 'upkeepify' ) . '</button></p>';
    echo '</form>';

    echo '</div>';// .upkeepify-resident-confirmation

    // Inline JS: character counter.
    echo '<script>';
    echo '(function(){';
    echo 'var ta=document.getElementById("resident_confirm_note");';
    echo 'var ct=document.getElementById("upkeepify-resident-note-count");';
    echo 'if(ta&&ct){ta.addEventListener("input",function(){ct.textContent=ta.value.length;});}';
    echo '})();';
    echo '</script>';

    return ob_get_clean();
}

/**
 * Handle resident confirmation form submission.
 *
 * Saves the resident's vote to the task post meta. Satisfied feedback closes
 * the lifecycle; dissatisfied feedback opens trustee review and can notify
 * the contractor for a follow-up.
 *
 * @hook admin_post_{UPKEEPIFY_ADMIN_ACTION_RESIDENT_CONFIRM_SUBMIT}
 * @hook admin_post_nopriv_{UPKEEPIFY_ADMIN_ACTION_RESIDENT_CONFIRM_SUBMIT}
 */
function upkeepify_admin_post_resident_confirm_submit() {
    $resident_confirm_nonce = isset( $_POST[ UPKEEPIFY_NONCE_RESIDENT_CONFIRM ] ) ? sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_NONCE_RESIDENT_CONFIRM ] ) ) : '';
    if ( ! $resident_confirm_nonce ||
        ! wp_verify_nonce( $resident_confirm_nonce, UPKEEPIFY_NONCE_ACTION_RESIDENT_CONFIRM ) ) {
        wp_die( esc_html__( 'Security check failed.', 'upkeepify' ) );
    }

    $task_id = isset( $_POST['task_id'] ) ? absint( wp_unslash( $_POST['task_id'] ) ) : 0;
    $task    = $task_id ? get_post( $task_id ) : null;
    if ( ! $task || $task->post_type !== UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ) {
        wp_die( esc_html__( 'Invalid task.', 'upkeepify' ) );
    }

    // Token verification.
    $stored_token = get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN, true );
    $posted_token = sanitize_text_field( wp_unslash( $_POST[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] ?? '' ) );
    if ( empty( $stored_token ) || ! hash_equals( $stored_token, $posted_token ) ) {
        wp_die( esc_html__( 'Token mismatch.', 'upkeepify' ) );
    }

    // Guard against double-submission.
    if ( get_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, true ) ) {
        wp_die( esc_html__( 'You have already submitted your feedback for this job.', 'upkeepify' ) );
    }

    $resident_satisfied = isset( $_POST['resident_satisfied'] ) ? sanitize_key( wp_unslash( $_POST['resident_satisfied'] ) ) : '';
    $satisfied          = $resident_satisfied === '1' ? '1' : '0';
    $note               = isset( $_POST['resident_confirm_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['resident_confirm_note'] ) ) : '';

    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED,    $satisfied );
    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, time() );
    if ( $note !== '' ) {
        update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, substr( $note, 0, 500 ) );
    }

    // Notify trustee. A satisfied response closes the lifecycle; a dissatisfied
    // response opens a review/follow-up state.
    $settings  = upkeepify_get_setting_cached( UPKEEPIFY_OPTION_SETTINGS, array() );
    $recipient = ! empty( $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] ) ? $settings[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] : get_option( 'admin_email' );
    $notify_contractor_on_issue = upkeepify_should_notify_contractor_on_resident_issue( $settings );

    $satisfaction_label = $satisfied === '1'
        ? __( 'Satisfied ✓', 'upkeepify' )
        : __( 'Not satisfied ✗', 'upkeepify' );

    $completed_response = null;
    if ( $satisfied === '0' ) {
        $completed_response = upkeepify_open_resident_issue_followup( $task_id, $task, $settings, $note );
    } elseif ( $satisfied === '1' ) {
        upkeepify_clear_resident_issue_followup( $task_id );
    }
    if ( function_exists( 'upkeepify_sync_task_lifecycle_status' ) ) {
        upkeepify_sync_task_lifecycle_status( $task_id );
    }

    $subject = $satisfied === '1'
        ? sprintf( __( '[%s] Lifecycle closed — resident confirmed: %s', 'upkeepify' ), get_bloginfo( 'name' ), $task->post_title )
        : sprintf( __( '[%s] Resident reported an issue: %s', 'upkeepify' ), get_bloginfo( 'name' ), $task->post_title );
    $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;">';
    $body   .= '<h2>' . ( $satisfied === '1'
        ? esc_html__( 'Resident Confirmation Received', 'upkeepify' )
        : esc_html__( 'Resident Issue Reported', 'upkeepify' ) ) . '</h2>';
    $body   .= '<p>' . ( $satisfied === '1'
        ? sprintf( esc_html__( 'The resident has confirmed the completion of "%s".', 'upkeepify' ), esc_html( $task->post_title ) )
        : sprintf( esc_html__( 'The resident was not satisfied with "%s". Review the issue before closing the job.', 'upkeepify' ), esc_html( $task->post_title ) ) ) . '</p>';
    $body   .= '<p><strong>' . esc_html__( 'Satisfaction:', 'upkeepify' ) . '</strong> ' . esc_html( $satisfaction_label ) . '</p>';
    if ( $note ) {
        $body .= '<p><strong>' . esc_html__( 'Resident comment:', 'upkeepify' ) . '</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
    }
    if ( $satisfied === '0' ) {
        $contractor_notice = $notify_contractor_on_issue && $completed_response
            ? __( 'The contractor has been notified and can submit a follow-up note/photos using their existing job link.', 'upkeepify' )
            : __( 'Contractor notification is disabled or no completed contractor response was found.', 'upkeepify' );
        $body .= '<p>' . esc_html( $contractor_notice ) . '</p>';
        $body .= '<p style="color:#666;font-size:12px;">' . esc_html__( 'This job is pending trustee review; the contractor cannot close it from the follow-up link.', 'upkeepify' ) . '</p>';
    } else {
        $body .= '<p style="color:#666;font-size:12px;">' . esc_html__( 'The full job lifecycle is now closed.', 'upkeepify' ) . '</p>';
    }
    $body .= '</div>';

    wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

    // Redirect back to the confirmation page with a success flag.
    $confirmation_page = isset( $settings[ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] )
        ? trailingslashit( $settings[ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] )
        : home_url( '/' );

    $redirect = add_query_arg( array(
        UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN => rawurlencode( $stored_token ),
        'upkeepify_resident_response'      => 'confirmed',
    ), untrailingslashit( $confirmation_page ) );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_'        . UPKEEPIFY_ADMIN_ACTION_RESIDENT_CONFIRM_SUBMIT, 'upkeepify_admin_post_resident_confirm_submit' );
add_action( 'admin_post_nopriv_' . UPKEEPIFY_ADMIN_ACTION_RESIDENT_CONFIRM_SUBMIT, 'upkeepify_admin_post_resident_confirm_submit' );
