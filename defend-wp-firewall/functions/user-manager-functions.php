<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_User_Manager_Functions {
	private $firewall_obj;
	private $dfwp_wp_delete_user_firewall_rules = array();

	public function __construct() {
		$this->firewall_obj = new Defend_WP_Firewall_Functions();
	}

	public function defend_wp_matched_rules( $dfwp_firewall_rule ) {
		$this->filter_rules_by_wp_delete_user_rules( $dfwp_firewall_rule );
	}

	private function filter_rules_by_wp_delete_user_rules( $dfwp_firewall_rule ) {
		if ( ! empty( $dfwp_firewall_rule['options'] ) && ! empty( $dfwp_firewall_rule['options']['wp_user_restrictions'] ) ) {
			if ( ! empty( $dfwp_firewall_rule['options']['wp_user_restrictions']['wp_delete_user'] ) && empty( $this->dfwp_wp_delete_user_firewall_rules[ $dfwp_firewall_rule['id'] ] ) ) {
				$this->dfwp_wp_delete_user_firewall_rules[ $dfwp_firewall_rule['id'] ] = $dfwp_firewall_rule;
			}
		}
	}

	public function delete_user( $user_id, $reassign, $user ) {
		if ( empty( $this->dfwp_wp_delete_user_firewall_rules ) ) {
			return true;
		}

		foreach ( $this->dfwp_wp_delete_user_firewall_rules as $firewall_id => $dfwp_firewall_rule ) {
			if ( ! empty( $dfwp_firewall_rule['options'] ) && ! empty( $dfwp_firewall_rule['options']['wp_user_restrictions'] ) ) {
				if ( ! empty( $dfwp_firewall_rule['options']['wp_user_restrictions']['wp_delete_user'] ) ) {
					$wp_delete_user = $dfwp_firewall_rule['options']['wp_user_restrictions']['wp_delete_user'];

					if ( ! empty( $wp_delete_user['block_immediately'] ) ) {
						$this->log_and_block( $dfwp_firewall_rule['options'], $user_id, $firewall_id );
					}
					if ( ! empty( $wp_delete_user['user_can'] ) && $this->user_can( $wp_delete_user['user_can'], $user_id ) === false ) {
						$this->log_and_block( $dfwp_firewall_rule['options'], $user_id, $firewall_id );
					}
				}
			}
		}
	}

	public function user_can( $user_can, $user_id ) {
		foreach ( $user_can as $value ) {
			if ( user_can( $user_id, $value ) ) {
				return true;
			}
		}
		return false;
	}

	public function log_and_block( $option, $user_id, $firewall_id ) {
		$wp_delete_user = $option['wp_user_restrictions']['wp_delete_user'];
		$block          = $wp_delete_user['block'];
		$log            = $wp_delete_user['log'];

		$title                        = $this->firewall_obj->format_firewall_title( $option );
		$matched_post_data            = array();
		$matched_post_data['user_id'] = $user_id;

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
