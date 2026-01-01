<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Add admin menu page for plugin settings.
 *
 * Registers a submenu page under Maintenance Tasks in the WordPress admin menu.
 *
 * @since 1.0
 * @uses add_submenu_page()
 * @hook admin_menu
 */
function upkeepify_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'Upkeepify Settings',
        'Settings',
        'manage_options',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_settings_page'
    );
}
add_action('admin_menu', 'upkeepify_add_admin_menu');

/**
 * Render a settings field with optional attributes.
 *
 * Callback function to render input fields for settings.
 * Supports various field types and custom attributes.
 *
 * @since 1.0
 * @param array $args {
 *     Field arguments.
 *
 *     @type string $name        Field name.
 *     @type string $type        Field type (defaults to 'text').
 *     @type array  $attributes   Optional associative array of HTML attributes.
 * }
 * @uses get_option()
 */
function upkeepify_render_settings_field($args) {
    $options = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    $field_value = isset($options[$args['name']]) ? $options[$args['name']] : '';
    $field_type = isset($args['type']) ? $args['type'] : 'text'; // Default to text if not specified

    echo '<input type="' . esc_attr($field_type) . '" id="' . esc_attr($args['name']) . '" name="upkeepify_settings[' . esc_attr($args['name']) . ']" value="' . esc_attr($field_value) . '"';

    // Append additional attributes if any
    if (isset($args['attributes']) && is_array($args['attributes'])) {
        foreach ($args['attributes'] as $attr => $value) {
            echo ' ' . $attr . '="' . esc_attr($value) . '"';
        }
    }

    echo '>';
}

/**
 * Initialize plugin settings.
 *
 * Registers settings, sections, and fields for the Upkeepify settings page.
 * Includes general settings and thank you page configuration.
 *
 * @since 1.0
 * @uses register_setting()
 * @uses add_settings_section()
 * @uses add_settings_field()
 * @hook admin_init
 */
function upkeepify_init_plugin_settings() {
    register_setting('upkeepify', UPKEEPIFY_OPTION_SETTINGS, 'upkeepify_settings_sanitize');

    add_settings_section(
        'upkeepify_general_settings',
        __('General Settings', 'upkeepify'),
        null,
        UPKEEPIFY_OPTION_SETTINGS
    );

    // SMTP Option
    add_settings_field(
        UPKEEPIFY_SETTING_SMTP_OPTION,
        __('Use Built-in SMTP Provider', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_SMTP_OPTION,
            'checked' => '1'
        ]
    );

// Add a field for SMTP Host if using built-in SMTP
add_settings_field(
    UPKEEPIFY_SETTING_SMTP_HOST,
    __('SMTP Host', 'upkeepify'),
    'upkeepify_text_field_callback',
    UPKEEPIFY_OPTION_SETTINGS,
    'upkeepify_general_settings',
    [
        'label_for' => UPKEEPIFY_SETTING_SMTP_HOST,
        'class' => 'smtp_setting'
    ]
);

// You can add more SMTP settings (e.g., Port, Username, Password) in a similar manner.
// Ensure these also have 'smtp_setting' class for easy JavaScript targeting.


    // Notify Option
    add_settings_field(
        UPKEEPIFY_SETTING_NOTIFY_OPTION,
        __('Notify on Status Change', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_NOTIFY_OPTION,
            'checked' => '1'
        ]
    );

    // Provider Delete Task
    add_settings_field(
        UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK,
        __('Allow Service Provider to Delete Task', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK,
            'checked' => '0'
        ]
    );

    // Public Task Logging
    add_settings_field(
        UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING,
        __('Allow Public Task Logging', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING,
            'checked' => '0'
        ]
    );

    // Override Email Address
    add_settings_field(
        UPKEEPIFY_SETTING_OVERRIDE_EMAIL,
        __('Override Email Address for Notifications', 'upkeepify'),
        'upkeepify_text_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_OVERRIDE_EMAIL
        ]
    );

    // Enable Token-based Status Update
    add_settings_field(
        UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE,
        __('Enable Token-based Status Update for Providers', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_general_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE,
        'checked' => '0'
        ]
    );

