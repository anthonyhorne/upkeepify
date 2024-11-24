# Usage

The Upkeepify plugin provides a comprehensive solution for managing maintenance tasks within a community or homeowners' association. Here's how you can use the various features:

## Task Submission

1. Add the `[upkeepify_task_form]` shortcode to any page or post where you want to display the task submission form.
2. Fill in the required fields (Task Title, Description, Category, Type) and optionally upload a photo related to the task.
3. The form includes a spam prevention mechanism (simple math captcha). Answer the math question correctly to proceed.
4. Upon submission, a new "Maintenance Task" post will be created, visible to administrators for review and assignment.

## Task Management

1. Navigate to **Maintenance Tasks** in the WordPress admin sidebar to view a list of all submitted tasks.
2. Each task displays essential details such as the title, rough estimate, category, type, and status.
3. Administrators can update task statuses, assign service providers, and leave comments or notes on individual tasks.

## Service Provider Management

1. The plugin utilizes a custom taxonomy called "Service Providers" to manage the list of available service providers.
2. Navigate to **Maintenance Tasks > Service Providers** to add, edit, or delete service provider entries.
3. When adding or editing a service provider, you can specify additional details like their phone number, email address, and associated task categories.

## Settings

1. Access the plugin settings by navigating to **Maintenance Tasks > Settings** in the WordPress admin area.
2. Configure various options such as SMTP settings, notification preferences, task visibility, and more.
3. Customize the settings to align with your community's specific requirements.

## Shortcodes

The Upkeepify plugin provides the following shortcodes for displaying task information on your WordPress site:

- `[upkeepify_task_form]`: Displays the task submission form for residents.
- `[upkeepify_list_tasks]`: Lists all published maintenance tasks with essential details.