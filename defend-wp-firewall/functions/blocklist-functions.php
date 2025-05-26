<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Blocklist_Functions {
	public $table_name;
	public $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->table_name = $this->wpdb->base_prefix . 'dfwp_blacklist';
	}

	public function check_and_block() {
		$ip        = defend_wp_firewall_get_remote_address();
		$bloked_ip = $this->get_all_blocklists();
		if ( empty( $bloked_ip ) ) {
			return false;
		}
		foreach ( $bloked_ip as $value ) {
			if ( $this->ip_in_range( $value['value'], $ip ) ) {
				defend_wp_firewall_die(
					array(
						'type'    => 'ip_block',
						'title'   => 'IP restriction',
						'message' => 'Access denied for this IP address.',
					),
					true
				);
			}
		}
	}

	public function set_global_blocklist( $data, $sanitize = false ) {
		try {
			$data = array_merge(
				array(
					'action'  => 'global',
					'hr_time' => wp_date( 'Y-m-d H:i:s' ),
					'ts'      => time(),
				),
				$data
			);

			$is_already_existing = $this->is_already_existing( $data );

			if ( $is_already_existing ) {

				return true;
			}

			$result = $this->wpdb->insert( $this->table_name, $data );

			if ( $result === false ) {
				defend_wp_firewall_log( $data, '--------set_global_blocklist-failed-------' );
				defend_wp_firewall_log( $this->wpdb->last_error, '--------set_global_blocklist-failed----last_error---' );
				defend_wp_firewall_log( $this->wpdb->last_query, '--------set_global_blocklist-failed----last_query---' );
			}
		} catch ( Exception $e ) {
			defend_wp_firewall_log( $e->getMessage(), '--------Caught error------' );
		}
	}

	public function is_already_existing( $data ) {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE type=%s AND action=%s AND value=%s; ', $this->table_name, $data['type'], $data['action'], $data['value'] ), ARRAY_A );

		if ( empty( $result ) || empty( $result[0] ) ) {

			return false;
		}

		return true;
	}

	public function get_all_blocklists() {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC;', $this->table_name ), ARRAY_A );

		return $result;
	}

	public function remove_global_blocklist_by_id( $data, $sanitize = false ) {
		try {

			if ( empty( $data ) || empty( $data['id'] ) ) {

				return false;
			}

			$result = $this->wpdb->delete( $this->table_name, $data );

			if ( $result === false ) {
				defend_wp_firewall_log( $data, '--------remove_global_blocklist_by_id-failed-------' );
				defend_wp_firewall_log( $this->wpdb->last_error, '--------remove_global_blocklist_by_id-failed----last_error---' );
				defend_wp_firewall_log( $this->wpdb->last_query, '--------remove_global_blocklist_by_id-failed----last_query---' );
			}
		} catch ( Exception $e ) {
			defend_wp_firewall_log( $e->getMessage(), '--------Caught error------' );
		}
	}

	private function ip_in_range( $ip, $range ) {

		if ( strpos( $range, '*' ) !== false ) { // a.b.*.* format
			// Just convert to A-B format by setting * to 0 for A and 255 for B.
			$lower = str_replace( '*', '0', $range );
			$upper = str_replace( '*', '255', $range );
			$range = "$lower-$upper";
		}

		if ( strpos( $range, '-' ) !== false ) { // A-B format
			list($lower, $upper) = explode( '-', $range, 2 );
			$lower_dec           = (float) sprintf( '%u', ip2long( $lower ) );
			$upper_dec           = (float) sprintf( '%u', ip2long( $upper ) );
			$ip_dec              = (float) sprintf( '%u', ip2long( $ip ) );
			return ( ( $ip_dec >= $lower_dec ) && ( $ip_dec <= $upper_dec ) );
		}
		if ( $ip == $range ) {
			return true;
		}
		return false;
	}

	public function blocklist_ip_address_list() {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE `type` = 'IP' ORDER BY id DESC;", $this->table_name ), ARRAY_A );
		ob_start();
		?>
			<div class=" rounded-tl-md rounded-tr-md relative flex flex-col border p-4 focus:outline-none">
				<div class="w-full relative">
					<input type="text" class="block_ip_dfwp_settings_val block w-full rounded-md border-0 py-1.5 text-gray-900 font-mono shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="0.0.0.0">
				<button class=" block_ip_from_settings_dfwp absolute inline-flex items-center justify-center rounded-sm bg-lime-600 px-2 py-1 text-xs font-normal text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 w-auto end-px top-px m-0.5" this_type="IP">Add</button>
				</div>
			</div>
			<?php
			foreach ( $result as $kk => $vv ) {
				if ( $vv['type'] == 'IP' ) {
					?>
						<div class=" 
						<?php
						if ( count( $result ) == $kk + 1 ) {
							?>
rounded-bl-md rounded-br-md <?php } ?> relative flex flex-row border px-4 py-3 focus:outline-none justify-between">
							<span class="flex items-center text-sm">
								<span id="pricing-plans-0-label" class="font-normal font-mono text-xs break-all"><?php echo esc_html( $vv['value'] ); ?></span>
							</span>
							<span id="pricing-plans-0-description-0" class="ml-6 pl-1 text-xs md:ml-0 md:pl-0 md:text-center">
								<a href="#" class="remove_single_blocklist_dfwp font-normal text-lime-600 hover:underline" this_id="<?php echo esc_html( $vv['id'] ); ?>" this_type="IP">Remove</a>
							</span>
						</div>
					<?php
				}
			}
			?>
		<?php
		return ob_get_clean();
	}
}
