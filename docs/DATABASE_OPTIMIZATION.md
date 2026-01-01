# Database Optimization Guide for Upkeepify

This document provides recommendations for database indexes and optimization strategies to improve the performance of the Upkeepify WordPress plugin.

## Overview

The Upkeepify plugin uses custom post types, taxonomies, and post meta to manage maintenance tasks. As the number of tasks grows, proper database indexing becomes crucial for maintaining query performance.

## Recommended Database Indexes

### 1. Post Meta Index for Frequently Queried Keys

Add indexes to the `wp_postmeta` table for meta keys that are frequently used in queries.

#### Index for Nearest Unit

The `upkeepify_nearest_unit` meta key is used to filter tasks by their nearest unit number.

```sql
-- Create index for nearest unit queries
CREATE INDEX idx_postmeta_nearest_unit
ON wp_postmeta(post_id, meta_key(50), meta_value(20))
WHERE meta_key = 'upkeepify_nearest_unit';

-- Alternative: Full composite index (better for complex queries)
CREATE INDEX idx_postmeta_nearest_unit_value
ON wp_postmeta(meta_key(50), meta_value(50), post_id)
WHERE meta_key = 'upkeepify_nearest_unit';
```

#### Index for Rough Estimate

The `upkeepify_rough_estimate` meta key stores cost estimates and may be used for sorting or filtering.

```sql
-- Create index for rough estimate queries
CREATE INDEX idx_postmeta_rough_estimate
ON wp_postmeta(post_id, meta_key(50), meta_value(50))
WHERE meta_key = 'upkeepify_rough_estimate';
```

#### Index for Assigned Service Provider

The `assigned_service_provider` meta key links tasks to service providers.

```sql
-- Create index for assigned service provider queries
CREATE INDEX idx_postmeta_assigned_provider
ON wp_postmeta(post_id, meta_key(50), meta_value(50))
WHERE meta_key = 'assigned_service_provider';
```

#### Index for GPS Coordinates

GPS coordinates are stored in separate meta keys and may be used for location-based queries.

```sql
-- Index for latitude queries
CREATE INDEX idx_postmeta_gps_latitude
ON wp_postmeta(post_id, meta_key(50), meta_value(50))
WHERE meta_key = 'upkeepify_gps_latitude';

-- Index for longitude queries
CREATE INDEX idx_postmeta_gps_longitude
ON wp_postmeta(post_id, meta_key(50), meta_value(50))
WHERE meta_key = 'upkeepify_gps_longitude';
```

### 2. Post Indexes for Custom Post Types

#### Index for Maintenance Tasks Status and Date

Tasks are frequently filtered by status and sorted by date.

```sql
-- Composite index for post type, status, and date
CREATE INDEX idx_posts_maintenance_tasks
ON wp_posts(post_type(50), post_status, post_date DESC)
WHERE post_type = 'maintenance_tasks';
```

### 3. Term Relationship Indexes

For taxonomy-based filtering (categories, types, statuses).

```sql
-- Index for term relationships
CREATE INDEX idx_term_relationships_taxonomy
ON wp_term_relationships(object_id, term_taxonomy_id);

-- Existing WordPress indexes are usually sufficient, but this can help
-- with complex taxonomy queries
```

## Query Patterns and Their Indexes

### 1. Tasks by Category/Type/Status

**Query Pattern:**
```php
new WP_Query(array(
    'post_type' => 'maintenance_tasks',
    'tax_query' => array(
        array(
            'taxonomy' => 'task_category',
            'field'    => 'slug',
            'terms'    => $category_slug,
        ),
    ),
));
```

**Indexes Used:**
- `wp_term_relationships.object_id`
- `wp_term_relationships.term_taxonomy_id`

### 2. Tasks by Nearest Unit

**Query Pattern:**
```php
new WP_Query(array(
    'post_type' => 'maintenance_tasks',
    'meta_query' => array(
        array(
            'key'     => 'upkeepify_nearest_unit',
            'value'   => $unit_number,
            'compare' => '=',
        ),
    ),
));
```

**Indexes Used:**
- `idx_postmeta_nearest_unit_value` (recommended)
- `idx_postmeta_nearest_unit` (alternative)

### 3. Tasks by Assigned Provider

**Query Pattern:**
```php
new WP_Query(array(
    'post_type' => 'maintenance_tasks',
    'meta_query' => array(
        array(
            'key'     => 'assigned_service_provider',
            'value'   => $provider_id,
            'compare' => '=',
        ),
    ),
));
```

**Indexes Used:**
- `idx_postmeta_assigned_provider`

### 4. Tasks Sorted by Rough Estimate

**Query Pattern:**
```php
new WP_Query(array(
    'post_type'      => 'maintenance_tasks',
    'meta_key'       => 'upkeepify_rough_estimate',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
));
```

