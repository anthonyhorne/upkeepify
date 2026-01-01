# Contributing to Upkeepify

Thank you for your interest in contributing to Upkeepify! This document provides guidelines and instructions for contributing to the WordPress plugin.

## Table of Contents

- [Code Standards](#code-standards)
- [Function Naming Conventions](#function-naming-conventions)
- [Development Setup](#development-setup)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Coding Style Guidelines](#coding-style-guidelines)

## Code Standards

### WordPress Coding Standards

Upkeepify follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). Please adhere to these standards when contributing:

- Use proper PHPDoc blocks for all functions and classes
- Follow WordPress naming conventions for functions, variables, and classes
- Use proper escaping and sanitization functions
- Follow WordPress database queries standards
- Use text domains for internationalization

### PHPCS Configuration

We recommend using PHP_CodeSniffer with the WordPress Coding Standards to check your code:

```bash
# Install the WordPress Coding Standards
composer global require "wp-coding-standards/wpcs"

# Run the sniffer
phpcs --standard=WordPress --extensions=php .
```

## Function Naming Conventions

All functions in Upkeepify follow a consistent naming pattern with the `upkeepify_` prefix:

### Checking Functions
Functions that check conditions should use:
- `upkeepify_is_*` - for boolean checks
- `upkeepify_has_*` - for capability checks

```php
function upkeepify_can_user_delete_task($task_id) {
    // Check if user can delete task
}
```

### Action Functions
Functions that perform actions should use:
- `upkeepify_*_action` - for action callbacks
- Direct verb patterns (e.g., `upkeepify_send_*`, `upkeepify_add_*`, `upkeepify_display_*`)

```php
function upkeepify_send_status_change_email($task_id, $new_status) {
    // Send email notification
}
```

### Retrieval Functions
Functions that retrieve data should use:
- `upkeepify_get_*` - for data retrieval

```php
function upkeepify_get_cached_option($option_name) {
    // Retrieve cached option
}
```

### Validation Functions
Functions that validate input should use:
- `upkeepify_validate_*` - for input validation

```php
function upkeepify_validate_task_update_token($task_id, $token) {
    // Validate task update token
}
```

### Generation Functions
Functions that generate something should use:
- `upkeepify_generate_*` - for generating tokens, content, etc.

```php
function upkeepify_generate_task_update_token($task_id) {
    // Generate unique token
}
```

## Magic Strings and Constants

**Important:** All magic strings (post types, taxonomies, meta keys, option names, etc.) are centralized in `includes/constants.php`. Never use hardcoded strings in your code.

```php
// CORRECT - Use constants
register_post_type(UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS, $args);
get_post_meta($post_id, UPKEEPIFY_META_KEY_NEAREST_UNIT, true);

// INCORRECT - Don't use hardcoded strings
register_post_type('maintenance_tasks', $args);
get_post_meta($post_id, 'upkeepify_nearest_unit', true);
```

### Constants File Order

The `includes/constants.php` file must be included first in `upkeepify.php` before any other includes. This ensures all constants are defined before they're used.

## Development Setup

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Git

### Setting Up Your Development Environment

1. **Fork the Repository**

   Click the "Fork" button on the GitHub repository page to create your own fork.

2. **Clone Your Fork**

   ```bash
   git clone https://github.com/yourusername/upkeepify.git
   cd upkeepify
   ```

3. **Add Upstream Remote**

   ```bash
   git remote add upstream https://github.com/anthonyhorne/upkeepify.git
   ```

4. **Create a Development Branch**

   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-description
   ```

5. **Install in WordPress**

   Copy the entire plugin directory to your WordPress installation:
   ```bash
   cp -r upkeepify /path/to/wordpress/wp-content/plugins/
   ```

6. **Activate the Plugin**

   - Log in to your WordPress admin dashboard
   - Navigate to Plugins → Installed Plugins
   - Find "Upkeepify" and click "Activate"

### Development Workflow

1. Make your changes on your feature branch
2. Test thoroughly (see [Testing](#testing))
3. Commit your changes with clear messages
4. Push to your fork
5. Create a pull request

## Testing

### Manual Testing

Since Upkeepify doesn't currently have automated tests, manual testing is essential:

#### Test Checklist

- [ ] **Post Type Registration**
  - Maintenance Tasks CPT appears in admin menu
  - Can create, edit, and delete tasks
  - Meta boxes (Nearest Unit, Rough Estimate) display correctly
  - Meta data saves and retrieves properly

- [ ] **Taxonomies**
  - Task Categories work correctly
  - Task Types display properly
  - Task Statuses function as expected
  - Service Provider taxonomy shows custom fields (phone, email, categories)

- [ ] **Settings**
  - Settings page loads without errors
  - All settings save correctly
  - Settings persist across page reloads
  - Caching works properly

- [ ] **Shortcodes**
  - `[maintenance_tasks]` displays tasks
  - `[upkeepify_list_tasks]` shows table format
  - `[upkeepify_task_form]` renders form correctly
  - Form submission works with CAPTCHA
  - GPS coordinates capture properly
  - File uploads work within size limits

- [ ] **Upload Handlers**
  - File size limit enforced (2MB)
  - Allowed file types restricted (JPG, PNG, GIF)
  - Error messages display correctly

- [ ] **Notifications**
  - Admin notifications display
  - Email notifications send when enabled

- [ ] **Sample Data**
  - Sample data inserts on activation
  - Sample data doesn't insert again on reactivation

### Testing Checklist for New Features

When adding new features, ensure:
- Function follows naming conventions
- Proper PHPDoc block added
- Uses constants from `constants.php`
- Input is properly sanitized
- Output is properly escaped
- Works in both admin and front-end contexts (if applicable)
- Compatible with existing features

### Cross-Browser Testing

Test your changes in:
- Latest versions of Chrome, Firefox, Safari, Edge
- Mobile browsers (iOS Safari, Chrome Mobile)
- Different screen sizes

## Pull Request Process

### Creating a Pull Request

1. **Update Your Branch**

   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Commit Your Changes**

   ```bash
   git add .
   git commit -m "Brief description of changes"
   ```

   Use clear, descriptive commit messages:
   ```
   Add provider response form shortcode

   - Implements token-based access for providers
   - Validates tokens before displaying form
   - Includes proper PHPDoc blocks
   ```

3. **Push to Your Fork**

   ```bash
   git push origin feature/your-feature-name
   ```

4. **Create Pull Request**

   - Go to your fork on GitHub
   - Click "Pull Requests"
   - Click "New Pull Request"
   - Select your branch
   - Provide a clear title and description

### Pull Request Template

```markdown
## Description
Brief description of what this PR does and why.

## Changes Made
- Bullet list of changes
- Including any breaking changes

## Testing
- [ ] Tested manually
- [ ] Tested in multiple browsers
- [ ] Checked for PHP errors
- [ ] Verified no regression bugs

## Screenshots (if applicable)
Add screenshots for visual changes
```

### What to Expect

- **Code Review**: Maintainers will review your code for quality, style, and adherence to standards
- **Feedback**: You may receive requests for changes
- **Timeline**: We aim to review all PRs within 1-2 weeks
- **Merge**: Once approved, your PR will be merged into the main branch

### Addressing Review Feedback

1. Make requested changes on your branch
2. Push updates to your fork
3. Comment on the PR to notify reviewers
4. Repeat until approved

## Coding Style Guidelines

### PHP Style

#### Indentation
- Use **real tabs** for indentation (not spaces)
- Align code block opening and closing braces

```php
function upkeepify_example_function($param) {
	if ($param) {
		// Code here
	}
}
```

#### Spacing
- Space after commas in function calls and arrays
- Space around operators (`=`, `+`, `-`, etc.)
- No space before opening parenthesis in function calls

```php
// CORRECT
$variable = array('key' => 'value', 'key2' => 'value2');
upkeepify_function($param1, $param2);

// INCORRECT
$variable=array('key'=>'value','key2'=>'value2');
upkeepify_function ($param1,$param2);
```

#### Quotes
- Use **single quotes** for strings unless interpolation is needed
- Use double quotes only when required (e.g., for HTML attributes or string interpolation)

```php
// CORRECT
$string = 'simple string';
$html = '<a href="link">text</a>';

// INCORRECT
$string = "simple string";
$html = "<a href='link'>text</a>";
```

#### Arrays
- Use `array()` syntax for consistency with WordPress standards
- For multi-line arrays, place each element on its own line

```php
$args = array(
	'post_type'   => 'maintenance_tasks',
	'post_status' => 'publish',
	'numberposts' => -1,
);
```

#### Control Structures
- Use braces for all control structures (even single-line)
- Place opening brace on same line as statement
- Closing brace on new line at same indentation level

```php
if (condition) {
	// Code here
} elseif (another_condition) {
	// Code here
} else {
	// Code here
}

foreach ($items as $item) {
	// Code here
}
```

### JavaScript Style

- Use jQuery (already included in WordPress)
- Encapsulate code in document ready handler
- Use WordPress-specific functions where possible

```javascript
jQuery(document).ready(function($) {
	// Your code here
});
```

### CSS Style

- Use descriptive class names with prefix
- Organize by section
- Add comments for complex styles

```css
.upkeepify-form {
	/* Form styles */
	margin: 20px 0;
}

.upkeepify-input {
	/* Input field styles */
	width: 100%;
	padding: 10px;
}
```

### Documentation Standards

#### PHPDoc Format

All public functions must have a PHPDoc block:

```php
/**
 * Brief description of function.
 *
 * Detailed description of what the function does,
 * including any important implementation details.
 *
 * @since 1.0
 * @param string $param1 Description of first parameter.
 * @param int    $param2 Description of second parameter.
 * @return bool   Description of return value.
 * @uses upkeepify_helper_function()
 * @hook init
 */
function upkeepify_example_function($param1, $param2) {
	// Function body
}
```

#### Required Tags

- `@since` - Version when function was added (usually 1.0)
- `@param` - For each parameter with type and description
- `@return` - Type and description of return value (if applicable)
- `@uses` - Functions called by this function
- `@hook` - WordPress action/filter hook if applicable

#### Inline Comments

Add inline comments for:
- **Why** something is done (not just what)
- Complex logic or algorithms
- Workarounds or temporary solutions
- Performance considerations

```php
// Check nonce first to prevent CSRF attacks
if (!wp_verify_nonce($nonce, $action)) {
	return;
}

// Use array_reverse() because get_terms() returns newest first
// and we want oldest first for chronological display
$terms = array_reverse(get_terms($args));
```

### Security Guidelines

#### Input Validation

- Always validate and sanitize user input
- Use WordPress sanitization functions:
  - `sanitize_text_field()`
  - `sanitize_textarea_field()`
  - `sanitize_email()`
  - `intval()`, `absint()` for numbers
  - `esc_url()`, `esc_html()`, `esc_attr()` for output

#### Nonce Verification

Always verify nonces before processing form submissions:

```php
// Check nonce
if (!isset($_POST['upkeepify_nonce_field']) ||
    !wp_verify_nonce($_POST['upkeepify_nonce_field'], 'upkeepify_action_name')) {
	return;
}

// Check permissions
if (!current_user_can('edit_posts')) {
	return;
}
```

#### Prepared Statements

Never concatenate user input into SQL queries. Use `$wpdb->prepare()`:

```php
$safe_sql = $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}upkeepify_table WHERE id = %d",
	$user_id
);
```

## Questions?

If you have questions about contributing:
- Check existing issues for similar discussions
- Start a new issue with the "question" label
- Tag maintainers in the issue for faster response

Thank you for contributing to Upkeepify! 🎉
