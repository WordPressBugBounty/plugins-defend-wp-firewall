<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Rules_Manager {
	private $defend_wp_firewall_options;

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
	}

	public function get_firewall_rules( $full_rules = false ) {
		$rules = $this->defend_wp_firewall_options->get_option( 'dfwp_firewall' );
		if ( empty( $rules ) ) {
			return;
		}
		$rules = json_decode( $rules, true );
		if ( $rules == '' || is_null( $rules ) ) {
			return;
		}
		if ( $full_rules ) {
			return $rules;
		}
		$supported_rules = array();
		foreach ( $rules as $key => $rule ) {
			if ( version_compare( $rule['rule_version'], DEFEND_WP_FIREWALL_RULES_VERSION, '>' ) ) {
				continue;
			}
			$supported_rules[ $key ] = $rule;
		}
		return $supported_rules;
	}

	public function set_rules( $rules ) {
		$result = $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', sanitize_text_field( wp_json_encode( $rules ) ) );
		$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
		do_action( 'defend_wp_firewall_set_rules', $rules );
		return $result;
	}

	public function delete_rules() {
		$result = $this->defend_wp_firewall_options->delete_option( 'dfwp_firewall' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_firewall_last_sync' );

		return $result;
	}
}
