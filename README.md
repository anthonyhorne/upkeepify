# Upkeepify

Upkeepify is a comprehensive WordPress plugin designed to manage maintenance tasks within a complex. It supports task submissions with categorization, service provider management, and customizable settings. Both trustees and residents can initiate and track maintenance requests with features like task visibility settings, image attachments, status updates, and provider response tracking to facilitate transparent and efficient maintenance operations.

## Features

### Task Management
- **Task Creation and Tracking**: Create and track maintenance tasks with detailed information, including progress percentage, estimated timelines, and status updates
- **Custom Post Types**: Dedicated Maintenance Tasks post type with custom fields for nearest unit and rough estimates
- **Task Categories & Types**: Organize tasks by category (General, Electrical, Plumbing, etc.) and type (Repair, Inspection, Installation)
- **Status Tracking**: Track task progress through statuses (Open, In Progress, Completed, On Hold)

### Service Provider Management
- **Provider Directory**: Manage service providers with contact information (phone, email)
- **Provider Associations**: Link providers to specific task categories
- **Token-Based Access**: Generate unique tokens for providers to submit responses without WordPress accounts
- **Provider Permissions**: Configure whether providers can delete tasks

### User Features
- **Task Submission Form**: Public-facing form with CAPTCHA protection
- **GPS Location Capture**: Automatically capture task location coordinates
- **Image Uploads**: Attach images to tasks (max 2MB, JPG/PNG/GIF only)
- **Multiple Shortcodes**: Display tasks in various formats (lists, tables, summaries, calendar)

### Administration
- **Admin Settings**: Configure SMTP, notifications, upload limits, currency, and more
- **Setup Wizard**: Quick initial configuration guide
- **Caching**: Built-in caching for improved performance
- **Notification System**: Admin notices and email notifications for status changes

## Installation

### Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Memory limit: 64MB or higher recommended

### Method 1: WordPress Plugin Repository (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "Upkeepify"
4. Click **Install Now** next to the plugin
5. Click **Activate** after installation completes
6. Follow the Setup Wizard prompts to configure basic settings

### Method 2: Manual Installation

