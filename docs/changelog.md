# Changelog

All notable changes to Upkeepify are documented here.

---

## 1.2.0 — 2026-04-21

### Added
- Visible plugin version label on the public request form to make live build verification easier during testing
- Public submission success/error feedback with redirect-back notices instead of silent failures
- Repo bump script for synchronized `major`, `minor`, and `patch` version updates across plugin metadata files
- Semantic versioning policy and release-planning notes in the project documentation

### Changed
- Adopted semantic versioning as the default release policy for future plugin updates

## 1.0.0 — 2026-03-30

Initial release.

### Added
- Maintenance Tasks custom post type with statuses: Open, In Progress, On Hold, Completed
- Service Provider directory with token-based response access (no WordPress account required)
- Public task submission form with math CAPTCHA, GPS location capture, and image uploads (JPG/PNG/GIF, max 2MB)
- Display shortcodes: `[upkeepify_maintenance_tasks]`, `[upkeepify_list_tasks]`, `[upkeepify_task_form]`, `[upkeepify_task_summary]`, `[upkeepify_task_calendar]`, `[upkeepify_tasks_by_category]`, `[upkeepify_tasks_by_provider]`, `[upkeepify_tasks_by_status]`, `[upkeepify_provider_response_form]`
- Admin settings page: SMTP, notifications, token updates, currency, unit count, thank-you page
- Setup wizard with automatic sample data insertion on first activation
- Email notifications with configurable SMTP and override recipient
- Multi-layer caching: WordPress object cache + transients with group-based invalidation
- Database performance indexes for `wp_postmeta` and `wp_posts`
- Migration system with rollback support and database health dashboard
- Meta field registry for schema verification and developer documentation
- PHPCS and PHPUnit configured with 150 unit tests across 6 modules
- JS assets minified via esbuild build pipeline
