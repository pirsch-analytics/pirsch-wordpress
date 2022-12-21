<?php
/**
 * Plugin Name:       Pirsch Analytics
 * Plugin URI:        https://pirsch.io/
 * Description:       Connect your Wordpress website to Pirsch Analytics.
 * Version:           1.5.4
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

function pirsch_analytics_activate() {}

function pirsch_analytics_deactivate() {
	delete_option('pirsch_analytics_base_url');
	delete_option('pirsch_analytics_hostname');
	delete_option('pirsch_analytics_client_id');
	delete_option('pirsch_analytics_client_secret');
	delete_option('pirsch_analytics_header');
	delete_option('pirsch_analytics_path_filter');
}

function pirsch_analytics_settings_page_init() {
	register_setting('pirsch_analytics_page', 'pirsch_analytics_base_url');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_hostname');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_id');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_secret');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_header');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_path_filter');
	add_settings_section(
		'pirsch_analytics_page',
		__('Settings', 'pirsch_analytics'),
		'pirsch_analytics_settings_callback',
		'pirsch_analytics_page'
    );
	add_settings_field(
		'pirsch_analytics_base_url',
		__('Base URL', 'pirsch_analytics'),
		'pirsch_analytics_base_url_callback',
		'pirsch_analytics_page',
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
	add_settings_field(
		'pirsch_analytics_header',
		__('Header', 'pirsch_analytics'),
		'pirsch_analytics_header_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_page'
	);
	add_settings_field(
		'pirsch_analytics_path_filter',
		__('Path Filter', 'pirsch_analytics'),
		'pirsch_analytics_path_filter_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_page'
	);
}

function pirsch_analytics_settings_callback() {
	echo '<p>Use the hostname you configured on the Pirsch dashboard for your website (e.g. example.com).
		To gain a client ID and secret, navigate to the Pirsch dashboard, click on settings, and create a new client.
		<strong>You can also use a single access token and skip the client ID.</strong> Read our <a href="https://docs.pirsch.io/get-started/backend-integration/" target="_blank">backend integration</a> for details.</p>';
	echo '<p>The base URL is optional and only required if you use a Pirsch proxy server.</p>';
	echo '<p>The header is optional and should only be set when WordPress is running behind a proxy or load balancer.
		Pirsch requires the real visitor IP address, so you must provide the right header.<br />
		Options are: CF-Connecting-IP, True-Client-IP, X-Forwarded-For, Forwarded, X-Real-IP.</p>';
	echo '<p>The path filter can be used to exclude pages and files. Enter a comma separated list of <a href="https://www.php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">regular expressions</a>, to filter unwanted page views.</p>';
	echo '<p><a href="https://dashboard.pirsch.io/settings" target="_blank">Go to the Pirsch settings page</a></p>';
}

function pirsch_analytics_base_url_callback() {
	$value = get_option('pirsch_analytics_base_url', '');
	echo '<input type="text" name="pirsch_analytics_base_url" value="' . esc_attr($value) . '" />';
}

function pirsch_analytics_hostname_callback() {
	$value = get_option('pirsch_analytics_hostname', '');
	echo '<input type="text" name="pirsch_analytics_hostname" value="'.esc_attr($value).'" />';
}

function pirsch_analytics_client_id_callback() {
	$value = get_option('pirsch_analytics_client_id', '');
	echo '<input type="text" name="pirsch_analytics_client_id" value="'.esc_attr($value).'" />';
}

function pirsch_analytics_header_callback() {
	$value = get_option('pirsch_analytics_header', '');
	echo '<input type="text" name="pirsch_analytics_header" value="'.esc_attr($value).'" />';
}

function pirsch_analytics_client_secret_callback() {
	$value = get_option('pirsch_analytics_client_secret', '');
	echo '<input type="password" name="pirsch_analytics_client_secret" value="'.esc_attr($value).'" />';
}

function pirsch_analytics_path_filter_callback() {
	$value = get_option('pirsch_analytics_path_filter', '');
	echo '<input type="text" name="pirsch_analytics_path_filter" value="'.esc_attr($value).'" />';
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
		if (!is_admin() && !pirsch_analytics_is_wp_site() && !pirsch_analytics_is_excluded()) {
			$baseURL = get_option('pirsch_analytics_base_url');
			$hostname = get_option('pirsch_analytics_hostname');
			$clientID = get_option('pirsch_analytics_client_id');
			$clientSecret = get_option('pirsch_analytics_client_secret');
			$header = get_option('pirsch_analytics_header');

			if (empty($baseURL)) {
				$baseURL = Pirsch\Client::DEFAULT_BASE_URL;
			}

			if (!empty($hostname) && !empty($clientSecret)) {
				$client = new Pirsch\Client($clientID, $clientSecret, $hostname, $baseURL);
				$options = new Pirsch\HitOptions();

				if (!empty($header)) {
					switch (strtolower($header)) {
						case 'cf-connecting-ip':
							$options->ip = pirsch_analytics_parse_x_forwarded_for($_SERVER['HTTP_CF_CONNECTING_IP']);
							break;
						case 'true-client-ip':
							$options->ip = pirsch_analytics_parse_x_forwarded_for($_SERVER['HTTP_TRUE_CLIENT_IP']);
							break;
						case 'x-forwarded-for':
							$options->ip = pirsch_analytics_parse_x_forwarded_for($_SERVER['HTTP_X_FORWARDED_FOR']);
							break;
						case 'forwarded':
							$options->ip = pirsch_analytics_parse_forwarded_header($_SERVER['HTTP_FORWARDED']);
							break;
						case 'x-real-ip':
							$options->ip = $_SERVER['HTTP_X_REAL_IP'];
							break;
					}
				}

				$client->pageview($options);
			}
		}
	} catch(Exception $e) {
		error_log($e->getMessage());
	}
}

function pirsch_analytics_is_wp_site() {
	$pattern = "/\/*wp-.*/i";
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	return preg_match($pattern, $path) === 1;
}

function pirsch_analytics_is_excluded() {
	$patterns = explode(',', get_option('pirsch_analytics_path_filter'));

	foreach ($patterns as $pattern) {
		if (preg_match('/'.$pattern.'/gU', $_SERVER['REQUEST_URI'])) {
			return true;
		}
	}

	return false;
}

function pirsch_analytics_parse_x_forwarded_for($header) {
	$parts = explode(',', $header);
	$n = count($parts);

	if ($n > 0) {
		return $parts[$n-1];
	}

	return '';
}

function pirsch_analytics_parse_forwarded_header($header) {
	$parts = explode(',', $header);
	$n = count($parts);

	if ($n > 0) {
		$parts = explode(';', $parts[$n-1]);

		foreach ($parts as $part) {
			$kv = explode('=', $part, 1);

			if (count($kv) == 2 && $kv[0] == 'for') {
				return $kv[1];
			}
		}

		unset($part);
	}

	return '';
}

register_activation_hook(__FILE__, 'pirsch_analytics_activate');
register_deactivation_hook(__FILE__, 'pirsch_analytics_deactivate');
add_action('admin_init', 'pirsch_analytics_settings_page_init');
add_action('admin_menu', 'pirsch_analytics_add_settings_page');
add_action('admin_menu', 'pirsch_analytics_remove_settings_page', 99);
add_action('init', 'pirsch_analytics_middleware');
