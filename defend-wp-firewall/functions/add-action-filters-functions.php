<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Defend_WP_Firewall_Add_Action_Filter_Functions {

	private $action_rules = array();
	private $filter_rules = array();
	private $firewall_obj;

	public function __construct() {
		$this->firewall_obj = new Defend_WP_Firewall_Functions();
	}

	public function add_filter( $rule ) {
		if ( ! empty( $this->filter_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options'] ) && ! empty( $rule['options']['add_filter'] ) ) {
			$this->filter_rules[ $rule['id'] ] = $rule['options']['add_filter'];

			$this->register_dynamic_filter( $rule['options']['add_filter'] );
		}
	}

	public function add_action( $rule ) {
		if ( ! empty( $this->action_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options'] ) && ! empty( $rule['options']['add_action'] ) ) {
			$this->action_rules[ $rule['id'] ] = $rule['options']['add_action'];

			$this->register_dynamic_action( $rule['options']['add_action'] );
		}
	}

	private function register_dynamic_filter( $filters ) {
		if ( empty( $filters ) ) {
			return;
		}
		foreach ( $filters as $filter ) {
			if ( ! empty( $filter['hook_name'] ) ) {
				$priority = 10;
				if ( ! empty( $filter['priority'] ) ) {
					$priority = $filter['priority'];
				}
				if ( ! empty( $filter['args'] ) ) {
					add_filter( $filter['hook_name'], array( $this, 'single_callback_filter' ), $priority, $filter['args'] );
				} else {
					add_filter( $filter['hook_name'], array( $this, 'single_callback_filter' ), $priority );
				}
			}
		}
	}
	private function register_dynamic_action( $filters ) {
		if ( empty( $filters ) ) {
			return;
		}
		foreach ( $filters as $filter ) {
			if ( ! empty( $filter['hook_name'] ) ) {
				$priority = 10;
				if ( ! empty( $filter['priority'] ) ) {
					$priority = $filter['priority'];
				}
				if ( ! empty( $filter['args'] ) ) {
					add_action( $filter['hook_name'], array( $this, 'single_callback_action' ), $priority, $filter['args'] );
				} else {
					add_action( $filter['hook_name'], array( $this, 'single_callback_action' ), $priority );
				}
			}
		}
	}

	public function single_callback_filter() {
		$args = func_get_args();
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $arg_key => $arg_value ) {
			if ( empty( $arg_value ) ) {
				continue;
			}
			if ( is_string( $arg_value ) || is_array( $arg_value ) ) {
				$args[ $arg_key ] = $this->run_all_rules( $arg_key, $arg_value, 'add_filter' );
			}
		}

		return $args[0];
	}

	public function single_callback_action() {
		$args = func_get_args();
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $arg_key => $arg_value ) {
			if ( empty( $arg_value ) ) {
				continue;
			}
			if ( is_string( $arg_value ) || is_array( $arg_value ) ) {
				$args[ $arg_key ] = $this->run_all_rules( $arg_key, $arg_value, 'add_action' );
			}
		}
		return $args[0];
	}

	public function run_all_rules( $args_key, $args, $run_type = null ) {
		if ( empty( $run_type ) ) {
			$run_type = 'add_filter';
		}
		if ( $run_type === 'add_filter' ) {
			$rules = $this->filter_rules;
		} elseif ( $run_type === 'add_action' ) {
			$rules = $this->action_rules;
		} else {
			return $args;
		}
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return $args;
		}
		$current_hook      = current_filter();
		$current_rule_data = $this->get_rule_by_hook( $rules, $current_hook );
		if ( empty( $current_rule_data ) ) {
			return $args;
		}
		$current_rule = $current_rule_data['rule'];
		$firewall_id  = $current_rule_data['id'];
		if ( empty( $current_rule ) ) {
			return;
		}
		$rule  = $current_rule['rule'];
		$block = false;
		if ( ! empty( $current_rule['block'] ) ) {
			$block = $current_rule['block'];
		}
		$log = false;
		if ( ! empty( $current_rule['log'] ) ) {
			$log = $current_rule['log'];
		}
		$rule_type = '';
		if ( ! empty( $current_rule['rule_type'] ) ) {
			$rule_type = $current_rule['rule_type'];
		}

		if ( is_string( $args ) ) {
			$result = false;
			if ( $rule_type === 'value' ) {
				$result = $this->firewall_obj->check_rule( $args, $rule );
			}
			if ( $rule_type === 'key' ) {
				$result = $this->firewall_obj->check_rule( $args_key, $rule );
			}
			if ( $rule_type === 'full' ) {
				if ( $this->firewall_obj->check_rule( $args_key, $rule, 'key' ) ) {
					$result = $this->firewall_obj->check_rule( $args, $rule );
				}
			}

			if ( $result !== false ) {
				if ( ! empty( $block ) ) {
					$title = $this->firewall_obj->format_firewall_title( array( $run_type => true ) );
					defend_wp_firewall_die(
						array(
							'type'        => 'firewall',
							'firewall_id' => $firewall_id,
							'title'       => $title . ' (ID #' . ( $firewall_id ) . ')',
							'message'     => 'Access denied by firewall.',
							'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $current_hook ) ),
						),
						$log,
						$block
					);
				} elseif ( ! empty( $current_rule['do_sanitize'] ) ) {
					$shortcode_return = $this->firewall_obj->do_sanitize( $args, $args_key, $current_rule['do_sanitize'] );
					$args             = $shortcode_return['request_value'];
				}
			}
		} elseif ( is_array( $args ) ) {
			foreach ( $args as $arg_key => $arg_value ) {
				$args[ $arg_key ] = $this->run_all_rules( $arg_key, $arg_value, $run_type );
			}
		}
		return $args;
	}

	public function get_rule_by_hook( $rules, $hook_name ) {
		if ( empty( $rules ) ) {
			return;
		}
		foreach ( $rules as $rule_id => $filters ) {
			foreach ( $filters as $filter ) {
				if ( ! empty( $filter['hook_name'] ) && $filter['hook_name'] === $hook_name ) {
					return array(
						'rule' => $filter,
						'id'   => $rule_id,
					);
				}
			}
		}
		return false;
	}
}
