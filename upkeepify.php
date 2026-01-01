<?php
/**
 * Plugin Name: Upkeepify
 * Plugin URI: https://github.com/anthonyhorne/upkeepify
 * Description: A comprehensive plugin to manage maintenance tasks within a complex. It supports task submissions with categorization, service provider management, and customizable settings.
 * Version: 1.0
 * Author: Anthony Horne
 * Text Domain: upkeepify
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * Credits:
 * - Anthony Horne: Plugin Development and Concept
 * - OpenAI's ChatGPT: Assistance with Code Examples and Logic (Sometimes Passive aggressively)
 * - WordPress Community: Various Tutorials and Code Snippets
 */

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

define('UPKEEPIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include constants first
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/constants.php';

// Include component files
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/custom-post-types.php';
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/taxonomies.php';
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/settings.php';
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/shortcodes.php';
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/utility-functions.php';
require_once UPKEEPIFY_PLUGIN_DIR . 'includes/admin-functions.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/sample-data.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/task-response-handling.php';
// Include the upload handlers file
require_once plugin_dir_path(__FILE__) . 'includes/upload-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/notification-system.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'upkeepify_activate');
register_deactivation_hook(__FILE__, 'upkeepify_deactivate');

/**
 * Load text domain for localization.
 */
function upkeepify_load_textdomain() {
    load_plugin_textdomain( 'upkeepify', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'upkeepify_load_textdomain' );

function upkeepify_activate() {
    // Activation code here

    // Include the sample data file
    //include_once plugin_dir_path( __FILE__ ) . 'includes/sample-data.php';

    // Activation code here, like loading sample data
    //upkeepify_install_sample_data();
    upkeepify_maybe_insert_sample_data(); // P0fe6
}

function upkeepify_deactivate() {
    // Deactivation code here
}

function upkeepify_add_favicon() {
    echo '<link rel="icon" type="image/png" href="' . plugins_url('favicon.png', __FILE__) . '" />';
}
add_action('wp_head', 'upkeepify_add_favicon');
add_action('admin_head', 'upkeepify_add_favicon'); // Also for admin area

function upkeepify_maybe_insert_sample_data_fallback() {
    if (!get_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED)) {
        upkeepify_insert_sample_data();
        update_option(UPKEEPIFY_OPTION_SAMPLE_DATA_INSERTED, 1);
    }
}

add_action('admin_init', 'upkeepify_maybe_insert_sample_data_fallback');