1. **Download the Plugin**
   - Visit the [GitHub Releases](https://github.com/anthonyhorne/upkeepify/releases) page
   - Download the latest `.zip` file
   - Alternatively, clone the repository:
     ```bash
     git clone https://github.com/anthonyhorne/upkeepify.git
     cd upkeepify
     # Create zip file
     zip -r upkeepify.zip .
     ```

2. **Upload to WordPress**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin**
   - Select the `upkeepify.zip` file
   - Click **Install Now**
   - Click **Activate** after installation completes

3. **Configure the Plugin**
   - Go to **Maintenance Tasks → Setup Wizard**
   - Follow the prompts to configure:
     - Number of units in your complex
     - Currency symbol
     - Email notification settings
     - Provider permissions

### Method 3: FTP/SFTP Installation

1. **Download Plugin Files**
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/anthonyhorne/upkeepify.git
   ```

2. **Set Permissions**
   ```bash
   chmod 755 upkeepify
   chmod 644 upkeepify/*.php
   ```

3. **Activate in WordPress**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins → Installed Plugins**
   - Find "Upkeepify" and click **Activate**

### Post-Installation Configuration

After activation, complete these steps:

1. **Configure Settings**
   - Visit **Maintenance Tasks → Settings**
   - Review and adjust:
     - **General Settings**: SMTP, notifications, public task logging
     - **Thank You Page**: Configure custom thank you page for providers
     - **Number of Units**: Set total number of units in your complex
     - **Currency**: Choose appropriate currency symbol

2. **Review Sample Data**
   - Sample task categories, types, and statuses are automatically inserted
   - Sample service providers are added for testing
   - Edit or delete these as needed

3. **Test the Plugin**
   - Create a test maintenance task
   - Test the public submission form
   - Upload an image to verify file handling
   - Check that GPS coordinates are captured

## Usage

### For Administrators

#### Creating and Managing Tasks

1. **Create a New Task**
   - Navigate to **Maintenance Tasks → Add New**
   - Enter task title and description
   - Select appropriate category, type, and status
   - Set "Nearest Unit" from sidebar
   - Add a "Rough Estimate" if applicable
   - Click **Publish**

2. **Edit Existing Tasks**
   - Go to **Maintenance Tasks → All Tasks**
   - Click on the task you want to edit
   - Make your changes
   - Click **Update**

3. **Manage Task Statuses**
   - Update status to track progress
   - Add comments for stakeholders
   - Upload progress images if needed

#### Managing Service Providers

1. **Add a New Provider**
   - Go to **Service Providers → Add New**
   - Enter provider name
   - Add contact information (phone, email)
   - Select associated task categories
   - Click **Add New Service Provider**

2. **Edit Provider Information**
   - Go to **Service Providers → All Providers**
   - Hover over provider name and click **Edit**
   - Update information as needed
   - Click **Update**

3. **Generate Provider Tokens**
   - When a task is published, response posts are automatically created
   - Each provider gets a unique token to access their response form
   - Share tokens with providers via email or other secure method

#### Configuring Settings

Access settings at **Maintenance Tasks → Settings**:

- **General Settings**:
  - **Use Built-in SMTP Provider**: Enable/disable SMTP
  - **SMTP Host**: Set SMTP server address
  - **Notify on Status Change**: Send email when status changes
  - **Allow Service Provider to Delete Task**: Grant delete permission to providers
  - **Allow Public Task Logging**: Enable public task submissions
  - **Override Email Address**: Set custom email for notifications
  - **Enable Token-based Status Update**: Allow providers to update status with tokens
  - **Number of Units**: Set total units in complex
  - **Currency**: Set currency symbol

- **Provider Thank You Page Settings**:
  - **Enable Custom Thank You Page**: Show custom page after provider response
  - **Thank You Page URL**: Set custom thank you page URL

### For Residents

#### Submitting a Task

1. Use the public task submission form by adding this shortcode to a page:
   ```
   [upkeepify_task_form]
   ```

2. **Fill in the Form**:
   - Task Title: Brief description of the issue
   - Task Description: Detailed explanation of the problem
   - Category: Select appropriate category
   - Type: Choose task type
   - Status: Usually set to "Open"
   - Upload Photo: Add an image if helpful
   - GPS Location: Click field to capture location (requires permission)
   - Answer CAPTCHA: Solve simple math problem

3. **Submit**:
   - Click "Submit Task"
   - You'll see a confirmation message
   - Your task will be reviewed and assigned

#### Viewing Tasks

Use these shortcodes to display tasks:

- **Display All Tasks in Table Format**:
  ```
  [upkeepify_list_tasks]
  ```

- **Display Tasks by Category**:
  ```
  [upkeepify_tasks_by_category category="plumbing"]
  ```

- **Display Tasks by Status**:
  ```
  [upkeepify_tasks_by_status status="open"]
  ```

- **Display Task Summary**:
  ```
  [upkeepify_task_summary]
  ```

- **Display Task Calendar**:
  ```
  [upkeepify_task_calendar]
  ```

### For Service Providers

#### Responding to Tasks

1. **Receive Your Token**
   - You'll receive a unique token via email when a task is assigned
   - Keep this token secure as it provides access to your response form

2. **Access Response Form**
   - Use the provider response form shortcode with your token:
     ```
     [upkeepify_provider_response_form token="YOUR_UNIQUE_TOKEN"]
     ```
   - This form will show task details relevant to you

3. **Submit Your Response**
   - Add your response text
   - Include estimated cost if applicable
   - Submit your response

## Development

### Setting Up Development Environment

For contributors and developers, see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/anthonyhorne/upkeepify.git
   cd upkeepify
   ```

2. **Install Dependencies** (if applicable):
   ```bash
   composer install
   npm install
   ```

3. **Create Symlink for Testing**:
   ```bash
   ln -s /path/to/upkeepify /path/to/wordpress/wp-content/plugins/upkeepify
   ```

4. **Enable Debug Mode**:
   In `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

### Code Structure

```
upkeepify/
├── upkeepify.php              # Main plugin file
├── README.md                  # This file
├── CONTRIBUTING.md             # Contribution guidelines
├── REFACTORING_SUMMARY.md    # Refactoring documentation
├── favicon.png                # Plugin icon
├── upkeepify-styles.css       # Frontend styles
├── includes/                  # Core functionality
│   ├── constants.php          # All constants (magic strings)
│   ├── custom-post-types.php  # CPT registration and meta boxes
│   ├── taxonomies.php         # Taxonomy registration
│   ├── settings.php            # Admin settings and configuration
│   ├── shortcodes.php          # Frontend shortcodes
│   ├── utility-functions.php   # Helper functions
│   ├── admin-functions.php    # Admin-specific functions
│   ├── notification-system.php # Notifications and alerts
│   ├── task-response-handling.php # Provider responses
│   ├── upload-handlers.php     # File upload validation
│   └── sample-data.php         # Sample data insertion
└── js/                        # JavaScript files
    └── admin-settings.js      # Admin settings behavior
```

### Architecture

The plugin follows a modular architecture:

- **Constants-First Approach**: All magic strings are centralized in `includes/constants.php`
- **Function Naming**: All functions use `upkeepify_` prefix with descriptive patterns
- **WordPress APIs Only**: No custom database tables, uses WordPress core APIs
- **Caching**: Built-in caching layer for frequently accessed options
- **Security**: Nonce verification, input sanitization, and output escaping

## Troubleshooting

### Common Issues

#### Plugin Won't Activate

**Problem**: Plugin activation fails or white screen appears.

**Solutions**:
1. Check PHP version (requires 7.2+)
2. Check WordPress version (requires 5.0+)
3. Enable debug mode to see error:
   ```php
   define('WP_DEBUG', true);
   ```
4. Check server error logs
5. Increase PHP memory limit in `wp-config.php`:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

#### Tasks Not Displaying

**Problem**: Shortcodes don't show any tasks.

**Solutions**:
1. Verify tasks have "Published" status (not "Draft")
2. Check that tasks are assigned to the correct post type
3. Clear browser and plugin caches
4. Verify shortcodes are correctly formatted
5. Check WordPress debug log for errors

#### File Uploads Failing

**Problem**: Image uploads fail or show errors.

**Solutions**:
1. Check file size (max 2MB)
2. Verify file type (JPG, PNG, GIF only)
3. Check folder permissions (755 for directories, 644 for files)
4. Verify PHP upload_max_filesize setting in php.ini
5. Check WordPress Media Library upload limit

#### CAPTCHA Not Working

**Problem**: CAPTCHA always shows incorrect error.

**Solutions**:
1. Ensure PHP sessions are enabled on server
2. Clear browser cookies and cache
3. Check that JavaScript is enabled
4. Verify no caching plugins are interfering
5. Check WordPress debug log for session errors

#### GPS Location Not Capturing

**Problem**: GPS coordinates remain blank.

**Solutions**:
1. User must click the coordinate field to trigger browser prompt
2. Ensure user grants location permission when prompted
3. Test in HTTPS environment (HTTP may block geolocation)
4. Check browser console for geolocation errors
5. Verify browser supports Geolocation API

#### Settings Not Saving

**Problem**: Changes to settings don't persist.

**Solutions**:
1. Clear browser cache and try again
2. Disable caching plugins temporarily
3. Check WordPress options table in database
4. Verify database user has write permissions
5. Check WordPress debug log for errors

#### Email Notifications Not Sending

**Problem**: Notification emails don't arrive.

**Solutions**:
1. Verify email notification is enabled in settings
2. Check WordPress "Settings → General" admin email
3. Test with wp_mail() using debugging plugin
4. Check spam/junk folders
5. Verify SMTP settings if using custom SMTP
6. Check server mail logs for errors
7. Verify hosting provider allows outbound emails

### Getting Help

If you encounter issues not covered here:

1. **Check the Docs**: Review this README and [CONTRIBUTING.md](CONTRIBUTING.md)
2. **Search Issues**: Check existing [GitHub Issues](https://github.com/anthonyhorne/upkeepify/issues)
3. **Create New Issue**:
   - Provide detailed description of the problem
   - Include WordPress version
   - Include PHP version
   - Include error messages or screenshots
   - Describe steps to reproduce the issue

4. **Enable Debug Mode** (when requesting help):
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
   Then share the contents of `wp-content/debug.log`

## Changelog

### Version 1.0.0
- Initial release
- Maintenance Tasks custom post type
- Task Categories, Types, and Statuses taxonomies
- Service Providers taxonomy with custom fields
- Public task submission form with CAPTCHA
- GPS location capture
- Image upload support (2MB limit)
- Provider token-based access
- Multiple display shortcodes
- Admin settings panel
- Notification system
- Sample data on activation
- Caching layer for options

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Code standards and style guidelines
- Function naming conventions
- Development setup instructions
- Testing guidelines
- Pull request process

## License

Upkeepify is open-sourced software licensed under the [GPLv2 license](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).

## Credits

- **Anthony Horne**: Plugin Development and Concept
- **OpenAI's ChatGPT**: Assistance with Code Examples and Logic
- **WordPress Community**: Various Tutorials and Code Snippets

## Support

- **Documentation**: [README.md](README.md) and [CONTRIBUTING.md](CONTRIBUTING.md)
- **Issues**: [GitHub Issues](https://github.com/anthonyhorne/upkeepify/issues)
- **Repository**: [https://github.com/anthonyhorne/upkeepify](https://github.com/anthonyhorne/upkeepify)

## Performance & Optimization

### Caching System

Upkeepify includes a comprehensive caching system to improve performance:

- **Settings Caching**: Plugin settings are cached to reduce database queries
- **Taxonomy Caching**: Service providers, categories, and terms are cached
- **Shortcode Output Caching**: Shortcode results are cached for faster page loads
- **Query Result Caching**: Frequently used query results are cached

Cache is automatically cleared when posts, terms, or settings are updated.

### Query Optimizations

All WP_Query calls in Upkeepify use performance optimizations:

- `no_found_rows`: Disabled when pagination is not needed
- `fields`: Limits returned data to only what's needed
- `posts_per_page`: Explicit limits instead of unlimited queries
- Proper indexing on frequently queried meta fields

For more details on database optimization, see [Database Optimization Guide](docs/DATABASE_OPTIMIZATION.md).

### Debug Mode

Enable `WP_DEBUG` to log query performance:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Performance logs will appear in your WordPress debug log file.

## Roadmap

Future planned features:
- [ ] Automated email reminders for overdue tasks
- [ ] Mobile app for residents
- [ ] Advanced reporting and analytics
- [ ] Multi-language support
- [ ] Integration with popular project management tools
- [ ] Automated task assignment based on provider availability
- [ ] SMS notifications for urgent tasks
- [ ] Task templates for common issues
- [ ] Approval workflow for public submissions
- [ ] Cost tracking and budget management
