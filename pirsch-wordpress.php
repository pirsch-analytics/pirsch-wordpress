<?php
/**
 * Plugin Name:       Pirsch Analytics
 * Plugin URI:        https://pirsch.io/
 * Description:       Connect your Wordpress website to Pirsch Analytics.
 * Version:           2.0.1
 * Requires at least: 5.9
 * Requires PHP:      8.1
 * Author:            Emvi Software GmbH
 * License:           MIT
 * License URI:       https://github.com/pirsch-analytics/pirsch-wordpress/blob/master/LICENSE
 */

if (!defined('WPINC')) {
	die;
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/admin.php';
require_once __DIR__.'/src/middleware.php';
require_once __DIR__.'/src/snippet.php';

register_activation_hook(__FILE__, 'pirsch_analytics_activate');
register_uninstall_hook(__FILE__, 'pirsch_analytics_uninstall');
add_action('admin_init', 'pirsch_analytics_settings_page_init');
add_action('admin_menu', 'pirsch_analytics_add_settings_page');
add_action('admin_menu', 'pirsch_analytics_remove_settings_page', 99);
add_action('init', 'pirsch_analytics_middleware', 9); // execute before WPRocket and other cache plugins
add_action('wp_head', 'pirsch_analytics_snippet');
