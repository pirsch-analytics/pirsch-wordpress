<?php
/**
 * Pirsch Analytics Request Data Checker
 *
 * Shows exactly what the Pirsch WordPress plugin (v2.x, SDK v2) would send to
 * POST https://api.pirsch.io/api/v1/hit for the request that opens this page,
 * and checks whether the values look right (IP address, User-Agent, URL, ...).
 *
 * Usage:
 *   1. Upload this file to your WordPress root (next to wp-load.php).
 *      Do NOT name it "wp-*.php" – the plugin never tracks paths starting with "wp-".
 *   2. Open https://your-site.example/pirsch-check.php in a normal browser window
 *      (ideally logged out, from another network or your phone).
 *   3. Delete the file when you are done. It displays IP addresses and request headers.
 *
 * Options (query string):
 *   ?header=x-forwarded-for   simulate a different "IP header" plugin setting
 *                             (none, x-forwarded-for, forwarded, cf-connecting-ip,
 *                              true-client-ip, x-real-ip)
 *   ?format=json              machine-readable output
 *   ?ref=newsletter           test the referrer fallback (ref, referer, referrer, source, utm_source)
 *
 * Configuration options:
 *   PIRSCH_CHECK_TOKEN        set a password that needs to be passed via the query URL (?token=password) to run this script
 *   PIRSCH_CHECK_LOAD_WP      decides whether the tool loads WordPress, displaying the actual plugin configuration
 *   PIRSCH_CHECK_ACCESS_KEY   is only used when WordPress isn't loaded and sets the Pirsch API access key
 */

declare(strict_types=1);

// If set, the page is only shown with ?token=<value>. Strongly recommended on a live site.
const PIRSCH_CHECK_TOKEN = "";

// Load WordPress (read-only) to pick up the plugin settings. Set to false to run standalone.
const PIRSCH_CHECK_LOAD_WP = true;

// Only used when WordPress is not loaded: the access key for the optional test page view.
const PIRSCH_CHECK_ACCESS_KEY = "";

const PC_HEADERS = [
    "" => ["None (REMOTE_ADDR)", "REMOTE_ADDR"],
    "x-forwarded-for" => ["X-Forwarded-For", "HTTP_X_FORWARDED_FOR"],
    "forwarded" => ["Forwarded", "HTTP_FORWARDED"],
    "cf-connecting-ip" => ["CF-Connecting-IP", "HTTP_CF_CONNECTING_IP"],
    "true-client-ip" => ["True-Client-IP", "HTTP_TRUE_CLIENT_IP"],
    "x-real-ip" => ["X-Real-IP", "HTTP_X_REAL_IP"],
];

if (
    PIRSCH_CHECK_TOKEN !== "" &&
    !hash_equals(PIRSCH_CHECK_TOKEN, (string) ($_GET["token"] ?? ""))
) {
    http_response_code(403);
    exit("Forbidden");
}

header("Cache-Control: no-store, max-age=0");
header("X-Robots-Tag: noindex, nofollow");

// WP settings
$pc_wp = [
    "loaded" => false,
    "path" => null,
    "plugin_active" => null,
    "options" => [],
    "logged_in" => false,
    "admin" => false,
];

if (PIRSCH_CHECK_LOAD_WP) {
    $dir = __DIR__;

    for ($i = 0; $i < 5 && $dir; $i++) {
        if (is_file($dir . "/wp-load.php")) {
            $pc_wp["path"] = $dir . "/wp-load.php";
            break;
        }
        $parent = dirname($dir);
        $dir = $parent === $dir ? null : $parent;
    }

    if ($pc_wp["path"]) {
        require_once $pc_wp["path"];
        $pc_wp["loaded"] = function_exists("get_option");
    }
}

if ($pc_wp["loaded"]) {
    foreach (
        [
            "client_access_key",
            "header",
            "path_filter",
            "disabled",
            "ignore_logged_in",
            "custom_event_404",
            "add_script",
            "identification_code",
            "script_disable_page_views",
        ]
        as $name
    ) {
        $pc_wp["options"][$name] = get_option("pirsch_analytics_" . $name, "");
    }

    $pc_wp["logged_in"] = is_user_logged_in();
    $pc_wp["admin"] = current_user_can("manage_options");
    $pc_wp["plugin_active"] = function_exists("pirsch_analytics_middleware");
}

