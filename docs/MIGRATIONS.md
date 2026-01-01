# Upkeepify Migrations

Upkeepify migrations manage **logical schema changes** for data stored in WordPress core tables.

Because Upkeepify does **not** create custom DB tables, migrations typically handle:
- option structure changes (new keys, renamed keys, defaults)
- meta key changes (rename, type normalization)
- default taxonomy term creation
- repair/verification routines

Migration implementation lives in:
- `includes/migrations.php`

## Versioning

- **Code version:** `UPKEEPIFY_DB_VERSION` (in `includes/constants.php`)
- **Stored version:** `UPKEEPIFY_OPTION_DB_VERSION` (`wp_options`)

On activation:
1. `upkeepify_setup_database()` runs (registers CPT/taxonomies + ensures defaults)
2. `upkeepify_run_migrations()` runs pending migrations to reach `UPKEEPIFY_DB_VERSION`

On every admin page load:
- an admin notice is shown if migrations are pending
- the Database Health page provides a “Run Migrations” button

## Authoring Rules

All migrations must:
1. Follow the naming convention: `upkeepify_migrate_vX_to_vY()`
2. Be **idempotent** (safe to run multiple times)
3. Avoid direct SQL unless absolutely necessary (prefer WP APIs)
4. Return `true` on success or `WP_Error` on failure
5. Add rollback support where feasible (especially if destructive)

Rollback naming convention:
- `upkeepify_rollback_vY_to_vX()`

## Migration Template

1) Increment `UPKEEPIFY_DB_VERSION`.

2) Add a migration function in `includes/migrations.php`:

```php
function upkeepify_migrate_v2_to_v3() {
    // 1) Read existing data
    $settings = get_option(UPKEEPIFY_OPTION_SETTINGS, array());

    // 2) Transform / validate (idempotent)
    if (!isset($settings['new_key'])) {
        $settings['new_key'] = 'default';
    }

    $validated = upkeepify_validate_settings($settings);
    if (is_wp_error($validated)) {
        return $validated;
    }

    // 3) Persist
    update_option(UPKEEPIFY_OPTION_SETTINGS, $validated, false);

    // 4) Optional: ensure taxonomy defaults
    upkeepify_ensure_default_terms();

    return true;
}
```

3) (Optional) Add a rollback function if needed:

```php
function upkeepify_rollback_v3_to_v2() {
    // Avoid deleting user content unless absolutely required.
    // Prefer reverting option/meta structure only.
    return true;
}
```

## Testing Checklist

Test each migration in two scenarios:

### A) Fresh install
- Activate plugin
- Confirm `upkeepify_db_version` equals `UPKEEPIFY_DB_VERSION`
- Confirm default options exist and are valid

### B) Upgrade
- Install/activate older version (or simulate by setting `upkeepify_db_version` lower)
- Update plugin code
- Activate or click “Run Migrations” in admin
- Confirm:
  - version updates to latest
  - migration history is recorded in `upkeepify_migration_history`
  - data remains readable by shortcodes/admin screens

## Logging & History

- `UPKEEPIFY_OPTION_MIGRATION_LOG` stores a rolling log (latest ~200 lines)
- `UPKEEPIFY_OPTION_MIGRATION_HISTORY` stores structured run history entries