// Number of Units
add_settings_field(
    UPKEEPIFY_SETTING_NUMBER_OF_UNITS,
    __('Number of Units', 'upkeepify'),
    'upkeepify_render_settings_field',
    UPKEEPIFY_OPTION_SETTINGS,
    'upkeepify_general_settings',
    [
        'name' => UPKEEPIFY_SETTING_NUMBER_OF_UNITS,
        'type' => 'number',
        'attributes' => [
            'min' => '0', // Example validation attribute
            'step' => '1'
        ]
    ]
);

// Add a field for specifying the currency in the 'General Settings' section
add_settings_field(
    UPKEEPIFY_SETTING_CURRENCY,
    __('Currency', 'upkeepify'),
    'upkeepify_text_field_callback', // Assuming you have a generic callback for rendering text fields
    UPKEEPIFY_OPTION_SETTINGS,
    'upkeepify_general_settings',
    [
        'label_for' => UPKEEPIFY_SETTING_CURRENCY,
        'description' => __('Specify the currency symbol (e.g., $, €, £).', 'upkeepify'),
    ]
);

    // Thank You Page Setting
    add_settings_section(
        'upkeepify_provider_thank_you_settings',
        __('Provider Thank You Page Settings', 'upkeepify'),
        'upkeepify_provider_thank_you_settings_section_callback',
        UPKEEPIFY_OPTION_SETTINGS
    );

    // Checkbox for enabling custom thank you page
    add_settings_field(
        UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE,
        __('Enable Custom Thank You Page', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_provider_thank_you_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE,
            'class' => 'upkeepify_row',
            'upkeepify_custom_data' => 'checkbox',
        ]
    );

    // Textbox for specifying the URL of the thank you page
    add_settings_field(
        UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL,
        __('Thank You Page URL', 'upkeepify'),
        'upkeepify_text_field_callback',
        UPKEEPIFY_OPTION_SETTINGS,
        'upkeepify_provider_thank_you_settings',
        [
            'label_for' => UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL,
            'class' => 'upkeepify_row upkeepify_thank_you_page_url',
        ]
    );

}

add_action('admin_init', 'upkeepify_init_plugin_settings');

/**
 * Callback for provider thank you settings section.
 *
 * Displays a description for the thank you page settings section.
 *
 * @since 1.0
 */
function upkeepify_provider_thank_you_settings_section_callback() {
    echo '<p>' . __('Configure the custom thank you page for service providers.', 'upkeepify') . '</p>';
}

/**
 * Render the settings page.
 *
 * Outputs the HTML for the Upkeepify settings page including
 * form fields and submit button.
 *
 * @since 1.0
 * @uses settings_fields()
 * @uses do_settings_sections()
 * @uses submit_button()
 */
