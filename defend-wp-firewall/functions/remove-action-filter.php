<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Remove_Action_Filter_Functions {

	private $action_rules = array();
	private $filter_rules = array();

	public function defend_wp_firewall_remove_action( $rule ) {
		if ( ! empty( $this->action_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['remove_action'] ) && ! empty( $rule['options']['remove_action']['always_run'] ) ) {
			$this->action_rules[ $rule['id'] ] = $rule['options']['remove_action'];
		}
	}

	public function defend_wp_firewall_remove_filter( $rule ) {
		if ( ! empty( $this->filter_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['remove_filter'] ) && ! empty( $rule['options']['remove_filter']['always_run'] ) ) {
			$this->filter_rules[ $rule['id'] ] = $rule['options']['remove_filter'];
		}
	}

	public function process_remove_action_filter_rules() {
		if ( ! empty( $this->action_rules ) ) {
			foreach ( $this->action_rules as $action ) {
				$priority = 10;
				if ( ! empty( $action['priority'] ) ) {
					$priority = $action['priority'];
				}
				if ( is_string( $action['callback'] ) ) {
					remove_action( $action['hook_name'], $action['callback'], $priority );
				} elseif ( is_array( $action['callback'] ) ) {
					defend_wp_firewall_remove_by_plugin_class( $action['hook_name'], $action['callback']['class'], $action['callback']['func'], true, $priority );
				}
			}
		}

		if ( ! empty( $this->filter_rules ) ) {
			foreach ( $this->filter_rules as $filter ) {
				$priority = 10;
				if ( ! empty( $filter['priority'] ) ) {
					$priority = $filter['priority'];
				}
				if ( is_string( $filter['callback'] ) ) {
					remove_filter( $filter['hook_name'], $filter['callback'], $priority );
				} elseif ( is_array( $filter['callback'] ) ) {
					defend_wp_firewall_remove_by_plugin_class( $filter['hook_name'], $filter['callback']['class'], $filter['callback']['func'], false, $priority );
				}
			}
		}
	}
}
