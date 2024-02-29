<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Register Custom Taxonomies
 */
function upkeepify_register_taxonomies() {
    // Task Categories - Non-hierarchical (like tags)
    $args_task_category = array(
        'hierarchical' => false,
        'label' => 'Task Categories',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-category'),
    );
    register_taxonomy('task_category', array('maintenance_tasks'), $args_task_category);

    // Task Types - Non-hierarchical (like tags)
    $args_task_type = array(
        'hierarchical' => false,
        'label' => 'Task Types',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-type'),
    );
    register_taxonomy('task_type', array('maintenance_tasks'), $args_task_type);

    // Task Statuses - Non-hierarchical (like tags)
    $args_task_status = array(
        'hierarchical' => false,
        'label' => 'Task Statuses',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-status'),
    );
    register_taxonomy('task_status', array('maintenance_tasks'), $args_task_status);

    // Service Providers - Non-hierarchical (like tags), associated with multiple categories
    $args_service_provider = array(
        'hierarchical' => false,
        'label' => 'Service Providers',
        'show_ui' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'service-provider'),
    );
    register_taxonomy('service_provider', array('maintenance_tasks'), $args_service_provider);
}

add_action('init', 'upkeepify_register_taxonomies');

// Add custom fields to "Service Providers" taxonomy term form
function upkeepify_service_provider_add_form_fields() {
    // Custom field for Phone Number
    ?><div class="form-field term-group">
        <label for="provider_phone"><?php _e('Phone Number', 'upkeepify'); ?></label>
        <input type="text" id="provider_phone" name="provider_phone">
        <p><?php _e('The phone number of the service provider.', 'upkeepify'); ?></p>
    </div><?php
    // Custom field for Email Address
    ?><div class="form-field term-group">
        <label for="provider_email"><?php _e('Email Address', 'upkeepify'); ?></label>
        <input type="email" id="provider_email" name="provider_email">
        <p><?php _e('The email address of the service provider.', 'upkeepify'); ?></p>
    </div><?php
}

function upkeepify_add_task_categories_to_providers() {
    $categories = get_terms(['taxonomy' => 'task_category', 'hide_empty' => false]);

    // Output a checkbox list or a multi-select field for all "Task Categories"
    echo '<div class="form-field term-group">';
    echo '<label for="associated_task_categories">Associated Task Categories</label>';
    echo '<select name="associated_task_categories[]" multiple style="width:100%;">';
    foreach ($categories as $category) {
        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
    }
    echo '</select>';
    echo '</div>';
}
add_action('service_provider_add_form_fields', 'upkeepify_add_task_categories_to_providers');

add_action('service_provider_add_form_fields', 'upkeepify_service_provider_add_form_fields');

// Display custom fields on "Service Providers" taxonomy term edit form
function upkeepify_service_provider_edit_form_fields($term) {
    // Retrieve existing values for phone and email
    $provider_phone = get_term_meta($term->term_id, 'provider_phone', true);
    $provider_email = get_term_meta($term->term_id, 'provider_email', true);
    $associated_categories = get_term_meta($term->term_id, 'associated_task_categories', true) ?: array();

    // Fetch all task categories for selection
    $task_categories = get_terms(['taxonomy' => 'task_category', 'hide_empty' => false]);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="provider_phone"><?php _e('Phone Number', 'upkeepify'); ?></label></th>
        <td>
            <input type="text" id="provider_phone" name="provider_phone" value="<?php echo esc_attr($provider_phone); ?>">
            <p class="description"><?php _e('The phone number of the service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="provider_email"><?php _e('Email Address', 'upkeepify'); ?></label></th>
        <td>
            <input type="email" id="provider_email" name="provider_email" value="<?php echo esc_attr($provider_email); ?>">
            <p class="description"><?php _e('The email address of the service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="associated_task_categories"><?php _e('Associated Task Categories', 'upkeepify'); ?></label></th>
        <td>
            <select name="associated_task_categories[]" id="associated_task_categories" multiple class="postform">
                <?php foreach ($task_categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo in_array($category->term_id, $associated_categories) ? 'selected' : ''; ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('Select task categories associated with this service provider.', 'upkeepify'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('service_provider_edit_form_fields', 'upkeepify_service_provider_edit_form_fields');

// Save custom fields data from "Service Providers" taxonomy term form
function upkeepify_save_service_provider_custom_fields($term_id) {
    // Check if the 'associated_task_categories' field is set in the submitted form
    if (isset($_POST['associated_task_categories'])) {
        // Sanitize the input to ensure it's an array of integers (term IDs)
        $category_ids = array_map('intval', $_POST['associated_task_categories']);

        // Update the term meta with the selected 'Task Categories' IDs
        update_term_meta($term_id, 'associated_task_categories', $category_ids);
    } else {
        // If no categories are selected, clear the existing associations
        delete_term_meta($term_id, 'associated_task_categories');
    }

    // Continue with saving other fields (e.g., phone number, email)
    if (isset($_POST['provider_phone'])) {
        update_term_meta($term_id, 'provider_phone', sanitize_text_field($_POST['provider_phone']));
    }
    if (isset($_POST['provider_email']) && is_email($_POST['provider_email'])) {
        update_term_meta($term_id, 'provider_email', sanitize_email($_POST['provider_email']));
    } elseif (isset($_POST['provider_email'])) {
        // Optional: Add error handling for invalid email
    }
}

//add_action('created_service_provider', 'upkeepify_save_service_provider_custom_fields');
add_action('edited_service_provider', 'upkeepify_save_service_provider_custom_fields');
add_action('created_service_provider', 'upkeepify_save_service_provider_custom_fields');
