<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Run_Functions {

	private $run_function_matched_rules = array();

	public function defend_wp_firewall_run( $rule ) {
		if ( ! empty( $this->run_function_matched_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['run'] ) && ! empty( $rule['options']['run']['always_run'] ) ) {
			$this->run_function_matched_rules[ $rule['id'] ] = $rule;
		}
	}

	public function process_always_run_functions() {
		$this->run_functions();
	}


	public function register_run_functions( $dfwp_firewall_rule, $firewall_id ) {
		if ( isset( $this->run_function_matched_rules[ $firewall_id ] ) ) {
			return false;
		}

		$this->run_function_matched_rules[ $firewall_id ] = array();
		$this->run_function_matched_rules[ $firewall_id ] = $dfwp_firewall_rule;
	}

	public function run_functions() {
		if ( empty( $this->run_function_matched_rules ) ) {
			return;
		}
		$registered_functions = array( 'defend_wp_firewall_plugin_backuply_1_3_4', 'defend_wp_firewall_plugin_wp_easy_gallery_4_8_5', 'defend_wp_firewall_plugin_the_events_calendar_6_6_4', 'defend_wp_firewall_plugin_watchtowerhq_3_9_6', 'defend_wp_firewall_plugin_really_simple_ssl_9_0_0', 'defend_wp_firewall_plugin_contest_24_0_7' );

		$registered_functions = apply_filters( 'defend_wp_firewall_register_run_function', $registered_functions );
		foreach ( $this->run_function_matched_rules as $firewall_id => $dfwp_firewall_rule ) {
			if ( empty( $dfwp_firewall_rule ) && empty( $dfwp_firewall_rule['options'] ) ) {
				continue;
			}
			if ( empty( $dfwp_firewall_rule['options']['run'] ) ) {
				continue;
			}

			$run_functions = $dfwp_firewall_rule['options']['run'];

			if ( empty( $run_functions['callback'] ) ) {
				continue;
			}

			$callback = $run_functions['callback'];
			if ( ! in_array( $callback, $registered_functions, true ) ) {
				continue;
			}

			if ( ! function_exists( $callback ) ) {
				continue;
			}

			$args = ! empty( $run_functions['args'] ) ? $run_functions['args'] : array();

			$args['dfwp_firewall_rule'] = $dfwp_firewall_rule;
			$args['run_functions']      = $run_functions;
			call_user_func( $callback, $args );
		}
	}
}
