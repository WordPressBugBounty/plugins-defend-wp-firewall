<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$live_vulns_count = defend_wp_firewall_get_live_vulnerabilities_count();

?>

<header class="header mb-2">
	<div class="left-section">
		<div class="logo-section">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfwp_firewall_settings' ) ); ?>" class="logo-link">
				<img src="<?php echo esc_url( DEFEND_WP_FIREWALL_PLUGIN_URL . 'assets/dwp-logo-icon-text.svg' ); ?>" alt="Defend WP Firewall Logo" class="logo-image">
			</a>
		</div>
		
		<nav class="nav">
			<ul>
				<li><a 
				class="<?php echo $active_tab === 'blocked_requests' ? 'active' : ''; ?>"
				href="<?php echo esc_url( DEFEND_WP_FIREWALL_BLOCKED_REQUESTS_PAGE_URL ); ?>" >Blocked requests</a></li>
				<li><a href="<?php echo esc_url( DEFEND_WP_FIREWALL_SETTINGS_PAGE_URL ); ?>" class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Firewall settings</a></li>
			</ul>
		</nav>
	</div>
	
	
</header>
<?php

if ( ( ! empty( $all_configs_dwp['dfwp_pub_key'] ) && $all_configs_dwp['enable_dfwp_firewall'] == 'yes' ) ) {
	?>
<div class="flex gap-2 items-stretch justify-between">
	<div class="rounded-md bg-green-200 p-4 flex-1">
		<div class="flex">
		<div class="shrink-0">
			<svg class="size-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
			<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"></path>
			</svg>
		</div>
		<div class="ml-3">
			<h3 class="text-base font-medium text-green-800">DefendWP Firewall is now Active</h3>
			<div class="mt-1 text-sm text-green-700">
			<p>All requests are monitored and suspicious ones automatically addressed.</p>
			</div>
			
		</div>
		</div>
	</div>
	<?php


	if ( ! empty( $live_vulns_count ) && $live_vulns_count > 0 ) {
		?>
	<a href="<?php echo esc_url( DEFEND_WP_FIREWALL_ORG_URL ) . 'crowd-sourced-vulnerabilities-database/'; ?>" target="_blank">
		<div class="rounded-md bg-green-200 p-4 flex-1">
			<div class="flex">
				<div class="shrink-0">
					<div class="status-indicator"></div>
				</div>
				<div class="ml-3">
					<h3 class="text-base font-medium text-green-800"><?php echo esc_html( $live_vulns_count ); ?> vulnerabilities actively monitored</h3>
					<div class="mt-1 text-sm text-green-700">
					<p>Requests that exploit these vulnerabilities are identified and addressed.</p>
					</div>
					
				</div>
			</div>
		</div>
	</a>
	<?php } ?>
	
</div>

	<?php
}
if ( empty( $all_configs_dwp['first_time_settings_page_visit_done'] ) ) {
	$defend_wp_firewall_options->set_option( 'first_time_settings_page_visit_done', true );
	?>
			<div class="dfwp_first_flap rounded-md bg-blue-100 p-4 mt-3 mb-6">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-blue-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
							<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3 flex-1 md:flex md:justify-between">
						<p class="text-sm text-blue-700">Welcome to DefendWP Firewall. We have pre-configured
some rules for your website's best defence. Feel free to customize the rules to your needs in the <a href="<?php echo esc_url( DEFEND_WP_FIREWALL_SETTINGS_PAGE_URL ); ?>" class="underline hover:text-blue-700">Firewall settings</a> page.<br><br>If you
							need any assistance with this, please reach out at <a class="underline hover:text-blue-700" href="mailto:help@defendwp.org"  target="_blank">help@defendwp.org</a>.</p>
						
					</div>
				</div>
			</div>
<?php } ?>