$configuredHeader = strtolower((string) ($pc_wp["options"]["header"] ?? ""));
$simulated = isset($_GET["header"]) && array_key_exists(strtolower((string) $_GET["header"]), PC_HEADERS)
    ? strtolower((string) $_GET["header"])
    : null;

if (isset($_GET["header"]) && strtolower((string) $_GET["header"]) === "none") {
    $simulated = "";
}

$activeHeader = $simulated ?? $configuredHeader;

// Client middleware replica
function pc_server(string $key): string {
    return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : "";
}

// pirsch_analytics_parse_x_forwarded_for(): first entry of the list
function pc_parse_xff(string $header): string {
    $parts = explode(",", $header);
    return count($parts) ? trim($parts[0]) : "";
}

// pirsch_analytics_parse_forwarded_header(): the "by" value of the LAST element
function pc_parse_forwarded(string $header, string $param = "by"): string {
    $parts = explode(",", $header);
    $n = count($parts);

    if ($n > 0) {
        foreach (explode(";", $parts[$n - 1]) as $part) {
            $kv = explode("=", $part);

            if (count($kv) == 2 && strtolower(trim($kv[0])) == $param) {
                return trim($kv[1], '\n\r\t\v"'); // single quotes, as in the plugin
            }
        }
    }

    return "";
}

// For comparison only: the "for" value of the FIRST element (the original client per RFC 7239)
function pc_parse_forwarded_client(string $header): string {
    foreach (explode(";", explode(",", $header)[0]) as $part) {
        $kv = explode("=", $part, 2);

        if (count($kv) == 2 && strtolower(trim($kv[0])) == "for") {
            $v = trim($kv[1], " \"\t");

            if (preg_match("/^\[([^\]]+)\]/", $v, $m)) {
                return $m[1];
            }

            return preg_replace('/:\d+$/', "", $v);
        }
    }

    return "";
}

// The IP the middleware puts into HitOptions for a given header setting
function pc_ip_from_header(string $header): string {
    switch ($header) {
        case "cf-connecting-ip":
            return pc_parse_xff(pc_server("HTTP_CF_CONNECTING_IP"));
        case "true-client-ip":
            return pc_parse_xff(pc_server("HTTP_TRUE_CLIENT_IP"));
        case "x-forwarded-for":
            return pc_parse_xff(pc_server("HTTP_X_FORWARDED_FOR"));
        case "forwarded":
            return pc_parse_forwarded(pc_server("HTTP_FORWARDED"));
        case "x-real-ip":
            return pc_server("HTTP_X_REAL_IP");
    }

    return "";
}

// Client::isEmpty()
function pc_is_empty($str): bool {
    return is_null($str) || empty(trim((string) $str, ' \t\n'));
}

// Client::getReferrer()
function pc_referrer(): string {
    $referrer = pc_server("HTTP_REFERER");

    if (empty($referrer)) {
        foreach (["ref", "referer", "referrer", "source", "utm_source"] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] != "") {
                return (string) $_GET[$key];
            }
        }
    }

    return $referrer;
}

