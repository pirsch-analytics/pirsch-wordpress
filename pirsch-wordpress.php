<?php
/**
 * Plugin Name:       Pirsch Analytics
 * Plugin URI:        https://pirsch.io/
 * Description:       Connect your Wordpress website to Pirsch Analytics.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Emvi Software GmbH
 * License:           MIT
 * License URI:       https://github.com/pirsch-analytics/pirsch-wordpress/blob/master/LICENSE
 */

if(!defined('WPINC')) {
	die;
}

require __DIR__.'/vendor/autoload.php';

function pirsch_analytics_activate() {

}

function pirsch_analytics_deactivate() {
	delete_option('pirsch_analytics_hostname');
	delete_option('pirsch_analytics_client_id');
	delete_option('pirsch_analytics_client_secret');
}

function pirsch_analytics_settings_page_init() {
	register_setting('pirsch_analytics_page', 'pirsch_analytics_hostname');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_id');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_secret');
	add_settings_section(
		'pirsch_analytics_page',
		__('Settings', 'pirsch_analytics'),
		'pirsch_analytics_settings_callback',
		'pirsch_analytics_page'
    );
	add_settings_field(
		'pirsch_analytics_hostname',
		__('Hostname', 'pirsch_analytics'),
		'pirsch_analytics_hostname_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_page'
	);
	add_settings_field(
		'pirsch_analytics_client_id',
		__('Client ID', 'pirsch_analytics'),
		'pirsch_analytics_client_id_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_page'
	);
	add_settings_field(
		'pirsch_analytics_client_secret',
		__('Client Secret', 'pirsch_analytics'),
		'pirsch_analytics_client_secret_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_page'
	);
}

function pirsch_analytics_settings_callback() {
	echo '<p>Use the hostname you configured on the Pirsch dashboard for your website (e.g. example.com). To gain a client ID and secret, navigate to the Pirsch dashboard, click on settings, and create a new client. Read our <a href="https://docs.pirsch.io/get-started/backend-integration/" target="_blank">backend integration</a> for details.</p>';
}

function pirsch_analytics_hostname_callback() {
	$hostname = get_option('pirsch_analytics_hostname', '');
	echo '<input type="text" name="pirsch_analytics_hostname" value="'.esc_attr($hostname).'" />';
}

function pirsch_analytics_client_id_callback() {
	$hostname = get_option('pirsch_analytics_client_id', '');
	echo '<input type="text" name="pirsch_analytics_client_id" value="'.esc_attr($hostname).'" />';
}

function pirsch_analytics_client_secret_callback() {
	$hostname = get_option('pirsch_analytics_client_secret', '');
	echo '<input type="password" name="pirsch_analytics_client_secret" value="'.esc_attr($hostname).'" />';
}

function pirsch_analytics_settings_page_html() {
    if(!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('pirsch_analytics_page');
            do_settings_sections('pirsch_analytics_page');
            submit_button(__( 'Save Settings', 'textdomain'));
            ?>
        </form>
    </div>
    <?php
}

function pirsch_analytics_add_settings_page() {
    add_submenu_page(
        'tools.php',
        'Pirsch Analytics',
        'Pirsch Analytics',
        'manage_options',
        'pirsch_analytics_page',
        'pirsch_analytics_settings_page_html'
    );
}

function pirsch_analytics_remove_settings_page() {
	remove_menu_page('pirsch_analytics_page');
}

function pirsch_analytics_middleware() {
	try {
		if(!is_admin()) {
			$hostname = get_option('pirsch_analytics_hostname');
			$clientID = get_option('pirsch_analytics_client_id');
			$clientSecret = get_option('pirsch_analytics_client_secret');

			if(!empty($hostname) && !empty($clientID) && !empty($clientSecret)) {
				$client = new Pirsch\Client($clientID, $clientSecret, $hostname);
				$client->hit();
			}
		}
	} catch(Exception $e) {
		error_log($e->getMessage());
	}
}

register_activation_hook(__FILE__, 'pirsch_analytics_activate');
register_deactivation_hook(__FILE__, 'pirsch_analytics_deactivate');
add_action('admin_init', 'pirsch_analytics_settings_page_init');
add_action('admin_menu', 'pirsch_analytics_add_settings_page');
add_action('admin_menu', 'pirsch_analytics_remove_settings_page', 99);
add_action('init', 'pirsch_analytics_middleware');
