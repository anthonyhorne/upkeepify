# Changelog

All notable changes to Upkeepify are documented here.

---

## 2.1.0 — 2026-06-25

### Added
- Trustee task status controls to put a whole task **On Hold** or **Cancel** it (and resume it later); the manual override wins over the automatic lifecycle status so parked/cancelled jobs don't get lost
- **Job sharing** between two complexes/HOAs: a task can be flagged as shared with a partner complex whose trustees then receive informational progress emails (share, estimate approval, quote approval, completion), plus Shared Complex Name and Shared Complex Trustee Emails settings
- Colour-coded **status key** (green = on track/done, orange = waiting/on hold, red = needs attention/cancelled) with status badges and a legend in the wp-admin Lifecycle column and the `[upkeepify_list_tasks]` table
- `[upkeepify_status_key]` shortcode to render the status legend on its own

### Changed
- Lifecycle status options now include Open, Completed, and On Hold so the admin status filter covers held and cancelled tasks
- Lifecycle status terms are seeded on demand so On Hold / Rejected always apply

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
