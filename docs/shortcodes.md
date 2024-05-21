# Shortcodes

The Upkeepify plugin provides two shortcodes that you can use to display task-related information on your WordPress site.

## Task Submission Form

The `[upkeepify_task_form]` shortcode generates a form for residents to submit new maintenance tasks. This form includes the following fields:

- Task Title
- Task Description
- Task Category (dropdown)
- Task Type (dropdown)
- Upload Photo
- GPS Location (auto-filled if user allows location access)
- Math Captcha (for spam prevention)

When a user submits the form, a new "Maintenance Task" post is created with the provided details. Administrators can then manage and assign these tasks to appropriate service providers.

## Task Listing

The `[upkeepify_list_tasks]` shortcode displays a list of all published maintenance tasks. For each task, the following information is shown:

- Task Title
- Rough Estimate
- Category
- Type (optional)
- Status (optional)

This shortcode provides a simple way to display the current list of maintenance tasks on your WordPress site, allowing residents to stay informed about ongoing maintenance activities.