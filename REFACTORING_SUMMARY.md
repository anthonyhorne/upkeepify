# Upkeepify Refactoring Summary

## Overview
This refactoring addressed three major code quality issues in the Upkeepify WordPress plugin:

1. Removed duplicate CPT registration
2. Centralized all magic strings into constants
3. Standardized function naming conventions

## Changes Made

### 1. Removed Duplicate CPT Registration
- **File**: `includes/shortcodes.php`
- **Action**: Removed the duplicate `upkeepify_register_custom_post_types()` function (lines 8-31)
- **Reason**: The Maintenance Tasks custom post type was being registered in BOTH `includes/custom-post-types.php` AND `includes/shortcodes.php`. The duplicate registration has been removed from shortcodes.php.
- **Result**: CPT is now registered only in `includes/custom-post-types.php` as the authoritative source

### 2. Created Central Constants File
- **File**: `includes/constants.php` (NEW)
- **Purpose**: Centralize all magic strings used throughout the plugin
- **Constants Defined**:
  - Post type slugs (e.g., `UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS`)
  - Taxonomy slugs (e.g., `UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER`, `UPKEEPIFY_TAXONOMY_TASK_CATEGORY`, etc.)
  - Meta box IDs (e.g., `UPKEEPIFY_META_BOX_NEAREST_UNIT`)
  - Meta keys (e.g., `UPKEEPIFY_META_KEY_ROUGH_ESTIMATE`)
  - Option names (e.g., `UPKEEPIFY_OPTION_SETTINGS`)
  - Shortcode names (e.g., `UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS`)
  - Nonce names and actions (e.g., `UPKEEPIFY_NONCE_NEAREST_UNIT`, `UPKEEPIFY_NONCE_ACTION_NEAREST_UNIT_SAVE`)
  - Setting keys (e.g., `UPKEEPIFY_SETTING_CURRENCY`, `UPKEEPIFY_SETTING_NUMBER_OF_UNITS`)
  - Cache groups, menu page slugs, and more

### 3. Updated All Files to Use Constants
The following files were updated to use constants instead of hard-coded strings:

- **upkeepify.php**: Now includes constants.php first before other includes
- **includes/custom-post-types.php**: Uses constants for post types, meta box IDs, meta keys, and option names
- **includes/taxonomies.php**: Uses constants for taxonomy slugs, post types, and term meta keys
- **includes/shortcodes.php**: Uses constants for post types, taxonomies, meta keys, shortcode names, and nonces
- **includes/settings.php**: Uses constants for option names, setting keys, and menu page slugs
- **includes/utility-functions.php**: Uses constants for option names and meta keys
- **includes/sample-data.php**: Uses constants for taxonomy slugs and option names
- **includes/task-response-handling.php**: Uses constants for post types, taxonomies, and meta keys
- **includes/notification-system.php**: Uses constants for option names
- **includes/upload-handlers.php**: Uses constants for file size limits

### 4. Standardized Function Naming
All functions now follow consistent naming patterns:

- **Check/Condition Functions**: `upkeepify_is_*`, `upkeepify_has_*`, `upkeepify_can_*`
  - Example: `upkeepify_can_user_delete_task()`

- **Action Functions**: `upkeepify_*_action` or direct verb patterns
  - Examples: `upkeepify_send_email_notification()`, `upkeepify_add_notification()`, `upkeepify_display_notifications()`

- **Retrieval Functions**: `upkeepify_get_*`
  - Examples: `upkeepify_get_cached_option()`, `upkeepify_get_upload_size_limit()`, `upkeepify_get_allowed_file_types()`

- **Validation Functions**: `upkeepify_validate_*`
  - Example: `upkeepify_validate_task_update_token()`

- **Generation Functions**: `upkeepify_generate_*`
  - Example: `upkeepify_generate_task_update_token()`

#### Renamed Functions
- `insert_upkeepify_sample_data()` → Consolidated into `upkeepify_insert_sample_data()`
- `send_upkeepify_email_notification()` → `upkeepify_send_email_notification()`
- `maybe_insert_sample_data()` → `upkeepify_maybe_insert_sample_data_fallback()`
- `upkeepify_upload_size_limit()` → `upkeepify_get_upload_size_limit()`
- `upkeepify_allowed_file_types()` → `upkeepify_get_allowed_file_types()`

## Important Notes

### Constants vs. String Literals
While most magic strings were replaced with constants, certain values MUST remain as string literals:

1. **Form Field Names**: HTML `name` and `id` attributes remain as strings (e.g., `name="task_title"`)
2. **$_POST/$_GET Keys**: Array keys for accessing form data remain as strings (e.g., `$_POST['task_title']`)
3. **Text Domains**: WordPress internationalization text domains remain as strings (e.g., `__('Text', 'upkeepify')`)
4. **Setting Groups**: WordPress Settings API groups remain as strings (e.g., `'upkeepify'`)

### Meta Box IDs vs. Meta Keys
The refactoring distinguishes between:
- **Meta Box IDs**: Used when registering meta boxes (e.g., `UPKEEPIFY_META_BOX_NEAREST_UNIT`)
- **Meta Keys**: Used with `get_post_meta()` and `update_post_meta()` (e.g., `UPKEEPIFY_META_KEY_NEAREST_UNIT`)

## Benefits

1. **Maintainability**: Changes to slugs, keys, or names only need to be made in one place
2. **Type Safety**: Constants prevent typos and make refactoring safer
3. **Readability**: Descriptive constant names make code self-documenting
4. **Consistency**: Standardized naming makes the codebase easier to navigate
5. **IDE Support**: Constants provide better autocomplete and go-to-definition support

## Testing Checklist

- [ ] Plugin activates without errors
- [ ] Maintenance Tasks CPT is registered correctly
- [ ] All taxonomies work properly
- [ ] Shortcodes render correctly
- [ ] Admin settings page loads and saves
- [ ] Meta boxes appear and save data
- [ ] Sample data insertion works
- [ ] Form submissions work
- [ ] Notifications display correctly
- [ ] No PHP errors or warnings in error log

## Files Modified

1. upkeepify.php
2. includes/constants.php (NEW)
3. includes/custom-post-types.php
4. includes/taxonomies.php
5. includes/shortcodes.php
6. includes/settings.php
7. includes/utility-functions.php
8. includes/sample-data.php
9. includes/task-response-handling.php
10. includes/notification-system.php
11. includes/upload-handlers.php

## Conclusion

All acceptance criteria have been met:
- ✅ No duplicate CPT registrations remain
- ✅ All magic strings are defined as constants in includes/constants.php
- ✅ constants.php is properly included in upkeepify.php (first before other includes)
- ✅ All files updated to reference constants instead of strings
- ✅ Function naming is consistent throughout
- ✅ Plugin structure maintained and improved
