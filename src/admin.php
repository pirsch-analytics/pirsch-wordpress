<?php
function pirsch_analytics_activate() {
	register_setting('pirsch_analytics_page', 'pirsch_analytics_ignore_logged_in');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_script_disable_page_views');
	update_option('pirsch_analytics_ignore_logged_in', 'on');
	update_option('pirsch_analytics_script_disable_page_views', 'on');
}

function pirsch_analytics_uninstall() {
	delete_option('pirsch_analytics_client_access_key');
	delete_option('pirsch_analytics_header');
	delete_option('pirsch_analytics_path_filter');
	delete_option('pirsch_analytics_iframe_url');
	delete_option('pirsch_analytics_disabled');
	delete_option('pirsch_analytics_ignore_logged_in');
	delete_option('pirsch_analytics_add_script');
	delete_option('pirsch_analytics_script_disable_page_views');
	delete_option('pirsch_analytics_identification_code');
}

function pirsch_analytics_settings_page_init() {
	register_setting('pirsch_analytics_page', 'pirsch_analytics_client_access_key');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_header');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_path_filter');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_iframe_url');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_disabled');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_ignore_logged_in');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_add_script');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_script_disable_page_views');
	register_setting('pirsch_analytics_page', 'pirsch_analytics_identification_code');

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

	// server-side tracking
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
	add_settings_field(
		'pirsch_analytics_disabled',
		__('Disable Tracking', 'pirsch_analytics'),
		'pirsch_analytics_disabled_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_tracking',
		[
			'label_for' => 'pirsch_analytics_disabled'
		]
	);
	add_settings_field(
		'pirsch_analytics_ignore_logged_in',
		__('Disable Tracking For WP Users', 'pirsch_analytics'),
		'pirsch_analytics_ignore_logged_in_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_tracking',
		[
			'label_for' => 'pirsch_analytics_ignore_logged_in'
		]
	);

	// client-side tracking
		add_settings_section(
		'pirsch_analytics_snippet',
		__('JavaScript Snippet', 'pirsch_analytics'),
		NULL,
		'pirsch_analytics_page'
    );
	add_settings_field(
		'pirsch_analytics_identification_code',
		__('Identification Code (required)', 'pirsch_analytics'),
		'pirsch_analytics_identification_code_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_snippet',
		[
			'label_for' => 'pirsch_analytics_identification_code'
		]
	);
	add_settings_field(
		'pirsch_analytics_add_script',
		__('Add JS Snippet', 'pirsch_analytics'),
		'pirsch_analytics_add_script_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_snippet',
		[
			'label_for' => 'pirsch_analytics_add_script'
		]
	);
	add_settings_field(
		'pirsch_analytics_script_disable_page_views',
		__('Disable Page Views', 'pirsch_analytics'),
		'pirsch_analytics_script_disable_page_views_callback',
		'pirsch_analytics_page',
		'pirsch_analytics_snippet',
		[
			'label_for' => 'pirsch_analytics_script_disable_page_views'
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
	echo '<textarea name="pirsch_analytics_path_filter" id="pirsch_analytics_path_filter" autocomplete="off" style="min-width: 300px;min-height: 200px;">'.$value.'</textarea>
	<p style="font-style: italic;">
		One filter per line. Prefix regular expressions with "regex:" and escape slashes.<br>
		Example:<br><br>
		/filter/path<br>
		regex:^\/regex\/filter\/.*$
	</p>
	';
}

function pirsch_analytics_iframe_url_callback() {
	$value = get_option('pirsch_analytics_iframe_url', '');
	echo '<input name="pirsch_analytics_iframe_url" value="'.esc_attr($value).'" id="pirsch_analytics_iframe_url" />';
}

function pirsch_analytics_disabled_callback() {
	$value = get_option('pirsch_analytics_disabled');
	echo '<input type="checkbox" name="pirsch_analytics_disabled" id="pirsch_analytics_disabled" '.($value == 'on' ? 'checked' : '').' />';
}

function pirsch_analytics_ignore_logged_in_callback() {
	$value = get_option('pirsch_analytics_ignore_logged_in');
	echo '<input type="checkbox" name="pirsch_analytics_ignore_logged_in" id="pirsch_analytics_ignore_logged_in" '.($value == 'on' ? 'checked' : '').' />';
}

function pirsch_analytics_identification_code_callback() {
	$value = get_option('pirsch_analytics_identification_code');
	echo '<input name="pirsch_analytics_identification_code" value="'.esc_attr($value).'" id="pirsch_analytics_identification_code" />';
}

function pirsch_analytics_add_script_callback() {
	$value = get_option('pirsch_analytics_add_script');
	echo '<input type="checkbox" name="pirsch_analytics_add_script" id="pirsch_analytics_add_script" '.($value == 'on' ? 'checked' : '').' />';
}

function pirsch_analytics_script_disable_page_views_callback() {
	$value = get_option('pirsch_analytics_script_disable_page_views');
	echo '<input type="checkbox" name="pirsch_analytics_script_disable_page_views" id="pirsch_analytics_script_disable_page_views" '.($value == 'on' ? 'checked' : '').' />';
}

function pirsch_embedded_dashboard() {
	$iframeURL = get_option('pirsch_analytics_iframe_url', '');

	if (!empty($iframeURL)) {
		?>
		<iframe src="<?php echo $iframeURL; ?>" style="width: 100%;height: 80vh;border-width: 0;margin-top: 20px;"></iframe>
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

	$tabs = array(
		'dashboard' => 'Dashboard',
		'settings' => 'Settings'
	);
	$currentTab = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : array_key_first($tabs);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		<form action="options.php" method="post">
			<nav class="nav-tab-wrapper">
				<?php
				foreach ($tabs as $tab => $name) {
					$current = $tab === $currentTab ? ' nav-tab-active' : '';
					$url = add_query_arg(array('page' => 'pirsch_analytics_page', 'tab' => $tab), '');
					echo "<a class=\"nav-tab{$current}\" href=\"{$url}\">{$name}</a>";
				}
				?>
				<a href="https://docs.pirsch.io/integrations/wordpress" target="_blank" class="nav-tab" style="display: flex;align-items: center;gap: 4px;">
					Documentation
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 12px;height: 12px;">
						<path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h5a.75.75 0 0 1 0 1.5h-5Z" clip-rule="evenodd" />
						<path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 0 0 1.06.053L16.5 4.44v2.81a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-.75-.75h-4.5a.75.75 0 0 0 0 1.5h2.553l-9.056 8.194a.75.75 0 0 0-.053 1.06Z" clip-rule="evenodd" />
					</svg>
				</a>
			</nav>
            <?php
			if ($currentTab == 'settings') {
				settings_fields('pirsch_analytics_page');
				do_settings_sections('pirsch_analytics_page');
				submit_button(__( 'Save', 'textdomain'));
			} else {
				pirsch_embedded_dashboard();
			}
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
