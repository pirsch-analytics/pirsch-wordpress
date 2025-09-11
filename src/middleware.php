<?php
// TODO
// - pirsch_analytics_client_access_key
// - track custom events for 404 pages
function pirsch_analytics_middleware() {
	try {
		if (!is_admin() && !pirsch_analytics_is_wp_site() && !pirsch_analytics_is_excluded()) {
			$clientID = get_option('pirsch_analytics_client_id');
			$clientSecret = get_option('pirsch_analytics_client_secret');
			$header = get_option('pirsch_analytics_header');

			if (!empty($clientSecret)) {
				$client = new Pirsch\Client($clientID, $clientSecret, Pirsch\Client::DEFAULT_TIMEOUT);
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
	$filter = trim(get_option('pirsch_analytics_path_filter'));

	if (!empty($filter)) {
		$patterns = explode(',', $filter);

		foreach ($patterns as $pattern) {
			if (preg_match('/'.$pattern.'/U', $_SERVER['REQUEST_URI'])) {
				return true;
			}
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
