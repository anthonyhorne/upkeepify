<?php
// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

function upkeepify_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=maintenance_tasks',
        'Upkeepify Settings',
        'Settings',
        'manage_options',
        'upkeepify_settings',
        'upkeepify_settings_page'
    );
}
add_action('admin_menu', 'upkeepify_add_admin_menu');

function upkeepify_init_plugin_settings() {
    register_setting('upkeepify', 'upkeepify_settings', 'upkeepify_settings_sanitize');

    add_settings_section(
        'upkeepify_general_settings',
        __('General Settings', 'upkeepify'),
        null,
        'upkeepify_settings'
    );

    // SMTP Option
    add_settings_field(
        'upkeepify_smtp_option',
        __('Use Built-in SMTP Provider', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_smtp_option',
            'checked' => '1'
        ]
    );

// Add a field for SMTP Host if using built-in SMTP
add_settings_field(
    'upkeepify_smtp_host',
    __('SMTP Host', 'upkeepify'),
    'upkeepify_text_field_callback',
    'upkeepify_settings',
    'upkeepify_general_settings',
    [
        'label_for' => 'upkeepify_smtp_host',
        'class' => 'upkeepify_row smtp_setting'
    ]
);

// You can add more SMTP settings (e.g., Port, Username, Password) in a similar manner.
// Ensure these also have 'smtp_setting' class for easy JavaScript targeting.


    // Notify Option
    add_settings_field(
        'upkeepify_notify_option',
        __('Notify on Status Change', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_notify_option',
            'checked' => '1'
        ]
    );

    // Provider Delete Task
    add_settings_field(
        'upkeepify_provider_delete_task',
        __('Allow Service Provider to Delete Task', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_provider_delete_task',
            'checked' => '0'
        ]
    );

    // Public Task Logging
    add_settings_field(
        'upkeepify_public_task_logging',
        __('Allow Public Task Logging', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_public_task_logging',
            'checked' => '0'
        ]
    );

    // Override Email Address
    add_settings_field(
        'upkeepify_override_email',
        __('Override Email Address for Notifications', 'upkeepify'),
        'upkeepify_text_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_override_email'
        ]
    );

    // Enable Token-based Status Update
    add_settings_field(
        'upkeepify_enable_token_update',
        __('Enable Token-based Status Update for Providers', 'upkeepify'),
        'upkeepify_checkbox_field_callback',
        'upkeepify_settings',
        'upkeepify_general_settings',
        [
            'label_for' => 'upkeepify_enable_token_update',
            'checked' => '0'
        ]
    );
}

add_action('admin_init', 'upkeepify_init_plugin_settings');

function upkeepify_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('upkeepify');
            do_settings_sections('upkeepify_settings');
            submit_button(__('Save Settings', 'upkeepify'));
            ?>
        </form>
    </div>
    <?php
}

function upkeepify_checkbox_field_callback($args) {
    $options = get_option('upkeepify_settings');
    $checked = isset($options[$args['label_for']]) ? (bool) $options[$args['label_for']] : false;
    echo '<input id="' . esc_attr($args['label_for']) . '" name="upkeepify_settings[' . esc_attr($args['label_for']) . ']" type="checkbox" value="1" ' . checked($checked, true, false) . '>';
}

function upkeepify_text_field_callback($args) {
    $options = get_option('upkeepify_settings');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
    echo '<input id="' . esc_attr($args['label_for']) . '" name="upkeepify_settings[' . esc_attr($args['label_for']) . ']" type="text" value="' . esc_attr($value) . '">';
}

function upkeepify_settings_sanitize($input) {
    $sanitized_input = [];
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $sanitized_input[$key] = array_map('sanitize_text_field', $value);
        } else {
            $sanitized_input[$key] = sanitize_text_field($value);
        }
    }
    return $sanitized_input;
}

function upkeepify_enqueue_admin_scripts($hook_suffix) {
    // Check if on the Upkeepify settings page
    if ('maintenance_tasks_page_upkeepify_settings' !== $hook_suffix) {
        //echo "<script>console.log('Not our page, current hook_suffix: " . $hook_suffix . "');</script>";
        return; // Not our settings page, do not enqueue the script
    }

    // Correct path to your JS file
    $script_url = plugin_dir_url(dirname(__FILE__)) . 'js/admin-settings.js';


    // Enqueue the admin-settings.js script
    wp_enqueue_script(
        'upkeepify-admin-settings-js', // Handle for the script.
        $script_url,
        array('jquery'), // Dependencies. It depends on jQuery
        '1.0.0', // Script version number for cache busting
        true // Load in the footer to not delay page rendering.
    );
}
add_action('admin_enqueue_scripts', 'upkeepify_enqueue_admin_scripts');

add_action('admin_enqueue_scripts', function($hook_suffix) {
    error_log('Current page hook suffix: ' . $hook_suffix);
});
