<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Login_Functions {
	private $user_logout_rule;
	private $firewall_obj;

	public function __construct() {
		$this->firewall_obj = new Defend_WP_Firewall_Functions();
	}
	public function defend_wp_firewall_matched_rule_action( $dfwp_firewall_rule ) {
		$this->filter_by_logout_rule( $dfwp_firewall_rule );
	}

	public function filter_by_logout_rule( $rule ) {
		if ( ! empty( $rule['options'] ) && ! empty( $rule['options']['wp_logout'] ) ) {
			if ( ! empty( $rule['options']['wp_logout'] ) && empty( $this->user_logout_rule[ $rule['id'] ] ) ) {
				$this->user_logout_rule[ $rule['id'] ] = $rule;
			}
		}
	}

	public function secure_auth_cookie( $secure, $user_id ) {

		if ( empty( $this->user_logout_rule ) ) {
			return $secure;
		}
		$this->process_request();
	}

	private function process_request( $wp_logout = true ) {
		if ( empty( $this->user_logout_rule ) ) {
			return false;
		}
		foreach ( $this->user_logout_rule as $firewall_id => $dfwp_firewall_rule ) {
			$title                        = $this->firewall_obj->format_firewall_title( $dfwp_firewall_rule['options'] );
			$matched_post_data            = array();
			$matched_post_data['user_id'] = get_current_user_id();
			$block                        = $dfwp_firewall_rule['options']['wp_logout']['block'];
			$log                          = $dfwp_firewall_rule['options']['wp_logout']['log'];
			if ( $wp_logout ) {
				wp_logout();
			}
			defend_wp_firewall_die(
				array(
					'type'        => 'firewall',
					'firewall_id' => $firewall_id,
					'title'       => $title . ' (ID #' . ( $firewall_id ) . ')',
					'message'     => 'Access denied by firewall.',
					'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_post_data ) ),
				),
				$log,
				$block
			);
		}
	}

	public function wp_logout() {
		if ( empty( $this->user_logout_rule ) ) {
			return false;
		}
		$this->process_request( true );
	}
}
