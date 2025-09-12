<?php
const PIRSCH_FILTER_REGEX_PREFIX = 'regex:';

function pirsch_analytics_middleware() {
	try {
		if (empty(get_option('pirsch_analytics_disabled')) &&
			!pirsch_analytics_ignore_logged_in_user() &&
			!pirsch_analytics_is_wp_site() &&
			!pirsch_analytics_is_excluded()) {
			$accessKey = get_option('pirsch_analytics_client_access_key');
			$header = get_option('pirsch_analytics_header');

			if (!empty($accessKey)) {
				$client = new Pirsch\Client('', $accessKey, Pirsch\Client::DEFAULT_TIMEOUT);
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
		error_log('Pirsch plugin error: '.$e->getMessage());
	}
}

function pirsch_analytics_ignore_logged_in_user() {
	return !empty(get_option('pirsch_analytics_ignore_logged_in')) && is_user_logged_in();
}

function pirsch_analytics_is_wp_site() {
	$pattern = "/^\/*wp-.*$/i";
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	return preg_match($pattern, $path) === 1;
}

function pirsch_analytics_is_excluded() {
	try {
		$filter = "regex:^\/favicon\.ico.*$\n".get_option('pirsch_analytics_path_filter');
		$filter = explode("\n", str_replace("\n\r", "\n", $filter));

		foreach ($filter as $f) {
			$f = trim($f);

			if (empty($f)) {
				continue;
			}

			if (str_starts_with(strtolower($f), PIRSCH_FILTER_REGEX_PREFIX)) {
				if (preg_match('/'.substr($f, strlen(PIRSCH_FILTER_REGEX_PREFIX)).'/U', $_SERVER['REQUEST_URI'])) {
					return true;
				}
			} else {
				if ($_SERVER['REQUEST_URI'] == $f || $_SERVER['REQUEST_URI'] == $f.'/') {
					return true;
				}
			}
		}
	} catch(Exception $e) {
		error_log('Pirsch plugin error: '.$e->getMessage());
		return false;
	}

	return false;
}

function pirsch_analytics_parse_x_forwarded_for($header) {
	$parts = explode(',', $header);
	
	if (count($parts)) {
		return trim($parts[0]);
	}

	return '';
}

function pirsch_analytics_parse_forwarded_header($header) {
	$parts = explode(',', $header);
	$n = count($parts);

	if ($n > 0) {
		$parts = explode(';', $parts[$n-1]);

		foreach ($parts as $part) {
			$kv = explode('=', $part);

			if (count($kv) == 2 && strtolower(trim($kv[0])) == 'by') {
				return trim($kv[1], '\n\r\t\v"');
			}
		}

		unset($part);
	}

	return '';
}
