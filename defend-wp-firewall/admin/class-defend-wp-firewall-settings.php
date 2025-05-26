<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `Defend_WP_Firewall_Settings` class in PHP defines hooks and methods for managing firewall
 * settings, including options for firewall protection, IP management, whitelisted requests, and
 * notification preferences.
 * */
class Defend_WP_Firewall_Settings {
	public $defend_wp_firewall_options;

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$this->define_hooks();
	}

	public function define_hooks() {
		add_filter( 'dfwp_settings_options', array( $this, 'dfwp_settings_options_firewall' ), 10, 2 );
		add_filter( 'dfwp_settings_options', array( $this, 'dfwp_settings_options_ips_action' ), 15, 2 );
		add_filter( 'dfwp_settings_options', array( $this, 'dfwp_settings_options_requests_whitelist' ), 16, 2 );
		add_filter( 'dfwp_settings_options', array( $this, 'dfwp_settings_notification' ), 17, 2 );
		add_filter( 'dfwp_settings_options', array( $this, 'dfwp_settings_advanced' ), 18, 2 );

		add_action( 'wp_ajax_save_settings_dwp', array( $this, 'save_settings_dwp' ) );
	}

	public function allowed_post_tags() {
		$allowed_atts                = array(
			'align'            => array(),
			'class'            => array(),
			'type'             => array(),
			'id'               => array(),
			'dir'              => array(),
			'lang'             => array(),
			'style'            => array( 'display' ),
			'xml:lang'         => array(),
			'src'              => array(),
			'alt'              => array(),
			'href'             => array(),
			'rel'              => array(),
			'rev'              => array(),
			'target'           => array(),
			'novalidate'       => array(),
			'value'            => array(),
			'name'             => array(),
			'tabindex'         => array(),
			'action'           => array(),
			'method'           => array(),
			'for'              => array(),
			'width'            => array(),
			'height'           => array(),
			'data'             => array(),
			'title'            => array(),
			'checked'          => array(),
			'this_type'        => array(),
			'this_id'          => array(),
			'data-navid'       => array(),
			'parent_prev_id'   => array(),
			'multiple'         => array(),
			'data-placeholder' => array(),
			'selected'         => array(),
		);
		$allowed_atts                = apply_filters( 'defend_wp_firewall_settings_allowed_attr', $allowed_atts );
		$allowed_post_tags           = wp_kses_allowed_html( 'post' );
		$allowed_post_tags['input']  = $allowed_atts;
		$allowed_post_tags['div']    = $allowed_atts;
		$allowed_post_tags['button'] = $allowed_atts;
		$allowed_post_tags['form']   = $allowed_atts;
		$allowed_post_tags['a']      = $allowed_atts;
		$allowed_post_tags['select'] = $allowed_atts;
		$allowed_post_tags['option'] = $allowed_atts;
		$allowed_post_tags           = apply_filters( 'defend_wp_firewall_settings_allowed_tags', $allowed_post_tags );
		return $allowed_post_tags;
	}

	public function save_settings_dwp() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification

		defend_wp_firewall_log( $_POST, '--------_POST----save_settings_dwp----' );

		if ( empty( $_POST ) || empty( $_POST['data'] ) || empty( $_POST['data']['settings'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this_settings = wp_unslash( $_POST['data']['settings'] ); // We sanitize values inside the set_option method below.
         // phpcs:enable WordPress.Security.NonceVerification.Missing

		$this->defend_wp_firewall_options->set_option( 'htaccess_protect_files', $this_settings['htaccess_protect_files'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'htaccess_directory_browsing', $this_settings['htaccess_directory_browsing'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'htaccess_uploads_php', $this_settings['htaccess_uploads_php'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'htaccess_plugins_php', $this_settings['htaccess_plugins_php'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'htaccess_themes_php', $this_settings['htaccess_themes_php'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'enable_defendwp_nonce', $this_settings['enable_defendwp_nonce'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'enable_csv_injection_request', $this_settings['enable_csv_injection_request'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'enable_dfwp_firewall', $this_settings['enable_dfwp_firewall'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'disable_xml_rpc_request', $this_settings['disable_xml_rpc_request'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'enable_sanitize_request', $this_settings['enable_sanitize_request'] ?? 'yes', true );
		$this->defend_wp_firewall_options->set_option( 'enable_auto_update', $this_settings['enable_auto_update'] ?? 'yes', true );

		global $defend_wp_firewall_all_configs;
		do_action( 'defend_wp_firewall_setttings_updated', $this_settings, $defend_wp_firewall_all_configs );

		do_action( 'defend_wp_firewall_setttings_updated_before_send_response', $this_settings, $defend_wp_firewall_all_configs );

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);
	}

	public function dfwp_settings_options_firewall( $setting_options, $all_configs_dwp ) {
		$nav_html  = '<li><a href="#" class="bg-lime-200  block rounded-md px-3 py-2 text-sm leading-6 font-semibold text-gray-700 whitespace-nowrap dfwp-nav-item hover:bg-gray-100 hover:text-gray-900" data-navid="dfwp-system-tweaks">Firewall</a><li>';
		$main_html = '';
		ob_start();
		?>
			<div class="dfwp-nav-dec" id="dfwp-system-tweaks">
				<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg">Firewall</h2>
				
				<fieldset class="border-b border-t border-gray-200">
					<div class="divide-y divide-gray-200">
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="enable_dfwp_firewall" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['enable_dfwp_firewall'] ) && $all_configs_dwp['enable_dfwp_firewall'] == 'yes' ) {
																		echo 'checked';
								}
								?>
								name="enable_dfwp_firewall" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<?php
								$firewall_version = '';
							if ( ! empty( $all_configs_dwp['dfwp_firewall_version'] ) ) {
								$firewall_version = '(v' . $all_configs_dwp['dfwp_firewall_version'] . ')';
							}
							?>
							<div class="ml-3 text-sm leading-6">
								<label for="enable_dfwp_firewall" class="font-medium text-gray-900 -mt-1 inline-block">Enable DefendWP firewall <?php echo esc_html( $firewall_version ); ?></label>
								<p id="enable_dfwp_firewall-description" class="text-gray-500">Enable firewall protection for all <span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">GET</span> and <span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">POST</span> requests.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="htaccess_protect_files" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['htaccess_protect_files'] ) && $all_configs_dwp['htaccess_protect_files'] == 'yes' ) {
																		echo 'checked';
								}
								?>
								name="htaccess_protect_files" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="htaccess_protect_files" class="font-medium text-gray-900 -mt-1 inline-block">Protect system files from direct access</label>
								<p id="htaccess_protect_files-description" class="text-gray-500">Protect wp-config.php, wp-config-sample.php, license.txt, readme.html, debug.log files from direct access.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="htaccess_directory_browsing" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['htaccess_directory_browsing'] ) && $all_configs_dwp['htaccess_directory_browsing'] == 'yes' ) {
																			echo 'checked';
								}
								?>
								name="htaccess_directory_browsing" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="htaccess_directory_browsing" class="font-medium text-gray-900 -mt-1 inline-block">Restrict directory browsing</label>
								<p id="htaccess_directory_browsing-description" class="text-gray-500">Restrict the listing all files and folders from the directories such as wp-content, wp-admin, and wp-includes.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="htaccess_uploads_php" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['htaccess_uploads_php'] ) && $all_configs_dwp['htaccess_uploads_php'] == 'yes' ) {
																		echo 'checked';
								}
								?>
								name="htaccess_uploads_php" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="htaccess_uploads_php" class="font-medium text-gray-900 -mt-1 inline-block">Restrict PHP files direct access in the "uploads" folder</label>
								<p id="htaccess_uploads_php-description" class="text-gray-500">Restrict PHP files in the "wp-content/uploads" folder from being accessed directly.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="htaccess_plugins_php" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['htaccess_plugins_php'] ) && $all_configs_dwp['htaccess_plugins_php'] == 'yes' ) {
																		echo 'checked';
								}
								?>
								name="htaccess_plugins_php" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="htaccess_plugins_php" class="font-medium text-gray-900 -mt-1 inline-block">Restrict PHP files direct access in the "plugins" folder</label>
								<p id="htaccess_plugins_php-description" class="text-gray-500">Restrict PHP files in the "wp-content/plugins" folder from being accessed directly.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="htaccess_themes_php" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['htaccess_themes_php'] ) && $all_configs_dwp['htaccess_themes_php'] == 'yes' ) {
																	echo 'checked';
								}
								?>
								name="htaccess_themes_php" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="htaccess_themes_php" class="font-medium text-gray-900 -mt-1 inline-block">Restrict PHP files direct access in the "themes" folder</label>
								<p id="htaccess_themes_php-description" class="text-gray-500">Restrict PHP files in the "wp-content/themes" folder from being accessed directly.</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="disable_xml_rpc_request" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['disable_xml_rpc_request'] ) && $all_configs_dwp['disable_xml_rpc_request'] == 'yes' ) {
																	echo 'checked';
								}
								?>
								name="disable_xml_rpc_request" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="disable_xml_rpc_request" class="font-medium text-gray-900 -mt-1 inline-block">Disable XML-RPC Request</label>
								<p id="disable_xml_rpc_request-description" class="text-gray-500">All XML-RPC authentication requests will be rejected</p>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="enable_defendwp_nonce" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['enable_defendwp_nonce'] ) && $all_configs_dwp['enable_defendwp_nonce'] == 'yes' ) {
																		echo 'checked';
								}
								?>
								name="enable_defendwp_nonce" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="enable_defendwp_nonce" class="font-medium text-gray-900 -mt-1 inline-block">Add DefendWP nonce for all AJAX requests</label>
								<p id="enable_defendwp_nonce-description" class="text-gray-500">Add DefendWP nonce for all ajax requests to prevent Cross-site Request Forgery (CSRF) attacks.</p>
								<div class="border rounded-lg border-yellow-400 bg-yellow-50 p-2 mt-4">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
												<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
											</svg>
										</div>
										<div class="ml-3">
											<p class="text-xs text-yellow-700">If you are facing any page loading or data displaying issues on your website, please clear all the caches. If you use a cache plugin or your hosting provider has a caching service, clear that as well.<br><br>If the issue persists, disable this setting and  
												<a class="font-medium text-yellow-700 underline hover:text-yellow-600" href="mailto:help@defendwp.org?subject=Facing%20issue%20with%20page%20loading%20or%20data%20displaying&amp;body=I%20was%20facing%20issues%20with%20the%20website%20and%20disabled%20the%20'Add%20DefendWP%20nonce%20for%20all%20requests'%20as%20instructed.%20What%20next%3F">contact us</a>.
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
							<div class="flex h-6 items-center">
								<input id="enable_sanitize_request" 
								<?php
								if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['enable_sanitize_request'] ) && $all_configs_dwp['enable_sanitize_request'] == 'yes' ) {
																			echo 'checked';
								}
								?>
								name="enable_sanitize_request" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
							</div>
							<div class="ml-3 text-sm leading-6">
								<label for="enable_sanitize_request" class="font-medium text-gray-900 -mt-1 inline-block">Enable Sanitize requests</label>
								<p id="enable_sanitize_request-description" class="text-gray-500">It will sanitize all <span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">GET</span> and <span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">POST</span> requests that match the firewall rules.</p>
							</div>
						</div>
					</div>
				</fieldset>
			</div>
		<?php
		$main_html         = ob_get_clean();
		$setting_options[] = array(
			'nav_html'  => $nav_html,
			'main_html' => $main_html,
		);
		return $setting_options;
	}

	public function dfwp_settings_options_ips_action( $setting_options, $all_configs_dwp ) {
		$defend_wp_blocklist = new Defend_WP_Firewall_Blocklist_Functions();

		$defend_wp_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$allowed_post_tags   = $this->allowed_post_tags();

		$nav_html  = '<li>
                <a href="#" class=" block rounded-md px-3 py-2 text-sm leading-6 font-semibold text-gray-700 whitespace-nowrap dfwp-nav-item hover:bg-gray-100 hover:text-gray-900" data-navid="dfwp-ips-action">IPs</a>
            </li>';
		$main_html = '';
		ob_start();

		?>

		<div class="dfwp-nav-dec" id="dfwp-ips-action" style="display: none;">
			<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg border-b">IPs</h2>
			<fieldset class="border-b border-gray-200 ">
				<div class="font-medium text-gray-900 mx-5 mt-5 text-sm">Blacklisted IPs</div>
				<p id="htaccess_themes_php-description" class="text-gray-500 mx-5 mb-5">Requests from the IP addresses listed below will be blocked.</p>
				<div class="relative mx-5 mb-5 -space-y-px rounded-md bg-white  shadow-sm" id="dfwp-blocklist-ip-list">
						<?php
							echo wp_kses( $defend_wp_blocklist->blocklist_ip_address_list(), $allowed_post_tags );
						?>
				</div>
			</fieldset>
			<fieldset class=" border-gray-200 ">
			<div class="font-medium text-gray-900 mx-5 mt-5 text-sm">Whitelisted IPs</div>
				<p id="htaccess_themes_php-description" class="text-gray-500 mx-5 mb-5">Requests from the IP addresses listed below will NOT be blocked.</p>
				<div class="relative mx-5 mb-5 -space-y-px rounded-md bg-white shadow-sm" id="dfwp-whitelist-ip-list">
						<?php
							echo wp_kses( $defend_wp_whitelist->whitelist_ip_address_list(), $allowed_post_tags );
						?>
				</div>
			</fieldset>
		</div>
		<?php
				$main_html         = ob_get_clean();
				$setting_options[] = array(
					'nav_html'  => $nav_html,
					'main_html' => $main_html,
				);
				return $setting_options;
	}

	public function dfwp_settings_options_requests_whitelist( $setting_options, $all_configs_dwp ) {

		$defend_wp_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$allowed_post_tags   = $this->allowed_post_tags();

		$nav_html  = '<li>
                <a href="#" class=" block rounded-md px-3 py-2 text-sm leading-6 font-semibold text-gray-700 whitespace-nowrap dfwp-nav-item hover:bg-gray-100 hover:text-gray-900" data-navid="dfwp-requests-whitelist">Whitelisted Requests</a>
            </li>';
		$main_html = '';
		ob_start();

		?>

			<div class="dfwp-nav-dec" id="dfwp-requests-whitelist" style="display: none;">
				<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg border-b">Whitelisted Requests</h2>
				<fieldset class="border-b border-gray-200 ">
				<div class="font-medium text-gray-900 mx-5 mt-5 text-sm">Whitelisted <span class="rounded-md py-1 px-2 ring-1 ring-inset bg-gray-50 ring-gray-500/10 font-mono">GET</span> Requests</div>
					<p id="htaccess_themes_php-description" class="text-gray-500 mx-5 mb-5"><span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">GET</span> requests with the variables listed below will NOT be blocked.</p>
					<div class="relative mx-5 mb-5 -space-y-px rounded-md bg-white shadow-sm" id="dfwp-whitelist-get-list">
						<?php
							echo wp_kses( $defend_wp_whitelist->whitelist_get_request_list(), $allowed_post_tags );
						?>
					</div>
				</fieldset>
				<fieldset class="">
					<div class="font-medium text-gray-900 mx-5 mt-5 text-sm">Whitelisted <span class="rounded-md py-1 px-2 ring-1 ring-inset bg-gray-50 ring-gray-500/10 font-mono">POST</span> Requests</div>
					<p id="htaccess_themes_php-description" class="text-gray-500 mx-5 mb-5"><span class="rounded-md py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10 font-mono">POST</span> requests with the variables listed below will NOT be blocked.</p>
					<div class="relative mx-5 mb-5 -space-y-px rounded-md bg-white shadow-sm" id="dfwp-whitelist-post-list">
						<?php
							echo wp_kses( $defend_wp_whitelist->whitelist_post_request_list(), $allowed_post_tags );
						?>
					</div>
				</fieldset>
			</div>
		<?php
				$main_html         = ob_get_clean();
				$setting_options[] = array(
					'nav_html'  => $nav_html,
					'main_html' => $main_html,
				);
				return $setting_options;
	}

	public function dfwp_settings_notification( $setting_options, $all_configs_dwp ) {
		$nav_html  = '<li><a href="#" class="block rounded-md px-3 py-2 text-sm leading-6 font-semibold text-gray-700 whitespace-nowrap dfwp-nav-item hover:bg-gray-100 hover:text-gray-900" data-navid="dfwp-notifcation">Notification</a><li>';
		$main_html = '';
		ob_start();
		?>
			<div class="dfwp-nav-dec" id="dfwp-notifcation" style="display: none;">
				<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg border-b">Notification preferences</h2>
				<fieldset class=" border-b border-gray-200 pb-5">
					<div class="font-medium text-gray-900 mx-5 mt-5 text-sm">Set your notification email address</div>
					<div class="mx-5 mt-5">
						<form class="mt-5 sm:flex sm:items-center">
							<div class="w-full sm:max-w-xs">
								<label for="email" class="sr-only">Email</label>
								<input type="email" name="email" id="dfwp_join_email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" value="<?php echo ! empty( $all_configs_dwp['dfwp_join_email'] ) ? esc_html( $all_configs_dwp['dfwp_join_email'] ) : ''; ?>">
							</div>
							<button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-lime-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime-600 sm:ml-3 sm:mt-0 sm:w-auto" id="dfwp_join">Save</button>
						</form>
					</div>
					<div class="text-center mt-2">
						<p class="dfwp-join-error text-sm  text-red-600">
						<p class="dfwp-join-res text-sm  text-red-600">
						</p>
					</div>
				</fieldset>
			</div>
		<?php
		$main_html         = ob_get_clean();
		$setting_options[] = array(
			'nav_html'  => $nav_html,
			'main_html' => $main_html,
		);
		return $setting_options;
	}

	public function dfwp_settings_advanced( $setting_options, $all_configs_dwp ) {
		$nav_html  = '<li><a href="#" class="block rounded-md px-3 py-2 text-sm leading-6 font-semibold text-gray-700 whitespace-nowrap dfwp-nav-item hover:bg-gray-100 hover:text-gray-900" data-navid="dfwp-advanced">Advanced</a><li>';
		$main_html = '';
		ob_start();
		?>
			<div class="dfwp-nav-dec" id="dfwp-advanced" style="display: none;">
				<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg border-b">Advanced</h2>
				<fieldset class=" border-b border-gray-200">
					<div class="divide-y divide-gray-200">
						<div class="relative flex items-start pt-3.5 pb-4  px-5">
							<div class="flex h-6 items-center">
								<div class="text-sm leading-6 flex items-center">
									<p class="text-gray-500">
										
										<?php
										if ( ! empty( $all_configs_dwp['dfwp_firewall_last_sync'] ) ) {
											echo 'Last sync ';
											$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
											echo esc_html( wp_date( $date_time_format, $all_configs_dwp['dfwp_firewall_last_sync'] ) );
										}
										?>
									</p>
									<div class="flex">
										<button class="sync_firewall_dfwp inline-flex items-center justify-center rounded-md bg-lime-600 ml-2 px-2 py-1 text-sm font-medium text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 w-auto top-px">Sync Firewall</button>
										<button class="revoke_connect_firewall_dfwp inline-flex items-center justify-center rounded-md bg-red-600 ml-2 px-2 py-1 text-sm font-medium text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 w-auto top-px">Revoke & Connect</button>
									</div>
									
								</div>
							</div>
						</div>
						<div class="relative flex items-start pb-4 pt-3.5 px-5">
								<div class="flex h-6 items-center">
									<input id="enable_auto_update" 
									<?php
									if ( ! empty( $all_configs_dwp ) && ! empty( $all_configs_dwp['enable_auto_update'] ) && $all_configs_dwp['enable_auto_update'] == 'yes' ) {
																			echo 'checked';
									}
									?>
									name="enable_auto_update" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="yes">
								</div>
								<div class="ml-3 text-sm leading-6">
									<label for="enable_auto_update" class="font-medium text-gray-900 -mt-1 inline-block">Enable Auto Update</label>
									<p id="enable_auto_update-description" class="text-gray-500">DefendWP Firewall plugin will be updated automatically.</p>
								</div>
						</div>
					</div>
				</fieldset>
			</div>
		<?php
		$main_html         = ob_get_clean();
		$setting_options[] = array(
			'nav_html'  => $nav_html,
			'main_html' => $main_html,
		);
		return $setting_options;
	}
}
