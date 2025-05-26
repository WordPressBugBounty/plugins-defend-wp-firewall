<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Whitelist_Functions {

	public $table_name;
	public $wpdb;
	public $current_wp_user;
	public $defend_wp_firewall_cookies;

	const DEFAULT_WHITELISTED_IPS_DWP = array(
		'52.33.122.174',
		'52.27.206.180',
		'52.25.129.179', // WPTC IPs.
	);

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->table_name = $this->wpdb->base_prefix . 'dfwp_whitelist';

		$this->defend_wp_firewall_cookies = new Defend_WP_Firewall_Cookie_Functions();
	}

	public function set_global_whitelist( $data, $sanitize = true ) {
		try {

			$data = array_merge(
				array(
					'action'  => 'global',
					'hr_time' => wp_date( 'Y-m-d H:i:s' ),
					'ts'      => time(),
				),
				$data
			);

			$is_already_existing_whitelist = $this->is_already_existing_whitelist( $data );

			if ( $is_already_existing_whitelist ) {

				return true;
			}

			$result = $this->wpdb->insert( $this->table_name, $data );

			if ( $result === false ) {
				defend_wp_firewall_log( $data, '--------set_global_whitelist-failed-------' );
				defend_wp_firewall_log( $this->wpdb->last_error, '--------set_global_whitelist-failed----last_error---' );
				defend_wp_firewall_log( $this->wpdb->last_query, '--------set_global_whitelist-failed----last_query---' );
			}
		} catch ( Exception $e ) {
			defend_wp_firewall_log( $e->getMessage(), '--------Caught error------' );
		}
	}

	public function remove_global_whitelist_by_id( $data, $sanitize = false ) {
		try {

			if ( empty( $data ) || empty( $data['id'] ) ) {

				return false;
			}

			$result = $this->wpdb->delete( $this->table_name, $data );

			if ( $result === false ) {
				defend_wp_firewall_log( $data, '--------remove_global_whitelist_by_id-failed-------' );
				defend_wp_firewall_log( $this->wpdb->last_error, '--------remove_global_whitelist_by_id-failed----last_error---' );
				defend_wp_firewall_log( $this->wpdb->last_query, '--------remove_global_whitelist_by_id-failed----last_query---' );
			}
		} catch ( Exception $e ) {
			defend_wp_firewall_log( $e->getMessage(), '--------Caught error------' );
		}
	}

	public function remove_global_whitelist_by_value( $data, $sanitize = false ) {
		try {

			if ( empty( $data ) || empty( $data['value'] ) ) {

				return false;
			}

			$result = $this->wpdb->delete( $this->table_name, $data );

			if ( $result === false ) {
				defend_wp_firewall_log( $data, '--------remove_global_whitelist-failed-------' );
				defend_wp_firewall_log( $this->wpdb->last_error, '--------remove_global_whitelist-failed----last_error---' );
				defend_wp_firewall_log( $this->wpdb->last_query, '--------remove_global_whitelist-failed----last_query---' );
			}
		} catch ( Exception $e ) {
			defend_wp_firewall_log( $e->getMessage(), '--------Caught error------' );
		}
	}

	public function is_already_existing_whitelist( $data ) {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE type=%s AND action=%s AND value=%s; ', $this->table_name, $data['type'], $data['action'], $data['value'] ), ARRAY_A );

		if ( empty( $result ) || empty( $result[0] ) ) {

			return false;
		}

		return true;
	}

	public function is_IP_whitelisted_globally( $IP = '' ) {
		global $wpdb;
		global $defend_wp_firewall_is_IP_whitelisted_globally;

		if ( ! empty( $defend_wp_firewall_is_IP_whitelisted_globally ) && $defend_wp_firewall_is_IP_whitelisted_globally == 'yes' ) {

			return true;
		}

		if ( ! empty( $defend_wp_firewall_is_IP_whitelisted_globally ) && $defend_wp_firewall_is_IP_whitelisted_globally == 'no' ) {

			return false;
		}

		$defend_wp_firewall_is_IP_whitelisted_globally = 'no';

		if ( empty( $IP ) ) {
			$IP = defend_wp_firewall_get_remote_address();
		}

		if ( in_array( $IP, self::DEFAULT_WHITELISTED_IPS_DWP ) ) {
			$defend_wp_firewall_is_IP_whitelisted_globally = 'yes';

			return true;
		}

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE type='IP' AND action='global' AND value=%s; ", $this->table_name, $IP ), ARRAY_A );

		if ( empty( $result ) || empty( $result[0] ) ) {

			return false;
		}

		$defend_wp_firewall_is_IP_whitelisted_globally = 'yes';

		return true;
	}

	public function is_POST_whitelisted_globally() {
		global $defend_wp_firewall_is_POST_whitelisted_globally;

		if ( ! empty( $defend_wp_firewall_is_POST_whitelisted_globally ) && $defend_wp_firewall_is_POST_whitelisted_globally == 'yes' ) {

			return true;
		}

		if ( ! empty( $defend_wp_firewall_is_POST_whitelisted_globally ) && $defend_wp_firewall_is_POST_whitelisted_globally == 'no' ) {

			return false;
		}

		$defend_wp_firewall_is_POST_whitelisted_globally = 'no';
        // phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST ) ) {

			return false;
		}

		$this_ip           = defend_wp_firewall_get_remote_address() ?? false;
		$post_json_strings = array();
		foreach ( $_POST as $kk => $vv ) {
			$kk                  = sanitize_text_field( $kk );
			$vv                  = sanitize_text_field( $vv );
			$post_json_strings[] = "'" . esc_sql(
				wp_json_encode(
					array(
						$kk => $vv,
					),
					JSON_UNESCAPED_SLASHES
				)
			) . "'";
			if ( $this_ip ) {
				$post_json_strings[] = "'" . esc_sql(
					wp_json_encode(
						array(
							$this_ip . '||||' . $kk => $vv,
						),
						JSON_UNESCAPED_SLASHES
					)
				) . "'";
			}
		}
         // phpcs:enable WordPress.Security.NonceVerification.Missing
		$placeholders = implode( ', ', array_fill( 0, count( $post_json_strings ), '%s' ) );

		$query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM `$this->table_name` WHERE type='POST' AND action='global' AND value IN ($placeholders);",
			...$post_json_strings
		);

		$result = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $result ) || empty( $result[0] ) ) {

			return false;
		}

		$defend_wp_firewall_is_POST_whitelisted_globally = 'yes';

		return true;
	}

	public function is_GET_whitelisted_globally() {
		global $defend_wp_firewall_is_GET_whitelisted_globally;

		if ( ! empty( $defend_wp_firewall_is_GET_whitelisted_globally ) && $defend_wp_firewall_is_GET_whitelisted_globally == 'yes' ) {

			return true;
		}

		if ( ! empty( $defend_wp_firewall_is_GET_whitelisted_globally ) && $defend_wp_firewall_is_GET_whitelisted_globally == 'no' ) {

			return false;
		}

		$defend_wp_firewall_is_GET_whitelisted_globally = 'no';

		if ( empty( $_GET ) ) {

			return false;
		}

		$this_ip          = defend_wp_firewall_get_remote_address() ?? false;
		$get_json_strings = array();
		foreach ( $_GET as $kk => $vv ) {
			$kk                 = sanitize_text_field( $kk );
			$vv                 = sanitize_text_field( $vv );
			$get_json_strings[] = "'" . esc_sql(
				wp_json_encode(
					array(
						$kk => $vv,
					),
					JSON_UNESCAPED_SLASHES
				)
			) . "'";
			if ( $this_ip ) {
				$get_json_strings[] = "'" . esc_sql(
					wp_json_encode(
						array(
							$this_ip . '||||' . $kk => $vv,
						),
						JSON_UNESCAPED_SLASHES
					)
				) . "'";
			}
		}

		$placeholders = implode( ', ', array_fill( 0, count( $get_json_strings ), '%s' ) );

		$query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM `$this->table_name` WHERE type='GET' AND action='global' AND value IN ($placeholders);",
			...$get_json_strings
		);

		$result = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $result ) || empty( $result[0] ) ) {

			return false;
		}

		$defend_wp_firewall_is_GET_whitelisted_globally = 'yes';

		return true;
	}

	public function is_whitelisted_by_any_means() {
		global $defend_wp_firewall_is_ALL_whitelisted_globally;
		if ( ! empty( $defend_wp_firewall_is_ALL_whitelisted_globally ) && $defend_wp_firewall_is_ALL_whitelisted_globally == 'yes' ) {

			return true;
		}

		if ( ! empty( $defend_wp_firewall_is_ALL_whitelisted_globally ) && $defend_wp_firewall_is_ALL_whitelisted_globally == 'no' ) {

			return false;
		}

		$defend_wp_firewall_is_ALL_whitelisted_globally = 'no';

		if ( $this->is_IP_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted----IP------' );

			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		if ( $this->is_POST_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted----POST-------' );

			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		if ( $this->is_GET_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted----GET------' );

			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		if ( $this->is_iwp_request() ) {
			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		if ( $this->is_IWP_whitelisted_globally() ) {
			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		return false;
	}

	public function get_all_whitelists() {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC;', $this->table_name ), ARRAY_A );

		return $result;
	}

	public function is_iwp_request() {
		if ( ! function_exists( 'iwp_mmb_parse_request' ) ) {
			return false;
		}

		global $defend_wp_firewall_is_ALL_whitelisted_globally;
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			if ( $defend_wp_firewall_is_ALL_whitelisted_globally == 'no' ) {
				$defend_wp_firewall_is_ALL_whitelisted_globally = false;
			}

			return false;
		}

		$is_auto_login = $this->check_iwp_auto_login();

		if ( $is_auto_login != false ) {
			return true;
		}

		$post_data_local = file_get_contents( 'php://input' );
		if ( ! empty( $post_data_local ) ) {
			return $this->check_iwp_post_request( $post_data_local );
		}
		return false;
	}

	public function check_iwp_post_request( $post_data_local ) {
		if ( strrpos( $post_data_local, '_IWP_JSON_PREFIX_' ) !== false ) {
			$request_data_array = explode( '_IWP_JSON_PREFIX_', $post_data_local );
			$request_raw_data   = $request_data_array[1];
			$data               = trim( base64_decode( $request_raw_data ) );
		}

		if ( empty( $data ) ) {
			return false;
		}
		$request_data = json_decode( $data, true );
		if ( empty( $request_data ) ) {
			return false;
		}
		$action = ! empty( $request_data['iwp_action'] ) ? $request_data['iwp_action'] : '';

		if ( $action == 'add_site' ) {
			$request_data = ! empty( $request_data['params'] ) ? $request_data['params'] : $request_data;
		}

		$signature = ! empty( $request_data['signature'] ) ? base64_decode( $request_data['signature'] ) : '';
		$id        = ! empty( $request_data['id'] ) ? $request_data['id'] : '';

		$auth = $this->iwp_authenticate_message( $action . $id, $signature, $id );
		if ( $auth === true ) {
			return true;
		}

		return false;
	}

	public function check_iwp_auto_login() {

		$is_already_exists = $this->is_IWP_whitelisted_globally();

		if ( $is_already_exists == true ) {
			return true;
		}

		global $defend_wp_firewall_iwp_auto_login_hash;

		$where      = isset( $_GET['iwp_goto'] ) ? sanitize_text_field( wp_unslash( $_GET['iwp_goto'] ) ) : false;
		$auto_login = isset( $_GET['auto_login'] ) ? sanitize_text_field( wp_unslash( $_GET['auto_login'] ) ) : 0;
		if ( ( $auto_login && ! is_user_logged_in() ) ) {
			$signature  = isset( $_GET['signature'] ) ? base64_decode( sanitize_text_field( wp_unslash( $_GET['signature'] ) ) ) : '';
			$message_id = isset( $_GET['message_id'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['message_id'] ) ) ) : '';
			$auth       = $this->iwp_authenticate_message( $where . $message_id, $signature, $message_id );
			if ( $auth === true ) {

				$defend_wp_firewall_iwp_auto_login_hash = md5( base64_encode( $signature . mt_rand() ) );

				return true;
			}
		}

		return false;
	}

	private function is_IWP_whitelisted_globally() {
		global $defend_wp_firewall_is_ALL_whitelisted_globally;
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			if ( $defend_wp_firewall_is_ALL_whitelisted_globally == 'no' ) {
				$defend_wp_firewall_is_ALL_whitelisted_globally = false;
			}

			return false;
		}

		global $defend_wp_firewall_is_ALL_whitelisted_globally;

		if ( ! empty( $defend_wp_firewall_is_ALL_whitelisted_globally ) && $defend_wp_firewall_is_ALL_whitelisted_globally == 'yes' ) {

			return true;
		}

		if ( ! empty( $defend_wp_firewall_is_ALL_whitelisted_globally ) && $defend_wp_firewall_is_ALL_whitelisted_globally == 'no' ) {

			return false;
		}

		$defend_wp_firewall_is_ALL_whitelisted_globally = 'no';

		$user_cookie = $this->defend_wp_firewall_cookies->get_user_cookie( 'dwp_user' );
		if ( empty( $user_cookie ) || empty( $user_cookie['from_iwp_hash'] ) ) {
			return false;
		}

		$current_wp_user         = wp_get_current_user();
		$user_id                 = $current_wp_user->ID;
		$dwp_iwp_auto_login_hash = get_user_meta( $user_id, 'dwp_iwp_auto_login_hash', true );

		if ( ! empty( $dwp_iwp_auto_login_hash ) && $dwp_iwp_auto_login_hash == $user_cookie['from_iwp_hash'] ) {
			$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';

			return true;
		}

		return false;
	}

	public function whitelist_post_request_list() {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE `type` = 'POST' ORDER BY id DESC;", $this->table_name ), ARRAY_A );
		ob_start();
		?>
		<div class="rounded-tl-md rounded-tr-md relative flex flex-col border p-4 focus:outline-none">
			<div class="w-full relative">
				<input type="text" class="whitelist_pr_from_settings_dfwp_val block w-full rounded-md border-0 py-1.5 text-gray-900 font-mono shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="key=value">
				<button class=" whitelist_pr_from_settings_dfwp absolute inline-flex items-center justify-center rounded-sm bg-lime-600 px-2 py-1 text-xs font-normal text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 w-auto end-px top-px m-0.5" this_type="POST">Add</button>
			</div>
		</div>

		<?php

		if ( ! empty( $result ) ) {
			foreach ( $result as $kk => $vv ) {
				if ( $vv['type'] == 'POST' ) {
					$req_val        = json_decode( $vv['value'], true );
					$val_to_display = current( $req_val );
					if ( ! empty( $val_to_display ) && is_array( $val_to_display ) ) {
						$val_to_display = wp_json_encode( $val_to_display );
					}
					$key_to_display = key( $req_val );
					$from_IP        = '';
					if ( stripos( $key_to_display, '||||' ) !== false ) {
						$key_to_display = explode( '||||', $key_to_display );
						$from_IP        = ' from IP ' . $key_to_display[0];
						$key_to_display = $key_to_display[1];
					}
					?>
						<div class=" 
						<?php
						if ( count( $result ) == $kk + 1 ) {
							?>
dfwp_row_list rounded-bl-md rounded-br-md <?php } ?> relative flex flex-row border px-4 py-3 focus:outline-none justify-between gap-4">
							<span class="flex items-center text-sm">
								<span id="pricing-plans-0-label" class="font-normal font-mono text-xs break-all"><?php echo esc_html( $key_to_display . '=' . $val_to_display . $from_IP ); ?></span>
							</span>
							<span id="pricing-plans-0-description-0" class="ml-6 pl-1 text-xs md:ml-0 md:pl-0 md:text-center">
								<a href="#" class="remove_single_whitelist_dfwp font-normal text-lime-600 hover:underline" this_id="<?php echo esc_html( $vv['id'] ); ?>" this_type="POST">Remove</a>
							</span>
						</div>
					<?php
				}
			}
		}

		return ob_get_clean();
	}

	public function whitelist_get_request_list() {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE `type` = 'GET' ORDER BY id DESC;", $this->table_name ), ARRAY_A );
		ob_start();
		?>
		<div class=" rounded-tl-md rounded-tr-md relative flex flex-col border p-4 focus:outline-none">
			<div class="w-full relative">
				<input type="text"  class="whitelist_gr_from_settings_dfwp_val block w-full rounded-md border-0 py-1.5 text-gray-900 font-mono shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="key=value">
				<button class=" whitelist_gr_from_settings_dfwp absolute inline-flex items-center justify-center rounded-sm bg-lime-600 px-2 py-1 text-xs font-normal text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 w-auto end-px top-px m-0.5" this_type="GET">Add</button>
			</div>
		</div>
		<?php
		foreach ( $result as $kk => $vv ) {
			if ( $vv['type'] == 'GET' ) {
				$req_val        = json_decode( $vv['value'], true );
				$val_to_display = current( $req_val );
				if ( ! empty( $val_to_display ) && is_array( $val_to_display ) ) {
					$val_to_display = wp_json_encode( $val_to_display );
				}
				$key_to_display = key( $req_val );
				$from_ip        = '';
				if ( stripos( $key_to_display, '||||' ) !== false ) {
					$key_to_display = explode( '||||', $key_to_display );
					$from_ip        = ' from IP ' . $key_to_display[0];
					$key_to_display = $key_to_display[1];
				}
				?>
					<div class=" 
					<?php
					if ( count( $result ) == $kk + 1 ) {
						?>
rounded-bl-md rounded-br-md <?php } ?> relative flex flex-row border px-4 py-3 focus:outline-none justify-between gap-4">
						<span class="flex items-center text-sm">
							<span id="pricing-plans-0-label" class="font-normal font-mono text-xs break-all"><?php echo esc_html( $key_to_display . '=' . $val_to_display . $from_ip ); ?></span>
						</span>
						<span id="pricing-plans-0-description-0" class="ml-6 pl-1 text-xs md:ml-0 md:pl-0 md:text-center">
							<a href="#" class="remove_single_whitelist_dfwp font-normal text-lime-600 hover:underline" this_id="<?php echo esc_html( $vv['id'] ); ?>" this_type="GET">Remove</a>
						</span>
					</div>
				<?php
			}
		}

		return ob_get_clean();
	}

	public function whitelist_ip_address_list() {
		global $wpdb;

		$result = $this->wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE `type` = 'IP' ORDER BY id DESC;", $this->table_name ), ARRAY_A );
		ob_start();
		?>
			<div class="rounded-tl-md rounded-tr-md relative flex flex-col border p-4 focus:outline-none">
				<div class="w-full relative">
					<input type="text"  class="whitelist_ip_dfwp_settings_val block w-full rounded-md border-0 py-1.5 text-gray-900 font-mono shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="0.0.0.0">
				<button class=" whitelist_ip_from_settings_dfwp absolute inline-flex items-center justify-center rounded-sm bg-lime-600 px-2 py-1 text-xs font-normal text-white shadow-sm hover:bg-lime-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 w-auto end-px top-px m-0.5" this_type="IP">Add</button>
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
							<a href="#" class="remove_single_whitelist_dfwp font-normal text-lime-600 hover:underline" this_id="<?php echo esc_html( $vv['id'] ); ?>" this_type="IP">Remove</a>
						</span>
					</div>
					<?php
				}
			}

			return ob_get_clean();
	}

	public function iwp_authenticate_message( $data = false, $signature = false, $message_id = false ) {
		global $iwp_mmb_core;// This global variable is defined in iwp-client plugin
		if ( ! $data && ! $signature ) {
			return false;
		}

		$current_message = $iwp_mmb_core->get_client_message_id();

		if ( isset( $_GET['auto_login'] ) ) {// temp fix for stopping reuse of open admin url
			if ( (int) $current_message >= (int) $message_id ) {
				return false;
			}
		}

		$pl_key = $iwp_mmb_core->get_admin_panel_public_key();
		if ( ! $pl_key ) {
			return false;
		}

		if ( checkOpenSSL() && ! $iwp_mmb_core->get_random_signature() ) {
			$verify = openssl_verify( $data, $signature, $pl_key );
			if ( $verify == 1 ) {
				return true;
			} elseif ( $verify == 0 ) {
				return false;
			} else {
				return false;
			}
		} elseif ( $iwp_mmb_core->get_random_signature() ) {

			if ( md5( $data . $iwp_mmb_core->get_random_signature() ) === $signature ) {
				$message_id = $iwp_mmb_core->set_client_message_id( $message_id );
				return true;
			}
			return false;
		} else {
			return false;
		}
	}
}