**Indexes Used:**
- `idx_postmeta_rough_estimate`

## Implementation Steps

### 1. Create Indexes via MySQL

Connect to your WordPress database via MySQL command line or phpMyAdmin and execute the SQL statements above.

**Example:**
```bash
mysql -u username -p database_name < indexes.sql
```

### 2. Create Indexes via WordPress Plugin

Alternatively, create a custom plugin or use a code snippet to create indexes during activation:

```php
function upkeepify_create_database_indexes() {
    global $wpdb;

    $collate = $wpdb->get_charset_collate();

    // Index for nearest unit
    $wpdb->query(
        "CREATE INDEX IF NOT EXISTS idx_postmeta_nearest_unit
        ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(20))
        WHERE meta_key = 'upkeepify_nearest_unit'"
    );

    // Index for rough estimate
    $wpdb->query(
        "CREATE INDEX IF NOT EXISTS idx_postmeta_rough_estimate
        ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
        WHERE meta_key = 'upkeepify_rough_estimate'"
    );

    // Index for assigned provider
    $wpdb->query(
        "CREATE INDEX IF NOT EXISTS idx_postmeta_assigned_provider
        ON {$wpdb->postmeta}(post_id, meta_key(50), meta_value(50))
        WHERE meta_key = 'assigned_service_provider'"
    );
}
register_activation_hook(__FILE__, 'upkeepify_create_database_indexes');
```

### 3. Verify Index Creation

Check that indexes were created successfully:

```sql
SHOW INDEX FROM wp_postmeta WHERE Key_name LIKE 'idx_postmeta_%';
```

## Performance Implications

### Benefits

1. **Faster Query Execution**: Indexed queries can be 10-100x faster
2. **Reduced Server Load**: Less CPU and memory usage for query processing
3. **Better Scalability**: Plugin performance remains stable as data grows
4. **Improved User Experience**: Faster page loads for task listings

### Considerations

1. **Storage Overhead**: Indexes consume additional disk space (typically 10-20% of table size)
2. **Write Performance**: Indexes slightly slow down INSERT/UPDATE/DELETE operations
3. **Maintenance**: Indexes need to be monitored and potentially rebuilt over time

### When to Add Indexes

- **Do add**: When queries are slow and executed frequently
- **Do add**: When table has 10,000+ rows
- **Don't add**: For queries that run once per hour or less
- **Don't add**: When table has fewer than 1,000 rows (overkill)

## Monitoring and Maintenance

### 1. Monitor Query Performance

Enable query logging in WordPress debug mode:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SAVEQUERIES', true);
```

Then add this to your theme or plugin to log slow queries:

```php
add_action('wp_footer', function() {
    if (current_user_can('administrator')) {
        global $wpdb;
        echo '<!-- Queries: ' . count($wpdb->queries) . ' -->';
        foreach ($wpdb->queries as $query) {
            if ($query[1] > 0.1) { // Log queries > 100ms
                error_log('Slow Query: ' . $query[0] . ' (' . $query[1] . 's)');
            }
        }
    }
});
```

### 2. Analyze Slow Queries

Use MySQL slow query log to identify performance bottlenecks:

```sql
-- Enable slow query log in MySQL configuration
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Log queries taking > 1 second
```

### 3. Optimize Tables Regularly

Run table optimization periodically:

```sql
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
OPTIMIZE TABLE wp_term_relationships;
```

Or via WordPress:

```php
function upkeepify_optimize_tables() {
    global $wpdb;

    $tables = array(
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->term_relationships,
    );

    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE $table");
    }
}

// Run once monthly via WP-Cron
wp_schedule_event(time(), 'monthly', 'upkeepify_optimize_tables');
add_action('upkeepify_optimize_tables', 'upkeepify_optimize_tables');
```

## Removing Indexes

If an index is no longer needed or causing performance issues:

```sql
DROP INDEX idx_postmeta_nearest_unit ON wp_postmeta;
DROP INDEX idx_postmeta_rough_estimate ON wp_postmeta;
DROP INDEX idx_postmeta_assigned_provider ON wp_postmeta;
```

## Best Practices

1. **Profile First**: Always analyze queries before adding indexes
2. **Monitor Storage**: Keep track of database size growth
3. **Test Changes**: Test indexes on staging environment first
4. **Document Changes**: Keep a record of all index additions
5. **Review Regularly**: Review index usage quarterly and remove unused ones

## Additional Resources

- [WordPress Database Optimization](https://developer.wordpress.org/apis/database/)
- [MySQL Index Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [Query Performance Analysis](https://dev.mysql.com/doc/refman/8.0/en/explain-output.html)

## Support

For database optimization issues or questions, please refer to the WordPress Codex or consult with a database administrator.

---

**Note**: Replace `wp_` with your actual table prefix if different from the default.