// The body Client::pageview() posts, for the given header setting
function pc_build_payload(string $header): array {
    $ip = pc_ip_from_header($header);
    return [
        "url" =>
            "http" .
            (isset($_SERVER["HTTPS"]) ? "s" : "") .
            "://" .
            pc_server("HTTP_HOST") .
            pc_server("REQUEST_URI"),
        "ip" => pc_is_empty($ip) ? pc_server("REMOTE_ADDR") : $ip,
        "user_agent" => pc_server("HTTP_USER_AGENT"),
        "accept_language" => pc_server("HTTP_ACCEPT_LANGUAGE"),
        "sec_ch_ua" => pc_server("HTTP_SEC_CH_UA"),
        "sec_ch_ua_mobile" => pc_server("HTTP_SEC_CH_UA_MOBILE"),
        "sec_ch_ua_platform" => pc_server("HTTP_SEC_CH_UA_PLATFORM"),
        "sec_ch_ua_platform_version" => pc_server(
            "HTTP_SEC_CH_UA_PLATFORM_VERSION",
        ),
        "sec_ch_width" => pc_server("HTTP_SEC_CH_WIDTH"),
        "sec_ch_viewport_width" => pc_server("HTTP_SEC_CH_VIEWPORT_WIDTH"),
        "title" => "",
        "referrer" => pc_referrer(),
        "screen_width" => 0,
        "screen_height" => 0,
        "tags" => null,
    ];
}

function pc_ip_kind(string $ip): string {
    if ($ip === "") {
        return "empty";
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return "invalid";
    }

    if (
        !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        )
    ) {
        return "private";
    }

    return "public";
}

$payload = pc_build_payload($activeHeader);

// Checks
$checks = [];
$add = function (
    string $field,
    string $status,
    string $title,
    string $detail = "",
) use (&$checks) {
    $checks[] = compact("field", "status", "title", "detail");
};

// IP address
$ipKind = pc_ip_kind($payload["ip"]);
$headerLabel = PC_HEADERS[$activeHeader][0];
$headerVar = PC_HEADERS[$activeHeader][1];
$remote = pc_server("REMOTE_ADDR");

if ($activeHeader !== "" && pc_is_empty(pc_ip_from_header($activeHeader))) {
    $add(
        "ip",
        "warn",
        "The {$headerLabel} header is not set on this request",
        "The plugin falls back to REMOTE_ADDR. Either your proxy does not send this header or a different one should be selected.",
    );
}

if ($ipKind === "empty") {
    $add(
        "ip",
        "fail",
        "No IP address is sent",
        "Pirsch cannot tell visitors apart without an IP address.",
    );
} elseif ($ipKind === "invalid") {
    $add(
        "ip",
        "fail",
        "The IP address is not valid",
        "\"{$payload["ip"]}\" is not an IPv4 or IPv6 address. The header value may include a port or other text.",
    );
} elseif ($ipKind === "private") {
    $add(
        "ip",
        "fail",
        "The IP address is a private or reserved address",
        "This is almost always the address of a proxy, load balancer, or container network – not the visitor. All visitors would be counted as one. Pick the header that contains your real IP in the table below.",
    );
} else {
    $add(
        "ip",
        "ok",
        "The IP address is a public address",
        'Compare it with your own public IP (search "what is my IP"). If they differ, a proxy is in between and another header setting is needed.',
    );
}

if ($activeHeader === "forwarded" && pc_server("HTTP_FORWARDED") !== "") {
    $client = pc_parse_forwarded_client(pc_server("HTTP_FORWARDED"));
    if ($client !== "" && $client !== $payload["ip"]) {
        $add(
            "ip",
            "warn",
            "The Forwarded header setting reads the proxy address, not the client",
            "The plugin uses the by= value of the last element ({$payload["ip"]}). The client address in for= is {$client}. If that is your IP, X-Forwarded-For or X-Real-IP may be the better choice.",
        );
    }
}

if ($activeHeader === "") {
    foreach (PC_HEADERS as $key => [$label, $var]) {
        if ($key === "") {
            continue;
        }

        $candidate = pc_ip_from_header($key);

        if (
            $candidate !== "" &&
            $candidate !== $remote &&
            pc_ip_kind($candidate) === "public"
        ) {
            $add(
                "ip",
                "warn",
                "A proxy header is present: {$label}",
                "It contains {$candidate}, but no IP header is selected in the plugin settings, so REMOTE_ADDR ({$remote}) is sent. If {$candidate} is your IP, select {$label}.",
            );
            break;
        }
    }
} elseif (
    $ipKind === "public" &&
    pc_ip_kind($remote) === "public" &&
    pc_ip_from_header($activeHeader) !== ""
) {
    $add(
        "ip",
        "info",
        "Only use an IP header when a proxy actually sets it",
        "Visitors can send {$headerLabel} themselves. If your site is not behind a proxy or CDN that overwrites it, visitors can fake their IP.",
    );
}

