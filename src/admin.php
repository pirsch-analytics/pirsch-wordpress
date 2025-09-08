<?php
function pirsch_analytics_activate() {}

function pirsch_analytics_uninstall() {
	delete_option('pirsch_analytics_client_access_key');
	delete_option('pirsch_analytics_header');
	delete_option('pirsch_analytics_path_filter');
	delete_option('pirsch_analytics_iframe_url');
}

function pirsch_analytics_settings_page_init() {
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_access_key');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_header');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_path_filter');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_iframe_url');

	// tracking
	add_settings_section(
		'pirsch_analytics_tracking',
		__('Tracking', 'pirsch_analytics'),
		NULL,
		'pirsch_analytics_page'
    );
	add_settings_field(
		'pirsch_analytics_client_access_key',
		__('Client Access Key', 'pirsch_analytics'),
		'pirsch_analytics_client_access_key_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_tracking',
		[
			'label_for' => 'pirsch_analytics_client_access_key'
		]
	);
	add_settings_field(
		'pirsch_analytics_header',
		__('Header', 'pirsch_analytics'),
		'pirsch_analytics_header_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_tracking',
		[
			'label_for' => 'pirsch_analytics_header'
		]
	);
	add_settings_field(
		'pirsch_analytics_path_filter',
		__('Path Filter', 'pirsch_analytics'),
		'pirsch_analytics_path_filter_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_tracking',
		[
			'label_for' => 'pirsch_analytics_path_filter'
		]
	);

	// embedded dashboard
	add_settings_section(
		'pirsch_analytics_embedded',
		__('Embedded Dashboard', 'pirsch_analytics'),
		NULL,
		'pirsch_analytics_page'
    );
	add_settings_field(
		'pirsch_analytics_iframe_url',
		__('Embed URL', 'pirsch_analytics'),
		'pirsch_analytics_iframe_url_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_embedded',
		[
			'label_for' => 'pirsch_analytics_iframe_url'
		]
	);
}

function pirsch_analytics_client_access_key_callback() {
	$value = get_option('pirsch_analytics_client_access_key', '');
	echo '<input type="password" name="pirsch_analytics_client_access_key" value="'.esc_attr($value).'" id="pirsch_analytics_client_access_key" />';
}

function pirsch_analytics_header_callback() {
	$value = get_option('pirsch_analytics_header', '');

	?>
	<select name="pirsch_analytics_header" id="pirsch_analytics_header">
		<option value="">None</option>
		<option value="x-forwarded-for" <?php if ($value == 'x-forwarded-for') echo 'selected'; ?>>X-Forwarded-For</option>
		<option value="forwarded" <?php if ($value == 'forwarded') echo 'selected'; ?>>Forwarded</option>
		<option value="cf-connecting-ip" <?php if ($value == 'cf-connecting-ip') echo 'selected'; ?>>CF-Connecting-IP</option>
		<option value="true-client-ip" <?php if ($value == 'true-client-ip') echo 'selected'; ?>>True-Client-IP</option>
		<option value="x-real-ip" <?php if ($value == 'x-real-ip') echo 'selected'; ?>>X-Real-IP</option>
	</select>
	<?php
}

function pirsch_analytics_path_filter_callback() {
	$value = get_option('pirsch_analytics_path_filter', '');
	echo '<input type="text" name="pirsch_analytics_path_filter" value="'.esc_attr($value).'" id="pirsch_analytics_path_filter" />';
}

function pirsch_analytics_iframe_url_callback() {
	$value = get_option('pirsch_analytics_iframe_url', '');
	echo '<input name="pirsch_analytics_iframe_url" value="'.esc_attr($value).'" id="pirsch_analytics_iframe_url" />';
}

function pirsch_embedded_dashboard() {
	$iframeURL = get_option('pirsch_analytics_iframe_url', '');

	if (!empty($iframeURL)) {
		?>
		<iframe src="<?php echo $iframeURL; ?>" style="width: 100%;height: 80vh;border-width: 0;"></iframe>
		<?php
	} else {
		?>
		<p>Please configure the embedding URL to see your Pirsch Analytics dashboard.</p>
		<?php
	}
}

function pirsch_analytics_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		<?php pirsch_embedded_dashboard(); ?>
        <form action="options.php" method="post">
            <?php
            settings_fields('pirsch_analytics_page');
            do_settings_sections('pirsch_analytics_page');
            submit_button(__( 'Save', 'textdomain'));
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
