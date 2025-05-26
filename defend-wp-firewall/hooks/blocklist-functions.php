<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Blocklist_Functions_Hooks {

	public function __construct() {
	}

	public function define_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_block_ip_from_settings_dfwp', array( $this, 'block_ip_from_settings_dfwp' ) );
		add_action( 'wp_ajax_remove_single_blocklist_dfwp', array( $this, 'remove_single_blocklist_dfwp' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'common_enqueue_scripts' ), 100000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'common_enqueue_scripts' ), 100000 );
		add_action( 'login_enqueue_scripts', array( $this, 'common_enqueue_scripts' ), 100000 );

		add_action( 'wp_ajax_save_ipify_ip_dfwp', array( $this, 'save_ipify_ip_dfwp' ) );
		add_action( 'wp_ajax_nopriv_save_ipify_ip_dfwp', array( $this, 'save_ipify_ip_dfwp' ) );
	}

	public function init() {
		$this->reset_ipify_ip();
		$defend_wp_firewall_blocklist = new Defend_WP_Firewall_Blocklist_Functions();
		$defend_wp_firewall_blocklist->check_and_block();
	}

	public function common_enqueue_scripts() {
		if ( defined( 'DEFEND_WP_FIREWALL_BLOCKED' ) ) {
			global $wp_scripts;
			foreach ( $wp_scripts->queue as $script ) {
				wp_dequeue_script( $script );
				wp_deregister_script( $script );
			}
		}
		wp_enqueue_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-blocklist-common', plugin_dir_url( __FILE__ ) . 'js/blocklist-common.js', array( 'jquery' ), DEFEND_WP_FIREWALL_VERSION, false );
		wp_localize_script(
			DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-blocklist-common',
			'defend_wp_firewall_common_blocklist_obj',
			array(
				'security' => wp_create_nonce( 'dwp_firewall_revmakx' ),
				'ipify_ip' => $this->get_ipify_ip_dfwp(),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function get_ipify_ip_dfwp() {
		$ip_obj      = new Defend_WP_Firewall_IP_Address();
		$ipify       = $ip_obj->get_ipify_ip_dfwp();
		$fallback_ip = $ip_obj->get_fallback_ip();
		if ( $ipify === $fallback_ip ) {
			return $ipify;
		}
		return false;
	}

	public function reset_ipify_ip() {
		$ip_obj      = new Defend_WP_Firewall_IP_Address();
		$ipify       = $ip_obj->get_ipify_ip_dfwp();
		$fallback_ip = $ip_obj->get_fallback_ip();
		if ( $ipify === $fallback_ip ) {
			return;
		}
		if ( ! empty( $ipify ) ) {
			$cookie_obj = new Defend_WP_Firewall_Cookie_Functions();
			$cookie_obj->delete_ipify_cookie();
		}
		return false;
	}

	public function save_ipify_ip_dfwp() {
		defend_wp_firewall_verify_ajax_requests( false ); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST ) || empty( $_POST['ip'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'No IP.',
				)
			);

			return false;
		}

		$dwp_cookie_functions = new Defend_WP_Firewall_Cookie_Functions();
		$dwp_cookie_functions->save_ipify_ip_cookie( sanitize_text_field( wp_unslash( $_POST['ip'] ) ) );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);
	}

	public function remove_single_blocklist_dfwp() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification

        // phpcs:disable WordPress.Security.NonceVerification.Missing
		defend_wp_firewall_log( $_POST, '--------_POST----remove_single_blocklist_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['this_id'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_id = sanitize_text_field( wp_unslash( $_POST['this_id'] ) );

		$defend_wp_firewall_blocklist = new Defend_WP_Firewall_Blocklist_Functions();
		$defend_wp_firewall_blocklist->remove_global_blocklist_by_id(
			array(
				'id' => $this_id,
			)
		);

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$update_ui = array();
		if ( $type == 'IP' ) {
			$update_ui = array(
				'id'   => '#dfwp-blocklist-ip-list',
				'html' => $defend_wp_firewall_blocklist->blocklist_ip_address_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}

	public function block_ip_from_settings_dfwp() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
        // phpcs:disable WordPress.Security.NonceVerification.Missing
		defend_wp_firewall_log( $_POST, '--------_POST----block_ip_from_settings_dfwp----' );

		if ( empty( $_POST ) || empty( $_POST['IP'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing contents.',
				)
			);

			return false;
		}

		$this_ip = sanitize_text_field( wp_unslash( $_POST['IP'] ) );

		$defend_wp_firewall_blocklist = new Defend_WP_Firewall_Blocklist_Functions();
		$defend_wp_firewall_blocklist->set_global_blocklist(
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
				'id'   => '#dfwp-blocklist-ip-list',
				'html' => $defend_wp_firewall_blocklist->blocklist_ip_address_list(),
			);
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success'   => true,
				'update_ui' => $update_ui,
			)
		);
	}
}
