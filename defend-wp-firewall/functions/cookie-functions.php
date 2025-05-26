<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Cookie_Functions {
	public $user_cookie;
	private $ipify_ip_dfwp = 'ipify_ip_dfwp';

	public function __construct() {
	}

	public function get_user_cookie( $cookie_name ) {
		if ( empty( $_COOKIE[ $cookie_name ] ) ) {

			return false;
		}
		$sanitized_cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$this_cookie            = base64_decode( $sanitized_cookie_value );

		return json_decode( $this_cookie, true );
	}

	public function make_user_cookie( $cookie_name, $cookie_val ) {

		defend_wp_firewall_log( $cookie_val, '--------make_user_cookie--------' );

		$value = base64_encode( wp_json_encode( $cookie_val ) );

		setcookie( $cookie_name, $value, time() + DEFEND_WP_FIREWALL_COOKIE_EXP_TIME, COOKIEPATH, COOKIE_DOMAIN );
	}

	public function delete_user_cookie( $cookie_name ) {

		defend_wp_firewall_log( $cookie_name, '--------delete_user_cookie--------' );
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return;
		}

		unset( $_COOKIE[ $cookie_name ] );

		setcookie( $cookie_name, false, time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
	}

	public function save_ipify_ip_cookie( $this_ip = '' ) {
		$this_ip = sanitize_text_field( $this_ip );

		$defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$ip_site_unique_id_dfwp     = $defend_wp_firewall_options->get_option( 'ip_site_unique_id_dfwp' );

		$this_enc_ip = base64_encode( $this_ip . '||||' . $ip_site_unique_id_dfwp );
		$this->make_user_cookie( $this->ipify_ip_dfwp, $this_enc_ip );
	}

	public function get_ipify_ip_from_cookie() {
		$this_ip = $this->get_user_cookie( $this->ipify_ip_dfwp );

		if ( empty( $this_ip ) ) {

			return false;
		}

		$this_ip = base64_decode( $this_ip );

		$this_ip_arr = explode( '||||', $this_ip );

		if ( empty( $this_ip_arr[1] ) ) {

			return false;
		}

		$defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$ip_site_unique_id_dfwp     = $defend_wp_firewall_options->get_option( 'ip_site_unique_id_dfwp' );

		if ( $this_ip_arr[1] != $ip_site_unique_id_dfwp ) {

			return false;
		}

		return sanitize_text_field( $this_ip_arr[0] );
	}

	public function delete_ipify_cookie() {
		$this->delete_user_cookie( $this->ipify_ip_dfwp );
	}
}
