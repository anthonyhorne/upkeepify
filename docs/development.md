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