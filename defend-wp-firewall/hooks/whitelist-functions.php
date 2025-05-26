<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Whitelist_Functions_Hooks {

	public function define_hooks() {

		add_action( 'wp_ajax_whitelist_ip_from_log_dfwp', array( $this, 'whitelist_ip_from_log_dfwp' ) );
		add_action( 'wp_ajax_whitelist_post_req_from_log_dfwp', array( $this, 'whitelist_post_req_from_log_dfwp' ) );
		add_action( 'wp_ajax_whitelist_get_req_from_log_dfwp', array( $this, 'whitelist_get_req_from_log_dfwp' ) );

		add_action( 'wp_ajax_whitelist_ip_from_settings_dfwp', array( $this, 'whitelist_ip_from_settings_dfwp' ) );
		add_action( 'wp_ajax_whitelist_pr_from_settings_dfwp', array( $this, 'whitelist_pr_from_settings_dfwp' ) );
		add_action( 'wp_ajax_whitelist_gr_from_settings_dfwp', array( $this, 'whitelist_gr_from_settings_dfwp' ) );
		add_action( 'wp_ajax_remove_single_whitelist_dfwp', array( $this, 'remove_single_whitelist_dfwp' ) );
	}

	public function remove_single_whitelist_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   

		if ( empty( $_POST ) || empty( $_POST['this_id'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_id = intval( $_POST['this_id'] );

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->remove_global_whitelist_by_id(
			array(
				'id' => $this_id,
			)
		);

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
		$update_ui = array();
		if ( $type == 'POST' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-post-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_post_request_list(),
			);
		}
		if ( $type == 'GET' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-get-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_get_request_list(),
			);
		}
		if ( $type == 'IP' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-ip-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_ip_address_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}

	public function whitelist_ip_from_settings_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_ip_from_settings_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['IP'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_ip = sanitize_text_field( wp_unslash( $_POST['IP'] ) );

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'IP',
				'action' => 'global',
				'value'  => $this_ip,
			)
		);

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$update_ui = array();

		if ( $type == 'IP' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-ip-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_ip_address_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}

	public function whitelist_pr_from_settings_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_pr_from_settings_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['pr_val'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_val = sanitize_text_field( wp_unslash( $_POST['pr_val'] ) );

		$this_val_arr = explode( '=', $this_val );

		$this_val = wp_json_encode(
			array(
				$this_val_arr[0] => $this_val_arr[1] ?? '',
			),
			JSON_UNESCAPED_SLASHES
		);

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'POST',
				'action' => 'global',
				'value'  => $this_val,
			)
		);

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$update_ui = array();
		if ( $type == 'POST' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-post-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_post_request_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}

	public function whitelist_gr_from_settings_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_gr_from_settings_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['gr_val'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_val = sanitize_text_field( wp_unslash( $_POST['gr_val'] ) );

		$this_val_arr = explode( '=', $this_val );

		$this_val = wp_json_encode(
			array(
				$this_val_arr[0] => $this_val_arr[1] ?? '',
			),
			JSON_UNESCAPED_SLASHES
		);

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'GET',
				'action' => 'global',
				'value'  => $this_val,
			)
		);

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$update_ui = array();

		if ( $type == 'GET' ) {
			$update_ui = array(
				'id'   => '#dfwp-whitelist-get-list',
				'html' => $defend_wp_firewall_whitelist->whitelist_get_request_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}

	public function whitelist_ip_from_log_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_ip_from_log_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['log_id'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$log_id = intval( $_POST['log_id'] );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$this_log                = $defend_wp_firewall_logs->get_log_by_id( $log_id );

		if ( empty( $this_log ) || empty( $this_log['source_ip'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing IP.',
				)
			);

			return false;
		}

		$this_log['source_ip'] = sanitize_text_field( $this_log['source_ip'] );

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'IP',
				'action' => 'global',
				'value'  => $this_log['source_ip'],
			)
		);

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);
	}

	public function whitelist_post_req_from_log_dfwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_post_req_from_log_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['log_id'] || empty( $_POST['this_key'] ) ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$log_id        = intval( $_POST['log_id'] );
		$post_this_key = sanitize_text_field( wp_unslash( $_POST['this_key'] ) );

		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$this_log                = $defend_wp_firewall_logs->get_log_by_id( $log_id );

		if ( empty( $this_log ) || empty( $this_log['extra'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing POST.',
				)
			);

				return false;
		}

		$this_log['extra'] = json_decode( $this_log['extra'], true );

		if ( empty( $this_log['extra']['POST'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing Post Key.',
				)
			);

			return false;
		}

		$with_ip = '';
		if ( ! empty( $_POST['with_ip'] ) && ! empty( $this_log['source_ip'] ) ) {
			$with_ip = $this_log['source_ip'] . '||||';
		}
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'POST',
				'action' => 'global',
				'value'  => wp_json_encode(
					array(
						$with_ip . $post_this_key => $this_log['extra']['POST'][ $post_this_key ],
					),
					JSON_UNESCAPED_SLASHES
				),
			)
		);

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);
	}

	public function whitelist_get_req_from_log_dfwp() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		defend_wp_firewall_log( $_POST, '--------_POST----whitelist_get_req_from_log_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['log_id'] || empty( $_POST['this_key'] ) ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$post_this_key = sanitize_text_field( wp_unslash( $_POST['this_key'] ) );

		$log_id = intval( $_POST['log_id'] );

		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$this_log                = $defend_wp_firewall_logs->get_log_by_id( $log_id );

		if ( empty( $this_log ) || empty( $this_log['extra'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing GET.',
				)
			);

			return false;
		}

		$this_log['extra'] = json_decode( $this_log['extra'], true );

		if ( empty( $this_log['extra']['GET'] ) || empty( $this_log['extra']['GET'][ $post_this_key ] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing Post Key.',
				)
			);

			return false;
		}

		$with_ip = '';
		if ( ! empty( $_POST['with_ip'] ) && ! empty( $this_log['source_ip'] ) ) {
			$with_ip = $this_log['source_ip'] . '||||';
		}

		$defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();
		$defend_wp_firewall_whitelist->set_global_whitelist(
			array(
				'type'   => 'GET',
				'action' => 'global',
				'value'  => wp_json_encode(
					array(
						$with_ip . $post_this_key => $this_log['extra']['GET'][ $post_this_key ],
					),
					JSON_UNESCAPED_SLASHES
				),
			)
		);
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);
	}
}
