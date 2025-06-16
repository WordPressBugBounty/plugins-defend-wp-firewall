<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://revmakx.com
 * @since      1.0.0
 *
 * @package    Defend_WP
 * @subpackage Defend_WP/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'safe_style_css',
	function ( $styles ) {
		$styles[] = 'display';
		return $styles;
	}
);

?>


<?php


$defend_wp_firewall_options = new Defend_WP_Firewall_Options();
$all_configs_dwp            = $defend_wp_firewall_options->get_all_configs();

$defend_wp_firewall_blocklist = new Defend_WP_Firewall_Blocklist_Functions();
$all_blocklists_dwp           = $defend_wp_firewall_blocklist->get_all_blocklists();
$dfwp_setting_options         = array();
$allowed_post_tags            = $this->allowed_post_tags();
$dfwp_setting_options         = apply_filters( 'dfwp_settings_options', $dfwp_setting_options, $all_configs_dwp );
$active_tab                   = 'settings';

do_action( 'defend_wp_firewall_before_setting_start' );

?>



<div class="wrap">
	<h1 style="display: none;">DefendWP - Settings</h1>
	<div class="defendwp-firewall-main-container">
		<?php
		require_once DEFEND_WP_FIREWALL_PLUGIN_DIR . 'admin/views/defend-wp-firewall-header.php';
		if ( ! empty( $_GET['dfwp_request_log'] ) ) {
			?>
				<h4>Request Logs</h4>
				<code class="block whitespace-pre-wrap overflow-x-scroll">
					
						<?php
						$log = $defend_wp_firewall_options->get_option( 'dfwp_request_log' );
						$log = json_decode( $log );
						echo esc_html( wp_json_encode( $log ) );
						?>
					
				</code>
			<?php
		}
		?>
	
		<div class="flex bg-white shadow rounded-lg mt-2">
			<div class="flex flex-col">
				<div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200" style="width: 300px;">
					<h2 class="text-base font-semibold leading-7 text-gray-900 px-5 py-3 bg-gray-50 rounded-tr-lg border-b">Settings</h2>
					<nav class="flex flex-col settings ">
						<ul role="list" class="flex flex-col gap-y-7">
							<li class="mb-4">
								<ul role="list" class="ml-4 space-y-1">
									<?php
									foreach ( $dfwp_setting_options as $nav_option ) {
										echo wp_kses( $nav_option['nav_html'], $allowed_post_tags );
									}
									?>
								</ul>
							</li>
						</ul>
					</nav>
				</div>
	
			</div>
			<main class="dfwp-content-wrapper lg:flex-auto relative flex flex-col">
				<?php
				foreach ( $dfwp_setting_options as $nav_option ) {
					echo wp_kses( $nav_option['main_html'], $allowed_post_tags );
				}
				?>
				<p class="save_settings_dwp_submit submit bg-gray-50"><input type="submit" name="submit" id="dwp_settings_submit" class="save_settings_dwp button button-primary" value="Save Changes"></p>
			</main>
		</div>
	</div>
</div>

<?php
do_action( 'defend_wp_firewall_setting_end' );