// User-Agent
$ua = $payload["user_agent"];
if ($ua === "") {
    $add(
        "user_agent",
        "fail",
        "No User-Agent is sent",
        "Pirsch will most likely reject the request as a bot.",
    );
} elseif (
    preg_match(
        "/bot|crawl|spider|slurp|curl|wget|python|java\/|go-http|headless|phantom|lighthouse|monitor/i",
        $ua,
    )
) {
    $add(
        "user_agent",
        "warn",
        "The User-Agent looks like a bot or tool",
        "Page views from this client will be filtered. Open the page in a regular browser to test.",
    );
} elseif (strlen($ua) < 20) {
    $add(
        "user_agent",
        "warn",
        "The User-Agent is unusually short",
        "A proxy or security plugin may be replacing it.",
    );
} else {
    $add(
        "user_agent",
        "ok",
        "The User-Agent looks like a browser",
        "It should match what your browser reports (navigator.userAgent).",
    );
}

// Accept-Language
if ($payload["accept_language"] === "") {
    $add(
        "accept_language",
        "warn",
        "No Accept-Language header",
        "Language statistics will be empty for this visitor.",
    );
} else {
    $add("accept_language", "ok", "Accept-Language is present");
}

// URL
$fwdProto = strtolower(pc_server("HTTP_X_FORWARDED_PROTO"));
$fwdHost = pc_server("HTTP_X_FORWARDED_HOST");

if (
    isset($_SERVER["HTTPS"]) &&
    strtolower((string) $_SERVER["HTTPS"]) === "off"
) {
    $add(
        "url",
        "warn",
        'The URL is reported as https although HTTPS is "off"',
        'The SDK only checks whether $_SERVER[\'HTTPS\'] is set, not its value.',
    );
} elseif (!isset($_SERVER["HTTPS"]) && $fwdProto === "https") {
    $add(
        "url",
        "warn",
        "The URL is reported as http:// behind an HTTPS proxy",
        'The proxy terminates TLS but PHP does not know about it. Set $_SERVER[\'HTTPS\'] = \'on\' in wp-config.php when X-Forwarded-Proto is https.',
    );
} else {
    $add("url", "ok", "The URL scheme looks right");
}

if ($fwdHost !== "" && strcasecmp($fwdHost, pc_server("HTTP_HOST")) !== 0) {
    $add(
        "url",
        "warn",
        "The host differs from the public host name",
        "HTTP_HOST is \"" .
            pc_server("HTTP_HOST") .
            "\", but the proxy reports \"{$fwdHost}\". Page views would be recorded under the wrong host.",
    );
}

// Client hints
if ($payload["sec_ch_ua"] === "") {
    $add(
        "sec_ch_ua",
        "info",
        "No client hints (Sec-CH-UA)",
        "Normal for Firefox and Safari, and for plain HTTP. Chromium browsers send them over HTTPS only.",
    );
} else {
    $add("sec_ch_ua", "ok", "Client hints are present");
}

// Referrer
$add(
    "referrer",
    "info",
    $payload["referrer"] === "" ? "No referrer" : "Referrer is set",
    "Open this page from a link on another site, or add ?ref=test, to check the referrer.",
);