function upkeepify_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('upkeepify');
            do_settings_sections(UPKEEPIFY_MENU_SETTINGS_PAGE);
            submit_button(__('Save Settings', 'upkeepify'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Callback to render checkbox settings fields.
 *
 * Outputs a checkbox input field for settings that require boolean values.
 *
 * @since 1.0
 * @param array $args {
 *     Field arguments.
 *
 *     @type string $label_for The field ID.
 * }
 * @uses get_option()
 * @uses checked()
 */
function upkeepify_checkbox_field_callback($args) {
    $options = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    $checked = isset($options[$args['label_for']]) ? (bool) $options[$args['label_for']] : false;
    echo '<input id="' . esc_attr($args['label_for']) . '" name="upkeepify_settings[' . esc_attr($args['label_for']) . ']" type="checkbox" value="1" ' . checked($checked, true, false) . '>';
}

/**
 * Callback to render text settings fields.
 *
 * Outputs a text input field for settings that require string values.
 *
 * @since 1.0
 * @param array $args {
 *     Field arguments.
 *
 *     @type string $label_for The field ID.
 * }
 * @uses get_option()
 */
function upkeepify_text_field_callback($args) {
    $options = upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
    echo '<input id="' . esc_attr($args['label_for']) . '" name="upkeepify_settings[' . esc_attr($args['label_for']) . ']" type="text" value="' . esc_attr($value) . '">';
}

/**
 * Sanitize plugin settings before saving.
 *
 * Validates and sanitizes all settings input before storing in the database.
 * Applies appropriate sanitization based on the setting type.
 *
 * @since 1.0
 * @param array $input The raw settings input from the form.
 * @return array The sanitized settings array.
 * @uses sanitize_text_field()
 * @uses intval()
 */
function upkeepify_settings_sanitize($input) {
    $validated = upkeepify_validate_settings(is_array($input) ? $input : array());

    if (is_wp_error($validated)) {
        add_settings_error(
            'upkeepify',
            'upkeepify_settings_validation',
            $validated->get_error_message(),
            'error'
        );

        return upkeepify_get_setting_cached(UPKEEPIFY_OPTION_SETTINGS, array());
    }

    return $validated;
}

/**
 * Clear cache after settings update.
 *
 * Hooked to run after settings are sanitized and saved.
 *
 * @since 1.0
 * @param mixed $value The new, un-sanitized value.
 * @param array $old_value The old (sanitized) value.
 * @param array $new_value The new (sanitized) value.
 */
function upkeepify_settings_update_clear_cache($value, $old_value, $new_value) {
    // Clear settings cache group
    upkeepify_invalidate_cache_group('settings');
}
add_action('update_option_' . UPKEEPIFY_OPTION_SETTINGS, 'upkeepify_settings_update_clear_cache', 10, 3);

/**
 * Enqueue admin scripts for settings page.
 *
 * Loads JavaScript files only on the Upkeepify settings page.
 * Includes jQuery dependency and loads in footer for performance.
 *
 * @since 1.0
 * @param string $hook_suffix The current admin page hook.
 * @uses plugin_dir_url()
 * @uses wp_enqueue_script()
 * @hook admin_enqueue_scripts
 */
function upkeepify_enqueue_admin_scripts($hook_suffix) {
    // Check if on the Upkeepify settings page
    if ('maintenance_tasks_page_upkeepify_settings' !== $hook_suffix) {
        //echo "<script>console.log('Not our page, current hook_suffix: " . $hook_suffix . "');</script>";
        return; // Not our settings page, do not enqueue the script
    }

    $js_dir = plugin_dir_url(dirname(__FILE__)) . 'js/';

    // Enqueue utils.js first (required by other scripts)
    wp_enqueue_script(
        'upkeepify-utils-js',
        $js_dir . 'utils.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Enqueue notifications.js
    wp_enqueue_script(
        'upkeepify-notifications-js',
        $js_dir . 'notifications.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue admin-settings.js
    wp_enqueue_script(
        'upkeepify-admin-settings-js',
        $js_dir . 'admin-settings.js',
        array('jquery', 'upkeepify-utils-js', 'upkeepify-notifications-js'),
        '1.0.1',
        true
    );
}
add_action('admin_enqueue_scripts', 'upkeepify_enqueue_admin_scripts');

/**
 * Enqueue frontend scripts and styles.
 *
 * Loads JavaScript files on front-end pages for forms, filters, and calendar.
 *
 * @since 1.0
 * @uses plugin_dir_url()
 * @uses wp_enqueue_script()
 * @uses wp_enqueue_style()
 * @hook wp_enqueue_scripts
 */
function upkeepify_enqueue_frontend_scripts() {
    $js_dir = plugin_dir_url(dirname(__FILE__)) . 'js/';
    $css_dir = plugin_dir_url(dirname(__FILE__)) . '';

    // Enqueue utils.js
    wp_enqueue_script(
        'upkeepify-utils-js',
        $js_dir . 'utils.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Enqueue notifications.js
    wp_enqueue_script(
        'upkeepify-notifications-js',
        $js_dir . 'notifications.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue form-validation.js
    wp_enqueue_script(
        'upkeepify-form-validation-js',
        $js_dir . 'form-validation.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue upload-handler.js
    wp_enqueue_script(
        'upkeepify-upload-handler-js',
        $js_dir . 'upload-handler.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue task-filters.js
    wp_enqueue_script(
        'upkeepify-task-filters-js',
        $js_dir . 'task-filters.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue calendar-interactions.js
    wp_enqueue_script(
        'upkeepify-calendar-interactions-js',
        $js_dir . 'calendar-interactions.js',
        array('jquery', 'upkeepify-utils-js'),
        '1.0.0',
        true
    );

    // Enqueue enhanced styles
    wp_enqueue_style(
        'upkeepify-enhanced-styles',
        $css_dir . 'upkeepify-styles.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'upkeepify_enqueue_frontend_scripts');

// Implement caching for frequently accessed data
/**
 * Retrieve an option value from cache or database.
 *
 * Implements a caching layer for frequently accessed options.
 * Returns cached value if available, otherwise fetches from database
 * and stores in cache for 1 hour.
 *
 * @since 1.0
 * @param string $option_name The option name to retrieve.
 * @return mixed The option value.
 * @uses wp_cache_get()
 * @uses get_option()
 * @uses wp_cache_set()
 */
function upkeepify_get_cached_option($option_name) {
    $cache_key = 'upkeepify_' . $option_name;
    $cached_value = wp_cache_get($cache_key, 'upkeepify');

    if ($cached_value === false) {
        $cached_value = get_option($option_name);
        wp_cache_set($cache_key, $cached_value, UPKEEPIFY_CACHE_GROUP, 3600); // Cache for 1 hour
    }

    return $cached_value;
}

/**
 * Update an option value and refresh cache.
 *
 * Updates both the database option and the cached value.
 * Ensures cache and database stay synchronized.
 *
 * @since 1.0
 * @param string $option_name The option name to update.
 * @param mixed $option_value The new option value.
 * @uses update_option()
 * @uses wp_cache_set()
 */
function upkeepify_update_cached_option($option_name, $option_value) {
    $cache_key = 'upkeepify_' . $option_name;
    update_option($option_name, $option_value);
    wp_cache_set($cache_key, $option_value, UPKEEPIFY_CACHE_GROUP, 3600); // Cache for 1 hour
}

/**
 * Register setup wizard menu page.
 *
 * Adds a submenu page for the plugin setup wizard under Maintenance Tasks.
 *
 * @since 1.0
 * @uses add_submenu_page()
 * @hook admin_menu
 */
function upkeepify_setup_wizard() {
    add_submenu_page(
        'edit.php?post_type=' . UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
        'Upkeepify Setup Wizard',
        'Setup Wizard',
        'manage_options',
        UPKEEPIFY_MENU_SETUP_WIZARD_PAGE,
        'upkeepify_setup_wizard_page'
    );
}

/**
 * Render the setup wizard page.
 *
 * Outputs HTML for the initial plugin setup wizard page.
 * Allows users to configure basic plugin settings.
 *
 * @since 1.0
 * @uses settings_fields()
 * @uses do_settings_sections()
 * @uses submit_button()
 */
function upkeepify_setup_wizard_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Upkeepify Setup Wizard', 'upkeepify'); ?></h1>
        <p><?php echo esc_html__('Welcome to the Upkeepify Setup Wizard. Follow the steps below to configure the plugin.', 'upkeepify'); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('upkeepify');
            do_settings_sections(UPKEEPIFY_MENU_SETTINGS_PAGE);
            submit_button(__('Save Settings', 'upkeepify'));
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_menu', UPKEEPIFY_MENU_SETUP_WIZARD_PAGE);
