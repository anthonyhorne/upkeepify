# Shortcodes

The Upkeepify plugin provides several shortcodes that you can use to display task-related information on your WordPress site.

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

### Enhancements

- The form now includes CSS classes for better styling.
- JavaScript enhancements have been added for improved user interaction, including a thank you message and a button to create a new task after submission.

## Task Listing

The `[upkeepify_list_tasks]` shortcode displays a list of all published maintenance tasks. For each task, the following information is shown:

- Task Title
- Rough Estimate
- Category
- Type (optional)
- Status (optional)

This shortcode provides a simple way to display the current list of maintenance tasks on your WordPress site, allowing residents to stay informed about ongoing maintenance activities.

### Enhancements

- The task list now includes CSS classes for better styling.

## Tasks by Category

The `[upkeepify_tasks_by_category]` shortcode displays tasks filtered by a specific category. You can use the `category` attribute to specify the category slug.

Example usage:
```
[upkeepify_tasks_by_category category="electrical"]
```

## Tasks by Service Provider

The `[upkeepify_tasks_by_provider]` shortcode displays tasks assigned to a specific service provider. You can use the `provider` attribute to specify the provider's name.

Example usage:
```
[upkeepify_tasks_by_provider provider="Handyman Heroes"]
```

## Tasks by Status

The `[upkeepify_tasks_by_status]` shortcode displays tasks filtered by a specific status. You can use the `status` attribute to specify the status slug.

Example usage:
```
[upkeepify_tasks_by_status status="completed"]
```

## Task Summary

The `[upkeepify_task_summary]` shortcode displays a summary of tasks, including counts of tasks by status.

Example usage:
```
[upkeepify_task_summary]
```

## Task Calendar

The `[upkeepify_task_calendar]` shortcode displays a calendar view of tasks, showing due dates and deadlines.

Example usage:
```
[upkeepify_task_calendar]
```
