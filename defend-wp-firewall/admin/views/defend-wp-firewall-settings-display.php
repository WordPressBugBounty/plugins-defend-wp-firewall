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


do_action( 'defend_wp_firewall_before_setting_start' );

?>



<div class="wrap">
	<h1>DefendWP - Settings</h1>
	<?php
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

	<?php
	if ( empty( $all_configs_dwp['first_time_settings_page_visit_done'] ) ) {
		$defend_wp_firewall_options->set_option( 'first_time_settings_page_visit_done', true );
		?>
		<div class="dfwp_first_flap rounded-md bg-blue-50 p-4 mt-3 mb-6 border border-blue-700" style="max-width: 760px;">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg class="h-5 w-5 text-blue-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
						<path fill-rule="evenodd"
							d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
							clip-rule="evenodd"></path>
					</svg>
				</div>
				<div class="ml-3 flex-1 md:flex md:justify-between">
					<p class="text-sm text-blue-700">Welcome to DefendWP. This is the Settings page where we have pre-configured
						the rules for your website's best defence. Feel free customize the rules to your needs.<br><br>If you
						need any assistance with this, please reach out at <a class="underline" href="mailto:help@defendwp.org"
							target="_blank">help@defendwp.org</a>.</p>
					<div class="ml-auto pl-3">
						<div class="-mx-1.5 -my-1.5">
							<button type="button"
								class="dfwp_first_flap_close inline-flex rounded-md bg-blue-50 p-1.5 text-blue-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50">
								<span class="sr-only">Dismiss</span>
								<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path
										d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z">
									</path>
								</svg>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="flex bg-white shadow rounded-lg mt-2" style="max-width: 850px;">
		<div class="flex flex-col">
			<div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 px-6 py-4" style="width: 280px;">
				<nav class="flex flex-col">
					<ul role="list" class="flex flex-col gap-y-7">
						<li>
							<ul role="list" class="-mx-2 space-y-1">
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
		<main class="dfwp-content-wrapper lg:flex-auto ">
			<?php
			foreach ( $dfwp_setting_options as $nav_option ) {
				echo wp_kses( $nav_option['main_html'], $allowed_post_tags );
			}
			?>
		</main>
	</div>
	<p class="submit"><input type="submit" name="submit" id="dwp_settings_submit" class="save_settings_dwp button button-primary" value="Save Changes"></p>
</div>

<?php
do_action( 'defend_wp_firewall_setting_end' );
