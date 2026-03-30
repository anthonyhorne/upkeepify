# Upkeepify Documentation

Upkeepify is a WordPress plugin for managing maintenance operations in residential complexes and housing communities.

---

## Contents

| Document | Description |
|---|---|
| [Installation](installation.md) | How to install the plugin |
| [Usage](usage.md) | How to use the plugin as an admin, resident, or service provider |
| [Shortcodes](shortcodes.md) | All available shortcodes and their attributes |
| [Settings](settings.md) | Admin settings reference |
| [Development](development.md) | Local dev setup, coding standards, running tests |
| [Data Schema](DATA_SCHEMA.md) | Post types, taxonomies, meta keys, and option structure |
| [Migrations](MIGRATIONS.md) | Database migration system and version history |
| [Database Optimization](DATABASE_OPTIMIZATION.md) | Index creation and performance tuning |
| [Performance Optimization](PERFORMANCE_OPTIMIZATION.md) | Caching strategy and benchmarks |
| [Contributing](contributing.md) | How to contribute to the project |
| [Changelog](changelog.md) | Version history |

---

## Quick Start

1. Upload the plugin to `wp-content/plugins/upkeepify/` and activate it
2. The setup wizard runs automatically and inserts sample categories, statuses, and providers
3. Add `[upkeepify_task_form]` to any page to accept resident submissions
4. Add `[upkeepify_list_tasks]` to display all maintenance tasks
5. Configure notifications and SMTP under **Maintenance Tasks → Settings**

## User Roles

- **Administrators** — manage tasks, providers, and settings via the WordPress dashboard
- **Residents** — submit requests via the public form shortcode
- **Service Providers** — respond to assigned tasks via a secure token link, no WordPress account needed
