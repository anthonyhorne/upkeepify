<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Taxonomies.
 *
 * Registers all custom taxonomies for the Maintenance Tasks post type:
 * - Task Categories
 * - Task Types
 * - Task Statuses
 * - Service Providers
 * - Units
 *
 * @since 1.0
 * @uses register_taxonomy()
 * @hook init
 */
function upkeepify_register_taxonomies() {
    // Task Categories - Non-hierarchical (like tags)
    $args_task_category = array(
        'hierarchical' => false,
        'label' => UPKEEPIFY_LABEL_TASK_CATEGORIES,
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-category'),
    );
    register_taxonomy(UPKEEPIFY_TAXONOMY_TASK_CATEGORY, array(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS), $args_task_category);

    // Task Types - Non-hierarchical (like tags)
    $args_task_type = array(
        'hierarchical' => false,
        'label' => UPKEEPIFY_LABEL_TASK_TYPES,
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-type'),
    );
    register_taxonomy(UPKEEPIFY_TAXONOMY_TASK_TYPE, array(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS), $args_task_type);

    // Task Statuses - Non-hierarchical (like tags)
    $args_task_status = array(
        'hierarchical' => false,
        'label' => UPKEEPIFY_LABEL_TASK_STATUSES,
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-status'),
    );
    register_taxonomy(UPKEEPIFY_TAXONOMY_TASK_STATUS, array(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS), $args_task_status);

    // Service Providers - Non-hierarchical (like tags)
    $args_service_provider = array(
        'hierarchical' => false,
        'label' => UPKEEPIFY_LABEL_SERVICE_PROVIDERS,
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'service-provider'),
    );
    register_taxonomy(UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER, array(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS), $args_service_provider);

    // Units - Non-hierarchical (like tags)
    $args_unit = array(
        'hierarchical' => false,
        'label' => UPKEEPIFY_LABEL_UNITS,
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'unit'),
    );
    register_taxonomy(UPKEEPIFY_TAXONOMY_UNIT, array(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS), $args_unit);
}
add_action('init', 'upkeepify_register_taxonomies');

/**
 * Add custom fields to "Service Providers" add term form.
 *
 * @since 1.0
 * @hook service_provider_add_form_fields
 */
function upkeepify_service_provider_add_form_fields() {
    ?>
    <div class="form-field term-group">
        <label for="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>"><?php _e('Phone Number', 'upkeepify'); ?></label>
        <input type="text" id="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>" name="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>" value="">
        <p><?php _e('The phone number of the service provider.', 'upkeepify'); ?></p>
    </div>

    <div class="form-field term-group">
        <label for="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>"><?php _e('Email Address', 'upkeepify'); ?></label>
        <input type="email" id="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>" name="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>" value="">
        <p><?php _e('The email address of the service provider.', 'upkeepify'); ?></p>
    </div>
    <?php
}
add_action('service_provider_add_form_fields', 'upkeepify_service_provider_add_form_fields');

/**
 * Add task categories selection field to service provider add term form.
 *
 * @since 1.0
 * @uses get_terms()
 * @hook service_provider_add_form_fields
 */
function upkeepify_add_task_categories_to_providers() {
    $categories = get_terms(array(
        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        'hide_empty' => false,
    ));

    echo '<div class="form-field term-group">';
    echo '<label for="' . esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES) . '">' . esc_html__('Associated Task Categories', 'upkeepify') . '</label>';
    echo '<select name="' . esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES) . '[]" id="' . esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES) . '" multiple style="width:100%;">';

    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
    }

    echo '</select>';
    echo '</div>';
}
add_action('service_provider_add_form_fields', 'upkeepify_add_task_categories_to_providers');

/**
 * Display custom fields on "Service Providers" edit term form.
 *
 * @since 1.0
 * @param WP_Term $term The term object being edited.
 * @uses get_term_meta()
 * @uses get_terms()
 * @hook service_provider_edit_form_fields
 */
function upkeepify_service_provider_edit_form_fields($term) {
    $provider_phone = get_term_meta($term->term_id, UPKEEPIFY_TERM_META_PROVIDER_PHONE, true);
    $provider_email = get_term_meta($term->term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, true);
    $associated_categories = get_term_meta($term->term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES, true);
    $associated_categories = is_array($associated_categories) ? $associated_categories : array();

    $task_categories = get_terms(array(
        'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_CATEGORY,
        'hide_empty' => false,
    ));

    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>"><?php _e('Phone Number', 'upkeepify'); ?></label></th>
        <td>
            <input type="text" id="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>" name="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_PHONE); ?>" value="<?php echo esc_attr($provider_phone); ?>">
            <p class="description"><?php _e('The phone number of the service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>"><?php _e('Email Address', 'upkeepify'); ?></label></th>
        <td>
            <input type="email" id="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>" name="<?php echo esc_attr(UPKEEPIFY_TERM_META_PROVIDER_EMAIL); ?>" value="<?php echo esc_attr($provider_email); ?>">
            <p class="description"><?php _e('The email address of the service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="<?php echo esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES); ?>"><?php _e('Associated Task Categories', 'upkeepify'); ?></label></th>
        <td>
            <select name="<?php echo esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES); ?>[]" id="<?php echo esc_attr(UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES); ?>" multiple class="postform">
                <?php if (!is_wp_error($task_categories) && !empty($task_categories)) : ?>
                    <?php foreach ($task_categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo in_array($category->term_id, $associated_categories, true) ? 'selected' : ''; ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="description"><?php _e('Select task categories associated with this service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('service_provider_edit_form_fields', 'upkeepify_service_provider_edit_form_fields');

/**
 * Save custom fields data for "Service Providers".
 *
 * @since 1.0
 * @param int $term_id The ID of the term being saved.
 * @uses update_term_meta()
 * @uses delete_term_meta()
 * @hook edited_service_provider
 * @hook created_service_provider
 */
function upkeepify_save_service_provider_custom_fields($term_id) {
    $phone = isset($_POST[UPKEEPIFY_TERM_META_PROVIDER_PHONE]) ? sanitize_text_field($_POST[UPKEEPIFY_TERM_META_PROVIDER_PHONE]) : '';
    $email = isset($_POST[UPKEEPIFY_TERM_META_PROVIDER_EMAIL]) ? sanitize_email($_POST[UPKEEPIFY_TERM_META_PROVIDER_EMAIL]) : '';

    $categories_raw = isset($_POST[UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES]) ? $_POST[UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES] : array();
    $category_ids = is_array($categories_raw) ? array_map('intval', $categories_raw) : array();

    $validation = upkeepify_validate_provider(array(
        'phone' => $phone,
        'email' => $email,
        'associated_categories' => $category_ids,
    ));

    if (is_wp_error($validation)) {
        if (WP_DEBUG) {
            error_log('Upkeepify Provider Validation: ' . $validation->get_error_message());
        }
        return;
    }

    if ($phone !== '') {
        update_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_PHONE, $phone);
    } else {
        delete_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_PHONE);
    }

    if ($email !== '' && is_email($email)) {
        update_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL, $email);
    } else {
        delete_term_meta($term_id, UPKEEPIFY_TERM_META_PROVIDER_EMAIL);
    }

    if (!empty($category_ids)) {
        update_term_meta($term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES, $category_ids);
    } else {
        delete_term_meta($term_id, UPKEEPIFY_TERM_META_ASSOCIATED_CATEGORIES);
    }
}
add_action('edited_service_provider', 'upkeepify_save_service_provider_custom_fields');
add_action('created_service_provider', 'upkeepify_save_service_provider_custom_fields');
