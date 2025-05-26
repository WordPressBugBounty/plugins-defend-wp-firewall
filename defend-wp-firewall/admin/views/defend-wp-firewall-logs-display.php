<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php


$defend_wp_firewall_options = new Defend_WP_Firewall_Options();

$dfwp_block_types = array(
	''                      => 'Block Type',
	'firewall'              => 'Firewall restriction',
	'nonce_checker'         => 'AJAX request restriction',
	'sanitize_post_request' => 'POST request sanitization',
	'sanitize_get_request'  => 'GET request sanitization',
);

$dfwp_block_types = apply_filters( 'dfwp_firewall_log_filter_types', $dfwp_block_types );

$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();

$block_type_dfwp_from_get = ! empty( $_GET['block_type_dfwp'] ) ? sanitize_text_field( wp_unslash( $_GET['block_type_dfwp'] ) ) : '';

$all_dwp_logs = $defend_wp_firewall_logs->get_all_logs( $block_type_dfwp_from_get );

do_action( 'defend_wp_before_login_page_start' );

?>
<div class="wrap">
	<h1>DefendWP - Blocked requests</h1>
	<div class="dwp_logs_wrapper bg-white shadow rounded-lg mt-2 mr-4 overflow-hidden" style="width: 900px;">
		<?php if ( empty( $all_dwp_logs ) && empty( $block_type_dfwp_from_get ) ) { ?> 
			<div class="text-center pb-2 pt-2">
				<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
					<path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6L21 6.00078M8 12L21 12.0008M8 18L21 18.0007M3 6.5H4V5.5H3V6.5ZM3 12.5H4V11.5H3V12.5ZM3 18.5H4V17.5H3V18.5Z"/>
				</svg>
				<h3 class="mt-1 text-sm font-semibold text-gray-900">A log of blocked requests will appear here.</h3>
				<p class="mt-1 text-sm text-gray-500">You can whitelist them by IP or request variables.</p>
			</div>
		<?php } else { ?>
		<div class="overflow-hidden border-t border-gray-100">
			<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 min-h-lvh">
				<div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
					<table class="w-full text-left">
						<thead class="sr-only">
							<tr>
								<th></th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody class="tbody_logs_dwp">
							<tr class="text-sm leading-4">
								<td scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold"></td>
								<td scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold"></td>
								<td scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold z-10">
									<div class="float-right">
										<div class="relative inline-block text-left">
											<div class="select_log_type_btn_dfwp">
												<input type="hidden" name="select_log_type_value_dfwp" class="select_log_type_value_dfwp" value="<?php echo esc_attr( $block_type_dfwp_from_get ); ?>"/>
												<button type="button" class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" aria-expanded="true" aria-haspopup="true">
													<span><?php echo esc_html( $dfwp_block_types[ $block_type_dfwp_from_get ] ); ?></span>
													<svg class="-mr-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
														<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
													</svg>
												</button>
											</div>
											<div class="select_log_type_cnt_dfwp absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-scroll" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" style="width: 250px; height: 280px; display: none;"> 
												<div class="py-1 overflow-scroll" role="none" style="height: 280px;">
													<!-- Active: "bg-gray-100 text-gray-900", Not Active: "text-gray-700" -->
													<?php
													foreach ( $dfwp_block_types as $kk => $vv ) {
														echo '<a class="text-gray-700 block px-4 py-2 text-sm hover:bg-lime-100 cursor-pointer" block_type="' . esc_attr( $kk ) . '">' . esc_html( $vv ) . '</a>';
													}
													?>
												</div>
											</div>
										</div>
									</div>
									<div class="float-right py-2 pr-2">Filter By</div>
									<div class="clear_all_logs_dfwp float-right py-2 text-xs text-lime-600 cursor-pointer pr-4 font-medium">Clear all logs</div>
								</td>
							</tr>
							<tr class="text-sm leading-6 text-gray-900">
								<th scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold">
									<div class="uppercase text-xs">Block Type</div>
									<div
										class="absolute inset-y-0 right-full -z-10 w-screen border-y border-gray-200 bg-gray-50">
									</div>
									<div
										class="absolute inset-y-0 left-0 -z-10 w-screen border-y border-gray-200 bg-gray-50">
									</div>
								</th>
								<th scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold">
									<div class="uppercase text-xs">IP</div>
									<div
										class="absolute inset-y-0 left-0 -z-10 w-screen border-y border-gray-200 bg-gray-50">
									</div>
								</th>
								<th scope="colgroup" colspan="1" class="relative isolate py-2 font-semibold">
									<div class="uppercase text-xs">Blocked Request</div>
									<div
										class="absolute inset-y-0 left-0 -z-10 w-screen border-y border-gray-200 bg-gray-50">
									</div>
								</th>
							</tr>

							<?php
								include_once WP_PLUGIN_DIR . '/defend-wp-firewall/admin/views/defend-wp-firewall-log-rows-template.php';
							?>

						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
</div>
