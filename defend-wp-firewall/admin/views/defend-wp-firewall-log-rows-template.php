<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parent_prev_id      = '';
$this_same_row_count = 0;

$copy_all_dwp_logs = $all_dwp_logs;
$allowed_post_tags = $this->allowed_post_tags();

foreach ( $all_dwp_logs as $key => $value ) {
	$value_extra_full_str = $value['extra'];
	$value['extra']       = json_decode( $value['extra'], true );

	$this_hidden_class   = '';
	$this_same_row_div   = '';
	$this_same_row_count = 0;

	if ( empty( $block_type_dfwp_from_get ) && empty( $block_type_dfwp_from_post ) ) {

		$this_next_key = $key + 1;
		foreach ( $copy_all_dwp_logs as $kk => $vv ) {
			if ( $kk < $this_next_key ) {

				continue;
			}

			if ( $value['type'] == $vv['type'] ) {
				++$this_same_row_count;
			} else {

				break;
			}
			if ( $this_same_row_count > 0 ) {
				$this_same_row_div = '<div class=" expand_log_row_dfwp text-xs text-lime-600 cursor-pointer mt-1 " parent_prev_id="' . $value['id'] . '" >Show ' . $this_same_row_count . ' more</div>';
			}
		}

		$this_prev_key = $key - 1;
		if ( ! empty( $all_dwp_logs[ $this_prev_key ] ) && $value['type'] == $all_dwp_logs[ $this_prev_key ]['type'] ) {
			if ( empty( $parent_prev_id ) ) {
				$parent_prev_id = $all_dwp_logs[ $this_prev_key ]['id'];
			}
			$this_hidden_class = ' log_row_hidden_dfwp ';
			$this_same_row_div = '';
		} else {
			$parent_prev_id    = '';
			$this_hidden_class = '';
		}
	}

	?>

	<tr log_id="<?php echo esc_html( $value['id'] ); ?>" parent_prev_id="<?php echo esc_attr( $parent_prev_id ); ?>" class=" <?php echo esc_attr( $this_hidden_class ); ?> ">
		<td class="relative py-5 pr-6">
			<div class="flex gap-x-6">
				<div class="flex-auto">
					<div class="flex items-start gap-x-3">
						<div class="text-sm font-medium leading-4 text-gray-900" style="width: 200px;"><?php echo esc_html( $value['title'] ); ?></div>
					</div>
					<div class="text-xs leading-5 text-gray-500"><?php echo esc_html( wp_date( 'd M @ h:ia', $value['ts'] ) ); ?></div>
					<?php echo wp_kses( $this_same_row_div, $allowed_post_tags ); ?>
				</div>
			</div>
			<div class="absolute bottom-0 right-full h-px w-screen bg-gray-100"></div>
			<div class="absolute bottom-0 left-0 h-px w-screen bg-gray-100"></div>
		</td>
		<td class="hidden py-5 pr-6 sm:table-cell">
			<div class="text-xs leading-4 text-gray-900 py-1 font-mono break-words" style="width:150px;"><?php echo esc_html( $value['source_ip'] ); ?></div>
			<div class="dfwp_whitelist_ip_from_log mt-1 text-xs leading-5 text-lime-600 hover:underline cursor-pointer whitespace-nowrap">Whitelist IP</div>
		</td>
		<td class="py-5">
			<div class="flex">
				<div class="collapsed_vars_cont_dwp overflow-x-hidden overflow-y-auto rounded-md p-2 text-xs font-normal ring-1 ring-inset text-gray-700 bg-gray-50 ring-gray-500/10 font-mono" style="max-height: 400px;">
					<?php
						$this_header_to_print = '...';
					if ( ! empty( $value['source_url'] ) ) {
						$this_header_to_print = $value['source_url'];
					} elseif ( ! empty( $value['extra'] ) && ! empty( $value['extra']['POST'] ) ) {
						$str_to_print         = wp_json_encode( $value['extra']['POST'], JSON_UNESCAPED_SLASHES );
						$str_to_print         = substr( $str_to_print, 0, 200 );
						$this_header_to_print = $str_to_print;
					} elseif ( ! empty( $value['extra'] ) && ! empty( $value['extra']['GET'] ) ) {
						$str_to_print         = wp_json_encode( $value['extra']['GET'], JSON_UNESCAPED_SLASHES );
						$str_to_print         = substr( $str_to_print, 0, 200 );
						$this_header_to_print = $str_to_print;
					}
					?>
					<div class="collapsed_vars_header_dwp text-ellipsis overflow-hidden whitespace-nowrap break-all"><?php echo esc_html( $this_header_to_print ); ?></div>
					<div class="collapsed_vars_dwp" style="display: none;">
						<div class="pb-2 break-all">URL: <?php echo ! empty( $value['source_url'] ) ? esc_html( $value['source_url'] ) : '---'; ?></div>
						<?php if ( empty( $value['extra'] ) ) { ?> 

								<div class="pt-2 mt-2 border-t">REQUEST: [</div>
									<div class="flex relative items-center group justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r">
										<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $value_extra_full_str ); ?>
										</div>
									</div>
								<div>]</div>

						<?php } else { ?>

							<?php
							if ( ! empty( $value['extra']['user_login'] ) ) {
								?>
								<div class="">User: <?php echo esc_html( $value['extra']['user_login'] ) ?? ''; ?> </div> <?php } ?>
							<?php if ( ! empty( $value['extra']['POST'] ) && $value['extra']['POST'] ) { ?>
							<div class="pt-2 mt-2 border-t">POST request: [</div>
								<?php
								if ( is_string( $value['extra']['POST'] ) ) {
									$vv = $value['extra']['POST'];
									?>
																			<div class="flex relative items-center group justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r">
											<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $vv ); ?>
											</div>
										</div>
								<?php } else { ?>
									<?php
									foreach ( $value['extra']['POST'] as $kk => $vv ) {
										if ( is_array( $vv ) ) {
											$vv = wp_json_encode( $vv, JSON_UNESCAPED_SLASHES ); }
										?>
										<div class="flex relative items-center group justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r" this_key="<?php echo esc_attr( $kk ); ?>">
											<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $kk . ' = ' . $vv ); ?>
												<div class="opacity-0 group-hover:opacity-100 absolute right-1 inline">
													<div class="dfwp_whitelist_pr_from_log rounded-md py-1 px-2 text-xs font-normal text-white bg-lime-600 cursor-pointer inline" with_ip='1'>Allow for this IP</div>
													<div class="dfwp_whitelist_pr_from_log rounded-md py-1 px-2 text-xs font-normal text-white bg-lime-600 cursor-pointer inline">Allow for all</div>
												</div>
											</div>
										</div>
										<?php
									}
								}
								?>
							<div>]</div>
							<?php } ?>
							<?php if ( ! empty( $value['extra']['GET'] ) && $value['extra']['GET'] ) { ?>
							<div class="pt-2 mt-2 border-t">GET request: [</div>
								<?php
								foreach ( $value['extra']['GET'] as $kk => $vv ) {
									if ( is_array( $vv ) ) {
										$vv = wp_json_encode( $vv, JSON_UNESCAPED_SLASHES ); }
									?>
									<div class="flex relative items-center group justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r" this_key="<?php echo esc_attr( $kk ); ?>">
										<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $kk . ' = ' . $vv ); ?>
											<div class="opacity-0 group-hover:opacity-100 absolute right-1 inline">
												<div class="dfwp_whitelist_gr_from_log rounded-md py-1 px-2 text-xs font-normal text-white bg-lime-600 cursor-pointer inline" with_ip='1'>Allow for this IP</div>
												<div class="dfwp_whitelist_gr_from_log rounded-md py-1 px-2 text-xs font-normal text-white bg-lime-600 cursor-pointer inline">Allow for all</div>
											</div>
										</div>
									</div>
								<?php } ?>
							<div>]</div>
							<?php } ?>
							<?php if ( ! empty( $value['extra']['HEADER'] ) && $value['extra']['HEADER'] ) { ?>
							<div class="pt-2 mt-2 border-t">HEADER: [</div>
								<?php
								foreach ( $value['extra']['HEADER'] as $kk => $vv ) {
									if ( is_array( $vv ) ) {
										$vv = wp_json_encode( $vv, JSON_UNESCAPED_SLASHES ); }
									?>
									<div class="flex relative items-center justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r" this_key="<?php echo esc_html( $kk ); ?>">
										<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $kk . ' = ' . $vv ); ?>
										</div>
									</div>
								<?php } ?>
							<div>]</div>
							<?php } ?>
							<?php if ( ! empty( $value['extra']['more_details'] ) && $value['extra']['more_details'] ) { ?>
							<div class="pt-2 mt-2 border-t">MORE_DETAILS: [</div>
								<?php
								foreach ( $value['extra']['more_details'] as $kk => $vv ) {
									if ( is_array( $vv ) ) {
										$vv = wp_json_encode( $vv, JSON_UNESCAPED_SLASHES ); }
									?>
									<div class="flex relative items-center justify-between bg-gray-100 hover:bg-lime-200 -mx-2 px-2 mb-1 border-l border-r" this_key="<?php echo esc_html( $kk ); ?>">
										<div class="px-2 py-1 w-full break-all"><?php echo esc_html( $kk . ' = ' . $vv ); ?>
										</div>
									</div>
								<?php } ?>
							<div>]</div>
							<?php } ?>

						<?php } ?>
					</div>
				</div>
			</div>
			<div class="inline-block show_variables_dwp_log mt-1 text-xs leading-5 text-lime-600 hover:underline cursor-pointer">Check variables to allow request</div>
			<?php if ( ! empty( $value['type'] ) && $value['type'] === 'nonce_checker' ) { ?>
				<div class="border rounded-lg border-yellow-400 bg-yellow-50 p-2 mt-4">
					<div class="flex">
						<div class="flex-shrink-0">
							<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
								<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
							</svg>
						</div>
						<div class="ml-3">
							<p class="text-xs text-yellow-700">If you are facing any page loading or data displaying issues on your website, please clear all the caches. If you use a cache plugin or your hosting provider has a caching service, clear that as well.<br><br>
							If the issue persists, disable the 'Add DefendWP nonce for all requests' under Settings › 'GET &amp; POST Requests' and <a class="font-medium text-yellow-700 underline hover:text-yellow-600" href="mailto:help@defendwp.org?subject=Facing%20issue%20with%20page%20loading%20or%20data%20displaying&amp;body=I%20was%20facing%20issues%20with%20the%20website%20and%20disabled%20the%20'Add%20DefendWP%20nonce%20for%20all%20requests'%20as%20instructed.%20What%20next%3F">contact us</a>.
							</p>
						</div>
					</div>
				</div> 
			<?php } ?>
		</td>
	</tr>

	<?php
	if ( empty( $this_hidden_class ) ) {
		$this_same_row_count = 0;
	}
	$last_log_id = $value['id'];
}
?>

<?php if ( ! empty( $last_log_id ) ) { ?>
	<tr class="more_tr_logs_dwp">
		<td class="load_more_logs_dwp py-2 text-lime-600 hover:underline cursor-pointer text-center text-xs uppercase" colspan="3" last_log_id="<?php echo esc_html( $last_log_id ); ?>">Load More</td>
	</tr>
<?php } ?>