// Plugin state
if (!$pc_wp["loaded"]) {
    $add(
        "plugin",
        "info",
        "WordPress was not loaded",
        "Settings could not be read. Use ?header=… to simulate the IP header setting.",
    );
} else {
    $o = $pc_wp["options"];

    if ($pc_wp["plugin_active"] === false) {
        $add("plugin", "fail", "The Pirsch plugin is not active");
    }

    if (empty($o["client_access_key"])) {
        $add(
            "plugin",
            "fail",
            "No access key is configured",
            "Without it, the plugin does not send server-side page views.",
        );
    } elseif (!str_starts_with((string) $o["client_access_key"], "pa_")) {
        $add(
            "plugin",
            "warn",
            "The access key has an unexpected format",
            'Pirsch access keys usually start with "pa_". Make sure you did not paste a client ID or secret.',
        );
    }

    if (!empty($o["disabled"])) {
        $add(
            "plugin",
            "fail",
            "Server-side tracking is disabled in the plugin settings",
        );
    }

    if (!empty($o["ignore_logged_in"]) && $pc_wp["logged_in"]) {
        $add(
            "plugin",
            "warn",
            "You are logged in, so your own visits are not tracked",
            'This is expected with "Ignore logged-in users". Log out or use a private window to test.',
        );
    }

    if (
        !empty($o["add_script"]) &&
        !empty($o["identification_code"]) &&
        empty($o["script_disable_page_views"]) &&
        empty($o["disabled"])
    ) {
        $add(
            "plugin",
            "warn",
            "Page views may be counted twice",
            "Both server-side tracking and the JavaScript snippet (with page views enabled) are on. Disable page views in the snippet or turn off one of them.",
        );
    }

    if ($simulated !== null && $simulated !== $configuredHeader) {
        $add(
            "plugin",
            "info",
            "Simulating a different IP header",
            "Showing results for " .
                PC_HEADERS[$simulated][0] .
                ". The plugin is set to " .
                PC_HEADERS[$configuredHeader][0] .
                ".",
        );
    }
}

// Optional real page view
$accessKey = $pc_wp["loaded"]
    ? (string) ($pc_wp["options"]["client_access_key"] ?? "")
    : PIRSCH_CHECK_ACCESS_KEY;
$canSend =
    $accessKey !== "" &&
    function_exists("curl_init") &&
    ($pc_wp["loaded"] ? $pc_wp["admin"] : PIRSCH_CHECK_TOKEN !== "");
$sendResult = null;

if (
    $canSend &&
    ($_SERVER["REQUEST_METHOD"] ?? "") === "POST" &&
    isset($_POST["pc_send"])
) {
    $ch = curl_init("https://api.pirsch.io/api/v1/hit");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $accessKey,
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $body = curl_exec($ch);
    $sendResult = [
        "status" => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
        "body" => $body === false ? curl_error($ch) : (string) $body,
    ];
    curl_close($ch);
}

// Print check results
$ipTable = [];

foreach (PC_HEADERS as $key => [$label, $var]) {
    $result = $key === "" ? $remote : pc_ip_from_header($key);
    $ipTable[] = [
        "setting" => $key === "" ? "none" : $key,
        "label" => $label,
        "raw" => pc_server($var),
        "result" => $result,
        "kind" => pc_ip_kind($result),
        "configured" => $key === $configuredHeader,
        "active" => $key === $activeHeader,
    ];
}

$rawHeaders = [];

foreach ($_SERVER as $k => $v) {
    if (
        is_string($v) &&
        (str_starts_with($k, "HTTP_") ||
            in_array(
                $k,
                [
                    "REMOTE_ADDR",
                    "REMOTE_PORT",
                    "HTTPS",
                    "REQUEST_SCHEME",
                    "SERVER_NAME",
                    "SERVER_PORT",
                    "REQUEST_URI",
                    "SERVER_SOFTWARE",
                ],
                true,
            ))
    ) {
        if ($k === "HTTP_COOKIE" || $k === "HTTP_AUTHORIZATION") {
            $v = "(hidden)";
        }

        $rawHeaders[$k] = $v;
    }
}

ksort($rawHeaders);
$counts = array_count_values(array_column($checks, "status"));
$fails = $counts["fail"] ?? 0;
$warns = $counts["warn"] ?? 0;

