=== Upkeepify ===
Contributors: anthonyhorne
Tags: maintenance, task-management, facility-management, HOA, property-management
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive plugin for managing maintenance tasks within residential complexes and HOA schemes.

== Description ==

Upkeepify is a WordPress plugin designed to help residential complexes, HOA (Home Owners Association) schemes, and property management teams track and manage maintenance tasks efficiently.

**Core Features:**

* **Task Management** — Create, categorize, and track maintenance tasks with due dates and status workflows
* **Public Submission Form** — Residents can submit maintenance requests without a WordPress account via secure shortcode form
* **Service Provider System** — Manage contractors and service providers with token-based access (no login required)
* **Task Categorization** — Organize tasks by category, type, status, and assigned provider
* **Shortcodes** — Display tasks, forms, summaries, and calendars using simple shortcodes
* **Email Notifications** — Automatic alerts when task statuses change
* **Responsive Design** — Works on desktop, tablet, and mobile devices
* **GPS Coordinates** — Optional location tracking for maintenance issues
* **Image Uploads** — Attach photos to task submissions and responses
* **Caching System** — Optimized performance for complex sites with many tasks
* **Admin Dashboard** — Centralized management interface for all maintenance operations

**Perfect For:**

* Sectional title communities (strata schemes)
* Homeowner associations (HOAs)
* Residential complexes with caretakers or facilities teams
* Property management companies
* Any organization needing maintenance task tracking

== Installation ==

1. Download the plugin from WordPress.org
2. Upload the `upkeepify` folder to `/wp-content/plugins/`
3. Activate the plugin via the WordPress admin panel
4. Navigate to **Maintenance Tasks → Settings** to configure
5. Use shortcodes to display forms and task lists on your pages

**Quick Start:**

* `[upkeepify_task_form]` — Public task submission form
* `[upkeepify_list_tasks]` — Display all maintenance tasks
* `[upkeepify_task_summary]` — Show task count by status
* `[upkeepify_provider_response_form]` — Service provider response form

See documentation for all shortcodes and configuration options.

== Frequently Asked Questions ==

= Can residents submit tasks without a WordPress account? =

Yes! The public task submission form (`[upkeepify_task_form]`) uses a secure CAPTCHA and does not require login.

= How do service providers access their tasks? =

Service providers receive a unique token via email that grants them access to view assigned tasks and submit responses without logging in.

= What file types can be uploaded? =

By default, image files (JPG, PNG, GIF) are supported. PDF support for documents can be enabled in settings.

= Can I customize email notifications? =

Yes. Navigate to **Maintenance Tasks → Settings** to configure SMTP, notification frequency, and email recipients.

= Is this plugin GDPR compliant? =

Upkeepify stores task data in WordPress post types and does not send data externally by default. You remain in full control of your data. Personal data handling follows WordPress standards. Consider your own privacy policy regarding personal information in task submissions.

= How do I report a security issue? =

Please report security issues responsibly by contacting the plugin author via GitHub: https://github.com/anthonyhorne/upkeepify

= What is the difference between Maintenance Tasks and Provider Responses? =

* **Maintenance Tasks** are reported issues submitted by residents or staff
* **Provider Responses** are the contractor's replies, updates, and completion notes

= How can I display tasks in a calendar view? =

Use the `[upkeepify_task_calendar]` shortcode to display all upcoming maintenance tasks sorted by due date.

= Is there a way to export task data? =

Yes. Administrators can export all data via **Maintenance Tasks → Database Health**. This creates a JSON backup of all tasks, categories, and settings.

== Screenshots ==

1. **Task Submission Form** — Clean, mobile-friendly form for residents to report maintenance issues
2. **Admin Task List** — Organized view of all maintenance tasks with filters and bulk actions
3. **Task Details & Notes** — View full task information with provider assignments and status history
4. **Service Provider Management** — Add and manage contractors with contact information
5. **Plugin Settings** — Configure notifications, SMTP, file upload limits, and more
6. **Task Calendar View** — Visual calendar display of upcoming maintenance work
7. **Database Health Dashboard** — Backup, restore, and manage plugin data

== Changelog ==

= 1.0 - March 2026 =
* Initial release on WordPress.org
* Task creation and management with full status workflow
* Public submission form with CAPTCHA protection
* Service provider token-based access system
* Email notifications for task status changes
* Multiple display shortcodes (list, form, calendar, summary)
* Image upload support with validation
* Comprehensive admin interface
* Database optimization and caching
* Security audit completion (nonce verification, input sanitization, output escaping)

== Upgrade Notice ==

= 1.0 =
First public release. Fresh installation required. No upgrade path from previous versions.

== Support ==

For documentation, tutorials, and support:
* **GitHub:** https://github.com/anthonyhorne/upkeepify
* **Issue Tracker:** https://github.com/anthonyhorne/upkeepify/issues

== License ==

This plugin is licensed under the GPL v2 or later. See LICENSE file for details.

== Credits ==

* **Author:** Anthony Horne
* **Company:** Zero Admin (Pty) Ltd

== Roadmap ==

Future planned features include:
* Contractor compliance tracking (tax clearance, insurance expiry)
* Multi-scheme management for property agents
* 10-year maintenance planning with budget forecasting
* Reserve fund health dashboard
* Anonymized maintenance cost benchmarking (opt-in)
* Gutenberg blocks for easier page building
