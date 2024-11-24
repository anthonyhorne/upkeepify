# Development

The Upkeepify plugin is built with PHP and follows the WordPress Coding Standards and best practices. This document provides information for developers who want to contribute to the plugin or extend its functionality. It covers the plugin's file structure, coding standards, hooks (actions and filters), and other relevant details to help you get started with development.

## Coding Standards

The Upkeepify plugin follows the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/). All contributions should adhere to these standards to ensure consistency and maintainability.

## File Structure

upkeepify/
├── docs/
├── includes/
│   ├── admin-functions.php
│   ├── custom-post-types.php
│   ├── sample-data.php
│   ├── settings.php
│   ├── shortcodes.php
│   ├── task-response-handling.php
│   ├── taxonomies.php
│   └── utility-functions.php
├── js/
│   └── admin-settings.js
├── upkeepify.php
├── README.md
└── ... (other files)

- `includes/`: This directory contains various PHP files that handle different aspects of the plugin's functionality, such as custom post types, taxonomies, settings, shortcodes, and utility functions.
- `js/`: This directory contains JavaScript files used for enhancing the plugin's functionality in the admin area.
- `upkeepify.php`: The main plugin file that bootstraps the plugin and includes other necessary files.

## Hooks (Actions and Filters)

The Upkeepify plugin provides several hooks (actions and filters) that allow developers to extend or modify its functionality. Here are some examples:

### Actions

- `upkeepify_generate_provider_tokens`: Fires when a new maintenance task is published, allowing you to modify the behavior of generating provider response tokens.
- `upkeepify_handle_task_form_submission`: Fires when the task submission form is submitted, allowing you to modify the form handling process.
- `admin_init`: Used to conditionally insert sample data when the plugin is activated.

### Filters

- `upkeepify_can_user_delete_task`: Allows you to modify the logic for determining if a user can delete a maintenance task.
- `upkeepify_validate_task_update_token`: Allows you to modify the logic for validating task update tokens provided by service providers.

You can hook into these actions and filters by adding your custom functions to the appropriate hooks in your theme's `functions.php` file or a custom plugin.

## Examples

### Adding a Custom Field to the Task Form

To add a custom field to the task submission form, you can use the `upkeepify_task_form_shortcode` filter. Here's an example:

```php
add_filter('upkeepify_task_form_shortcode', 'add_custom_field_to_task_form');
function add_custom_field_to_task_form($form_html) {
    $custom_field_html = '<p><label for="custom_field">Custom Field:</label><br />';
    $custom_field_html .= '<input type="text" id="custom_field" name="custom_field" class="upkeepify-input"></p>';
    return $form_html . $custom_field_html;
}
```

### Validating the Custom Field

To validate the custom field, you can use the `upkeepify_handle_task_form_submission` action. Here's an example:

```php
add_action('upkeepify_handle_task_form_submission', 'validate_custom_field');
function validate_custom_field() {
    if (isset($_POST['custom_field']) && empty($_POST['custom_field'])) {
        wp_die('The custom field is required.');
    }
}
```

### Saving the Custom Field

To save the custom field value, you can use the `save_post` action. Here's an example:

```php
add_action('save_post', 'save_custom_field');
function save_custom_field($post_id) {
    if (isset($_POST['custom_field'])) {
        update_post_meta($post_id, 'custom_field', sanitize_text_field($_POST['custom_field']));
    }
}
```

## Best Practices for Contributing

When contributing to the Upkeepify plugin, please follow these best practices:

1. **Fork the Repository**: Start by forking the Upkeepify repository on GitHub to your own account.
2. **Create a Branch**: Create a new branch for your feature or bug fix. Use a descriptive name for the branch, such as `feature/add-custom-field` or `bugfix/fix-task-form`.
3. **Write Clear Commit Messages**: Write clear and concise commit messages that describe the changes you made. Use the imperative mood, such as "Add custom field to task form" or "Fix validation for custom field".
4. **Follow Coding Standards**: Ensure that your code adheres to the WordPress Coding Standards. Use a code linter to check for any issues.
5. **Test Your Changes**: Test your changes thoroughly to ensure they work as expected and do not introduce any new issues. Write unit tests if applicable.
6. **Submit a Pull Request**: Once you are satisfied with your changes, submit a pull request to the main Upkeepify repository. Provide a detailed description of the changes and any relevant information for the reviewers.

By following these best practices, you can help maintain the quality and consistency of the Upkeepify plugin and make it easier for others to review and merge your contributions.