if (($_GET["format"] ?? "") === "json") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(
        [
            "endpoint" => "POST https://api.pirsch.io/api/v1/hit",
            "ip_header" => $activeHeader === "" ? "none" : $activeHeader,
            "payload" => $payload,
            "checks" => $checks,
            "ip_sources" => $ipTable,
            "wordpress_loaded" => $pc_wp["loaded"],
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
    exit();
}

function e($v): string {
    return htmlspecialchars(
        is_scalar($v) || $v === null ? (string) $v : json_encode($v),
        ENT_QUOTES,
        "UTF-8",
    );
}

$byField = [];

foreach ($checks as $c) {
    $byField[$c["field"]][] = $c;
}

$statusWord = [
    "ok" => "Looks right",
    "warn" => "Check this",
    "fail" => "Wrong",
    "info" => "Note",
];

if ($fails) {
    $verdict =
        $fails === 1
            ? "One value would be sent wrong."
            : "{$fails} values would be sent wrong.";
} elseif ($warns) {
    $verdict =
        "The data looks usable, with " .
        ($warns === 1 ? "one thing" : "{$warns} things") .
        " to check.";
} else {
    $verdict = "The data sent to Pirsch looks right.";
}

$tone = $fails ? "fail" : ($warns ? "warn" : "ok");
$selfUrl = strtok(pc_server("REQUEST_URI"), "?");
$tokenQs =
    PIRSCH_CHECK_TOKEN !== ""
        ? "&token=" . rawurlencode(PIRSCH_CHECK_TOKEN)
        : "";
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pirsch request check</title>
    <style>
    :root {
    	--bg: #f3f5f7; --panel: #fff; --ink: #1b2730; --muted: #5a6a75; --line: #d9e0e5;
    	--ok: #1d7549; --ok-bg: #e5f3eb; --warn: #9a5b00; --warn-bg: #fbf0dc;
    	--fail: #b0261d; --fail-bg: #fbe6e4; --info: #2d5a86; --info-bg: #e6eef6;
    	--mono: ui-monospace, "SF Mono", "Cascadia Mono", Menlo, Consolas, monospace;
    }
    @media (prefers-color-scheme: dark) {
    	:root {
    		--bg: #12181d; --panel: #1a2229; --ink: #e3e9ed; --muted: #93a3ae; --line: #2c3842;
    		--ok: #6fcf9a; --ok-bg: #173226; --warn: #f0b85a; --warn-bg: #362a14;
    		--fail: #ff8a80; --fail-bg: #3a1c1a; --info: #8bb8e8; --info-bg: #1b2b3b;
    	}
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--ink); font: 16px/1.55 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    main { max-width: 880px; margin: 0 auto; padding: 40px 20px 80px; }
    h1 { font-size: 15px; font-weight: 600; color: var(--muted); margin: 0 0 8px; }
    .verdict { font-size: clamp(28px, 5vw, 40px); line-height: 1.15; font-weight: 700; letter-spacing: -0.02em; margin: 0 0 12px; padding-left: 18px; border-left: 6px solid var(--c); }
    .verdict.ok { --c: var(--ok); } .verdict.warn { --c: var(--warn); } .verdict.fail { --c: var(--fail); }
    .lede { color: var(--muted); max-width: 64ch; margin: 0 0 36px; }
    h2 { font-size: 20px; margin: 44px 0 6px; }
    h2 + p { color: var(--muted); margin: 0 0 16px; max-width: 68ch; }
    code, .v { font-family: var(--mono); font-size: 14px; }
    .fields { background: var(--panel); border: 1px solid var(--line); border-radius: 10px; }
    .field { display: grid; grid-template-columns: 210px 1fr; gap: 4px 20px; padding: 14px 18px; border-top: 1px solid var(--line); }
    .field:first-child { border-top: 0; }
    .field .k { font-family: var(--mono); font-size: 13px; color: var(--muted); padding-top: 2px; }
    .field .v { word-break: break-all; }
    .field .v.empty { color: var(--muted); font-style: italic; font-family: inherit; }
    .note { grid-column: 2; display: flex; gap: 10px; align-items: baseline; margin-top: 6px; font-size: 14px; }
    .note p { margin: 2px 0 0; color: var(--muted); }
    .tag { flex: none; font-size: 12px; font-weight: 600; padding: 1px 8px; border-radius: 99px; color: var(--c); background: var(--cb); }
    .tag.ok { --c: var(--ok); --cb: var(--ok-bg); } .tag.warn { --c: var(--warn); --cb: var(--warn-bg); }
    .tag.fail { --c: var(--fail); --cb: var(--fail-bg); } .tag.info { --c: var(--info); --cb: var(--info-bg); }
    .plugin { list-style: none; padding: 0; margin: 0; }
    .plugin li { display: flex; gap: 10px; align-items: baseline; padding: 10px 0; border-top: 1px solid var(--line); }
    .plugin li p { margin: 2px 0 0; color: var(--muted); font-size: 14px; }
    .scroll { overflow-x: auto; background: var(--panel); border: 1px solid var(--line); border-radius: 10px; }
    table { border-collapse: collapse; width: 100%; font-size: 14px; }
    th, td { text-align: left; padding: 10px 14px; border-top: 1px solid var(--line); vertical-align: top; }
    thead th { border-top: 0; color: var(--muted); font-weight: 600; }
    tr.active td { background: var(--info-bg); }
    td.v { word-break: break-all; }
    a { color: var(--info); }
    a:focus-visible, button:focus-visible, summary:focus-visible { outline: 3px solid var(--info); outline-offset: 2px; }
    pre { margin: 0; padding: 16px 18px; font: 13px/1.5 var(--mono); overflow-x: auto; }
    details { margin-top: 12px; }
    summary { cursor: pointer; font-weight: 600; }
    button { font: inherit; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: 1px solid var(--ink); background: var(--ink); color: var(--bg); cursor: pointer; }
    .result { margin-top: 12px; padding: 12px 16px; border-radius: 8px; background: var(--cb); color: var(--c); }
    .result.ok { --c: var(--ok); --cb: var(--ok-bg); } .result.fail { --c: var(--fail); --cb: var(--fail-bg); }
    footer { margin-top: 56px; color: var(--muted); font-size: 14px; }
    @media (max-width: 640px) {
    	.field { grid-template-columns: 1fr; }
    	.note { grid-column: 1; }
    }
    </style>
</head>
<body>
    <main>
        <h1>Pirsch Analytics request check</h1>
        <p class="verdict <?= $tone ?>"><?= e($verdict) ?></p>
        <p class="lede">This is the page view the WordPress plugin would send to <code>POST api.pirsch.io/api/v1/hit</code> for your visit to this page, using the IP header setting <strong><?= e(
            $headerLabel,
        ) ?></strong><?= $simulated !== null
            ? " (simulated)"
            : "" ?>. Nothing has been sent.</p>

        <h2>Data sent to the API</h2>
        <p>Each field as the plugin fills it in. Title and screen size are always empty for server-side tracking.</p>
        <div class="fields">
        <?php foreach ($payload as $k => $v): ?>
       	<div class="field">
      		<div class="k"><?= e($k) ?></div>
      		<?php $isEmpty = $v === "" || $v === null || $v === 0; ?>
      		<div class="v<?= $isEmpty ? " empty" : "" ?>"><?= $isEmpty
            ? ($v === 0
                ? "0"
                : "empty")
            : e($v) ?></div>
      		<?php foreach ($byField[$k] ?? [] as $c): ?>
      		<div class="note"><span class="tag <?= $c["status"] ?>"><?= $statusWord[
            $c["status"]
        ] ?></span><div><?=
        e($c["title"]);
        if ($c["detail"]): ?><p><?= e($c["detail"]) ?></p><?php endif;
        ?></div></div>
      		<?php endforeach; ?>
       	</div>
        <?php endforeach; ?>
        </div>

        <?php if (!empty($byField["plugin"])): ?>
        <h2>Plugin settings</h2>
        <p><?= $pc_wp["loaded"]
            ? "Read from WordPress."
            : "WordPress could not be loaded from this location." ?></p>
        <ul class="plugin">
       	<?php foreach ($byField["plugin"] as $c): ?>
       	<li><span class="tag <?= $c["status"] ?>"><?= $statusWord[
            $c["status"]
        ] ?></span><div><?=
        e($c["title"]);
        if ($c["detail"]): ?><p><?= e($c["detail"]) ?></p><?php endif;
        ?></div></li>
       	<?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h2>Which IP header is right for you?</h2>
        <p>The IP each plugin setting would send. The right one shows your own public IP address. Select a row to see all checks with that setting.</p>
        <div class="scroll">
        <table>
       	<thead><tr><th>Setting</th><th>Header value</th><th>IP sent</th><th>Type</th></tr></thead>
       	<tbody>
       	<?php foreach ($ipTable as $r): ?>
       	<tr class="<?= $r["active"] ? "active" : "" ?>">
      		<td><a href="?header=<?= e($r["setting"]) . e($tokenQs) ?>"><?= e(
            $r["label"],
        ) ?></a><?= $r["configured"] && $pc_wp["loaded"]
            ? "<br><small>current setting</small>"
            : "" ?></td>
      		<td class="v"><?= $r["raw"] === ""
            ? '<span class="empty">not set</span>'
            : e($r["raw"]) ?></td>
      		<td class="v"><?= $r["result"] === "" ? "–" : e($r["result"]) ?></td>
      		<td><?php if (
            $r["result"] !== "" ||
            $r["raw"] !== ""
        ): ?><span class="tag <?= $r["kind"] === "public" ? "ok" : "fail" ?>"><?= e(
            $r["kind"] === "empty" ? "not found" : $r["kind"],
        ) ?></span><?php endif; ?></td>
       	</tr>
       	<?php endforeach; ?>
       	</tbody>
        </table>
        </div>

        <h2>Send a test page view</h2>
        <?php if ($canSend): ?>
        <p>Posts the data above to Pirsch with your access key. It will show up in your dashboard as a visit to this page.</p>
        <form method="post" action="<?= e(
            $selfUrl .
                "?" .
                ltrim(
                    ($simulated !== null
                        ? "header=" .
                            rawurlencode($simulated === "" ? "none" : $simulated)
                        : "") . $tokenQs,
                    "&",
                ),
        ) ?>">
       	<button type="submit" name="pc_send" value="1">Send test page view</button>
        </form>
        <?php if ($sendResult): ?>
       	<div class="result <?= $sendResult["status"] >= 200 &&
        $sendResult["status"] < 300
            ? "ok"
            : "fail" ?>">
      		HTTP <?=
        $sendResult["status"] ?: "error";
        $sendResult["body"] !== ""
            ? ": <code>" . e(mb_substr($sendResult["body"], 0, 500)) . "</code>"
            : ""
        ?>
      		<?= $sendResult["status"] === 401 ? "<br>The access key was rejected." : "" ?>
       	</div>
        <?php endif; ?>
        <?php else: ?>
        <p>Not available. <?=
        $pc_wp["loaded"]
            ? "Log in as an administrator and make sure an access key is set."
            : "Set PIRSCH_CHECK_TOKEN and PIRSCH_CHECK_ACCESS_KEY at the top of this file.";
        function_exists("curl_init") ? "" : " The PHP cURL extension is required."
        ?></p>
        <?php endif; ?>

        <h2>Raw request</h2>
        <p>What PHP received. Cookies and authorization headers are hidden.</p>
        <details>
       	<summary>Request headers and server variables</summary>
       	<div class="scroll" style="margin-top:10px">
       	<table><tbody>
       	<?php foreach ($rawHeaders as $k => $v): ?>
      		<tr><td><code><?= e($k) ?></code></td><td class="v"><?= e($v) ?></td></tr>
       	<?php endforeach; ?>
       	</tbody></table>
       	</div>
        </details>
        <details>
       	<summary>JSON body</summary>
       	<div class="scroll" style="margin-top:10px"><pre><?= e(
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        ) ?></pre></div>
        </details>

        <footer>
       	Delete this file when you are done – it shows IP addresses and request headers to anyone who opens it.
       	<a href="?format=json<?= e($tokenQs) ?>">View as JSON</a>
        </footer>
    </main>
</body>
</html>
