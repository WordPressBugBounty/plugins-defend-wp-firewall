<?php

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Functions {
	private $defend_wp_firewall_whitelist;
	private $defend_wp_firewall_options;
	private $do_full_sanitize = array();
	private $dfwp_get_plugin;
	private $dfwp_get_theme;
	private $dfwp_firewall_rules;
	private $request_matched_rules                 = array();
	private $nonce_matched_rules                   = array();
	private $sanitize_matched_rules                = array();
	private $sanitize_supported_request_methods    = array( 'get', 'post', 'header', 'url', 'cookie' );
	private $nonce_supported_request_methods       = array( 'get', 'post', 'raw' );
	private $available_request_methods             = array( 'post', 'get', 'header', 'raw', 'file', 'url', 'cookie' );
	private $json_base64_supported_request_methods = array( 'post', 'get', 'header', 'raw' );
	private $skip_request_methods_for_key_match    = array( 'url' );
	private $skip_request_methods_for_full_match   = array( 'url' );
	private $rule_register_functions               = array( 'wp_check_filetype', 'defend_wp_users_can_register', 'get_post_meta', 'get_post_type', 'is_email', 'get_current_user_id', 'get_user_meta', 'defend_wp_firewall_detect_sql_injection' );
	private $callable_action_hooks                 = array( 'wp_logout', 'do_sanitize', 'remove_action', 'remove_filter', 'shortcode_rules', 'do_full_sanitize', 'deactivate_plugin', 'wp_post_restrictions', 'wp_user_restrictions', 'run' );

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
	}

	public function get_firewall_rules() {
		$rules = $this->defend_wp_firewall_options->get_option( 'dfwp_firewall' );
		if ( empty( $rules ) ) {
			return;
		}
		$rules = json_decode( $rules, true );
		if ( $rules == '' || is_null( $rules ) ) {
			return;
		}
		return $rules;
	}

	public function is_enabled() {
		global $defend_wp_firewall_all_configs;
		if ( ( empty( $defend_wp_firewall_all_configs['enable_dfwp_firewall'] ) ||
		( isset( $defend_wp_firewall_all_configs['enable_dfwp_firewall'] ) && $defend_wp_firewall_all_configs['enable_dfwp_firewall'] !== 'yes' ) )
		) {
			return false;
		}

		return true;
	}

	public function pre_process_request() {
		if ( $this->is_enabled() === false ) {
			return false;
		}

		$this->defend_wp_firewall_whitelist = new Defend_WP_Firewall_Whitelist_Functions();

		if ( $this->defend_wp_firewall_whitelist->is_IP_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted IP so allowing------' );

			return false;
		}

		if ( $this->defend_wp_firewall_whitelist->is_POST_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted POST so allowing------' );

			return false;
		}
		if ( $this->defend_wp_firewall_whitelist->is_GET_whitelisted_globally() ) {
			defend_wp_firewall_log( '', '--------whitelisted POST so allowing------' );

			return false;
		}

		return true;
	}

	public function process_request( $hook_type = 'init' ) {

		if ( $this->pre_process_request() === false ) {
			return false;
		}

		$firewall_rules = $this->get_firewall_rules();
		if ( empty( $firewall_rules ) ) {
			return false;
		}
		do_action( 'defend_wp_firewall_rules_before_pre_condition_filter', $firewall_rules );

		$dfwp_formated_request = $this->fetch_request();
		if ( empty( $dfwp_formated_request ) ) {
			return false;
		}
		if ( ! class_exists( 'ExpressionLanguage' ) ) {
			require DEFEND_WP_FIREWALL_PATH . 'vendor/autoload.php';
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->dfwp_get_plugin = get_plugins();

		if ( ! function_exists( 'wp_get_themes' ) ) {
			include_once ABSPATH . 'wp-includes/theme.php';
		}
		$this->dfwp_get_theme      = wp_get_themes();
		$this->dfwp_firewall_rules = $this->filter_rules_by_hook_and_conditions( $firewall_rules, $hook_type );
		if ( empty( $this->dfwp_firewall_rules ) ) {
			return false;
		}
		$dfwp_sanitize_rules = false;
		if ( $this->is_sanitize_enabled() ) {
			$dfwp_sanitize_rules = $this->filter_rules_by_full_sanitize( $this->dfwp_firewall_rules );
		}

		$dfwp_nonce_rules = false;
		$dfwp_nonce_rules = $this->filter_rules_by_nonce( $this->dfwp_firewall_rules );

		do_action( 'defend_wp_firewall_rules_after_pre_condition_filter', $this->dfwp_firewall_rules );

		$this->pre_firewall_check( $dfwp_formated_request, $dfwp_nonce_rules, $dfwp_sanitize_rules );

		foreach ( $dfwp_formated_request as $request_type => $request_method ) {

			if ( empty( $request_method ) ) {
				continue;
			}

			foreach ( $request_method as $request_key => $request_value ) {
				if ( empty( $request_value ) ) {
					continue;
				}
				if ( is_string( $request_value ) || is_array( $request_value ) ) {

					$filtered_value = $this->process_data_run_rule( $request_value, $request_key, $request_type );
					if ( $request_type === 'post' ) {
						if ( $filtered_value['request_key'] != $request_key ) {
							unset( $_POST[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							unset( $_REQUEST[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							if ( isset( $filtered_value['request_key'] ) ) {
								$_POST[ $filtered_value['request_key'] ]    = $filtered_value['request_value'];
								$_REQUEST[ $filtered_value['request_key'] ] = $filtered_value['request_value'];
							}
						} else {
							$_POST[ $request_key ]    = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
							$_REQUEST[ $request_key ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it

						}
					} elseif ( $request_type === 'get' ) {
						if ( $filtered_value['request_key'] != $request_key ) {
							unset( $_GET[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							unset( $_REQUEST[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							if ( isset( $filtered_value['request_key'] ) ) {
								$_GET[ $filtered_value['request_key'] ]     = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
								$_REQUEST[ $filtered_value['request_key'] ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
							}
						} else {
							$_GET[ $request_key ]     = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
							$_REQUEST[ $request_key ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
						}
					} elseif ( $request_type === 'header' ) {
						if ( $filtered_value['request_key'] != $request_key ) {
							unset( $_SERVER[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							if ( isset( $filtered_value['request_key'] ) ) {
								$_SERVER[ $filtered_value['request_key'] ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
							}
						} else {
							$_SERVER[ $request_key ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
						}
					} elseif ( $request_type === 'cookie' ) {
						if ( $filtered_value['request_key'] != $request_key ) {
							unset( $_COOKIE[ $request_key ] ); // We need unsanitized value to find out if there is any vulnerability and prevent it
							if ( isset( $filtered_value['request_key'] ) ) {
								$_COOKIE[ $filtered_value['request_key'] ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
							}
						} else {
							$_COOKIE[ $request_key ] = $filtered_value['request_value']; // We need unsanitized value to find out if there is any vulnerability and prevent it
						}
					}
				}
			}
		}
		$this->post_firewall_check();

		do_action( 'defend_wp_firewall_after_firewall_run', $this );
	}

	private function pre_firewall_check( $dfwp_formated_request, $dfwp_nonce_rules, $dfwp_sanitize_rules ) {
		foreach ( $dfwp_formated_request as $request_type => $request_method ) {

			if ( empty( $request_method ) ) {
				continue;
			}
			if ( ! empty( $dfwp_nonce_rules ) && in_array( $request_type, $this->nonce_supported_request_methods, true ) ) {
				$this->nonce_checker( $dfwp_nonce_rules, $request_method, $request_type );
			}
			if ( ! empty( $dfwp_sanitize_rules ) && in_array( $request_type, $this->sanitize_supported_request_methods, true ) ) {
				$this->run_full_sanitize_checker( $dfwp_sanitize_rules, $request_method, $request_type );
			}
		}

		$results = $this->run_unfinished_matched_rules( $this->sanitize_matched_rules );
		if ( ! empty( $results ) ) {
			foreach ( $results as $firewall_id ) {
				$this->process_matched_sanitize_rule( $firewall_id );
			}
		}
	}

	private function post_firewall_check() {
		$results = $this->run_unfinished_matched_rules( $this->request_matched_rules );
		if ( ! empty( $results ) ) {
			foreach ( $results as $firewall_id ) {
				if ( empty( $this->request_matched_rules[ $firewall_id ] ) && empty( $this->request_matched_rules[ $firewall_id ]['result'] ) ) {
					continue;
				}

				$matched_rule_data = $this->request_matched_rules[ $firewall_id ];
				$this->block_request_matched_rules( $matched_rule_data, $firewall_id );
			}
		}
	}

	public function process_data_run_rule( $request_value, $request_key, $request_type ) {
		if ( ! is_null( $request_value ) ) {
			$result = $this->run_all_rules( $request_value, $request_key, $request_type );
			if ( $result !== false ) {
				$is_sanitized       = false;
				$copy_request_value = $request_value;
				foreach ( $result as $firewall_id ) {
					if ( empty( $this->request_matched_rules[ $firewall_id ] ) && empty( $this->request_matched_rules[ $firewall_id ]['result'] ) ) {
						continue;
					}
					$matched_rule_data = $this->request_matched_rules[ $firewall_id ];
					$this->block_request_matched_rules( $matched_rule_data, $firewall_id );

					$formated_array                       = array();
					$formated_array['request_value']      = $request_value;
					$formated_array['request_key']        = $request_key;
					$formated_array['matched_rule_data']  = $matched_rule_data;
					$formated_array['request_type']       = $request_type;
					$formated_array['copy_request_value'] = $copy_request_value;
					$formated_array['is_sanitized']       = $is_sanitized;

					$filtered_value = apply_filters( 'defend_wp_firewall_matched_rule', $formated_array );

					if ( ! empty( $filtered_value ) && isset( $filtered_value['request_value'] ) ) {
						$request_value = $filtered_value['request_value'];
					}

					if ( ! empty( $filtered_value ) && isset( $filtered_value['is_sanitized'] ) ) {
						$is_sanitized = $filtered_value['is_sanitized'];
					}
					if ( ! empty( $filtered_value ) && isset( $filtered_value['request_key'] ) ) {
						$request_key = $filtered_value['request_key'];
					}
				}
			}
			$formated_array                  = array();
			$formated_array['request_value'] = $request_value;
			$formated_array['request_key']   = $request_key;
			$formated_array['request_type']  = $request_type;
			$filtered_value                  = apply_filters( 'defend_wp_firewall_request_after_run_all_rules', $formated_array );

			if ( ! empty( $filtered_value ) && isset( $filtered_value['request_value'] ) ) {
				$request_value = $filtered_value['request_value'];
			}

			if ( ! empty( $filtered_value ) && isset( $filtered_value['request_key'] ) ) {
				$request_key = $filtered_value['request_key'];
			}
		}
		if ( is_array( $request_value ) ) {

			$filtered_value = $this->process_data_run_rule( '_dfwp_dummy_value', $request_key, $request_type );

			if ( ! empty( $filtered_value ) && isset( $filtered_value['request_key'] ) ) {
				$request_key = $filtered_value['request_key'];
			}
			foreach ( $request_value as $a_key => $loop_value ) {
				$filtered_value = $this->process_data_run_rule( $loop_value, $a_key, $request_type );
				if ( $filtered_value['request_key'] != $a_key ) {
					unset( $request_value[ $a_key ] );
					if ( isset( $filtered_value['request_key'] ) ) {
						$request_value[ $filtered_value['request_key'] ] = $filtered_value['request_value'];
					}
				} else {
					$request_value[ $a_key ] = $filtered_value['request_value'];
				}
			}
		}

		if ( is_string( $request_value ) && in_array( $request_type, $this->json_base64_supported_request_methods, true ) ) {
			$json_value = defend_wp_firewall_is_valid_json( $request_value );
			if ( $json_value !== false && isset( $json_value['data'] ) ) {
				$result = $this->process_data_run_rule( $json_value['data'], $request_key, $request_type );

				if ( ! empty( $result['request_value'] ) ) {
					$request_value = wp_json_encode( $result['request_value'] );
					if ( $json_value['is_slashed'] ) {
						$request_value = addslashes( $request_value );
					}
				}
			} else {
				$base64_value = defend_wp_firewall_is_valid_base64( $request_value );
				if ( $base64_value !== false && ! empty( $base64_value ) ) {
					$result        = $this->process_data_run_rule( $base64_value, $request_key, $request_type );
					$request_value = base64_encode( $result['request_value'] );
				}
			}
		}

		return array(
			'request_value' => $request_value,
			'request_key'   => $request_key,
		);
	}

	public function run_all_rules( $data, $data_key, $request_type ) {
		if ( empty( $this->dfwp_firewall_rules ) ) {
			return false;
		}
		if ( empty( $data ) ) {
			return false;
		}

		return $this->find_matched_rule( $this->dfwp_firewall_rules, $data, $data_key, $request_type, $this->request_matched_rules );
	}

	public function find_matched_rule( $dfwp_firewall_rules, $data, $data_key, $request_type, &$matched_rules, $immediate_return = false ) {
		$matched_firewall_id = array();
		foreach ( $dfwp_firewall_rules as $f_key => $rule_item ) {
			$rules = $rule_item['rule'];
			if ( empty( $rule_item['options'] ) && empty( $rule_item['options'][ $request_type ] ) ) {
				continue;
			}

			$rule_options = $rule_item['options'];
			if ( empty( $rule_options[ $request_type ] ) ) {
				continue;
			}
			if ( ! empty( $this->do_full_sanitize ) && ! empty( $this->do_full_sanitize[ $request_type ] ) ) {
				if ( ! empty( $rule_options['do_sanitize'] ) ) {
					continue;
				}
			}
			$rule_request_type = $rule_options[ $request_type ];
			foreach ( $rule_request_type as $request_rule_key => $request_rule_value ) {
				if ( is_array( $request_rule_value ) ) {
					foreach ( $request_rule_value as $rule_type => $rule_ref ) {
						if ( $rule_ref === 'no' || ! isset( $rules[ $rule_ref ] ) ) {
							continue;
						}
						$condition = $rules[ $rule_ref ];
						if ( empty( $condition ) ) {
							continue;
						}
						if ( $rule_type === 'key' && ! in_array( $request_type, $this->skip_request_methods_for_key_match, true ) ) {
							$result = $this->check_rule( $data_key, $condition );
							if ( $result !== false ) {
								$firewall_condition = $this->build_and_check_matched_rule_condition( $rule_item, $request_type, $request_rule_key, $result, $data, $data_key, $matched_rules );
								if ( $firewall_condition !== false ) {
									if ( $immediate_return ) {
										return $firewall_condition;
									}
									$matched_firewall_id[] = $firewall_condition;
									if ( $rule_options['block'] ) {
										return $matched_firewall_id;
									}
								}
							}
						}
						if ( $rule_type === 'value' && ! empty( $data ) ) {
							$result = $this->check_rule( $data, $condition );
							if ( $result !== false ) {
								$firewall_condition = $this->build_and_check_matched_rule_condition( $rule_item, $request_type, $request_rule_key, $result, $data, $data_key, $matched_rules );
								if ( $firewall_condition !== false ) {
									if ( $immediate_return ) {
										return $firewall_condition;
									}
									$matched_firewall_id[] = $firewall_condition;
									if ( $rule_options['block'] ) {
										return $matched_firewall_id;
									}
								}
							}
						}
						if ( $rule_type === 'full' && ! empty( $data ) && ! in_array( $request_type, $this->skip_request_methods_for_full_match, true ) ) {
							if ( $this->check_rule( $data_key, $condition, 'key' ) ) {
								$result = $this->check_rule( $data, $condition );
								if ( $result !== false ) {
									$firewall_condition = $this->build_and_check_matched_rule_condition( $rule_item, $request_type, $request_rule_key, $result, $data, $data_key, $matched_rules );
									if ( $firewall_condition !== false ) {
										if ( $immediate_return ) {
											return $firewall_condition;
										}
										$matched_firewall_id[] = $firewall_condition;
										if ( $rule_options['block'] ) {
											return $matched_firewall_id;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if ( ! empty( $matched_firewall_id ) ) {
			return $matched_firewall_id;
		}
		return false;
	}

	public function check_rule( $data, $condition, $rule_or_key = 'rule' ) {
		if ( empty( $condition[ $rule_or_key ] ) ) {
			return false;
		}

		$rule = base64_decode( $condition[ $rule_or_key ] );
		if ( empty( $rule ) ) {
			return false;
		}
		$type = ! empty( $condition['type'] ) ? $condition['type'] : '';

		$return = true;

		if ( ! empty( $condition['return'] ) ) {
			if ( isset( $condition['return'][ $rule_or_key ] ) ) {
				$return = $condition['return'][ $rule_or_key ];
			}
		}
		$wp_unslash = false;
		if ( ! empty( $condition['wp_unslash'] ) ) {
			if ( isset( $condition['wp_unslash'][ $rule_or_key ] ) ) {
				$wp_unslash = $condition['wp_unslash'][ $rule_or_key ];
			}
		}
		if ( $wp_unslash ) {
			$data = wp_unslash( $data );
		}

		$return_value = false;
		switch ( $type ) {
			case 'stripos':
				if ( stripos( urldecode( $data ), $rule ) !== false ) {
					$return_value = true;
				}
				break;
			default:
				try {
					if ( preg_match( $rule, urldecode( $data ) ) == $return ) {
						$return_value = true;
					}
				} catch ( \Throwable $th ) {
					return false;
				}
		}
		if ( $return_value ) {
			$url = ! empty( $condition['url'] ) ? $condition['url'] : '';
			if ( ! empty( $url ) ) {
				$request_url = defend_wp_firewall_get_request_uri();
				if ( preg_match( $url, urldecode( $request_url ) ) == false ) {
					$return_value = false;
				}
			}

			$run_functions = '';

			if ( ! empty( $condition['functions'] ) ) {
				if ( isset( $condition['functions'][ $rule_or_key ] ) ) {
					$run_functions = $condition['functions'][ $rule_or_key ];
				}
			}
			if ( ! empty( $run_functions ) ) {
				foreach ( $run_functions as $func_args ) {
					if ( in_array( $func_args['name'], $this->rule_register_functions, true ) && function_exists( $func_args['name'] ) ) {
						$func_return = call_user_func( $func_args['name'], defend_wp_firewall_detect_and_sanitize_sql_injection( sanitize_text_field( urldecode( $data ) ) ) );
						if ( $func_args['name'] === 'get_user_by' && is_object( $func_return ) ) {
							if ( $func_return->$func_args['result_key'] == $func_args['result_value'] ) {
								$return_value = false;
							}
						} elseif ( ! empty( $func_args['match_data'] ) && $data != $func_return ) {
							$return_value = false;
						} elseif ( ! empty( $func_args['result_key'] ) ) {
							if ( isset( $func_return[ $func_args['result_key'] ] ) && $func_return[ $func_args['result_key'] ] && ! empty( $func_args['result'] ) && $func_return[ $func_args['result_key'] ] === $func_args['result'] ) {
								$return_value = false;
							} elseif ( isset( $func_return[ $func_args['result_key'] ] ) && $func_return[ $func_args['result_key'] ] ) {
								$return_value = false;
							}
						} elseif ( isset( $func_args['result'] ) && $func_args['result'] != $func_return ) {
							$return_value = false;
						}

						if ( ! empty( $condition['return'] ) ) {
							if ( isset( $condition['return']['function'] ) ) {
								$return = $condition['return']['function'];
								if ( $return === $return_value ) {
									$return_value = true;
								} else {
									$return_value = false;
								}
							}
						}
					}
				}
			}
		}

		return $return_value;
	}

	private function build_and_check_matched_rule_condition( $rule_item, $request_type, $rule_ref, $result, $data, $data_key, &$matched_rules ) {
		$firewall_id = $rule_item['id'];
		if ( empty( $matched_rules[ $firewall_id ] ) ) {
			$matched_rules[ $firewall_id ] = array();
		}

		if ( empty( $matched_rules[ $firewall_id ]['rule_item'] ) ) {
			$matched_rules[ $firewall_id ]['matched_rule'] = $rule_item;
		}

		$rule_options = $rule_item['options'];
		if ( empty( $rule_options['rule_condition'] ) ) {
			return false;
		}
		if ( empty( $matched_rules[ $firewall_id ]['original_condition'] ) ) {
			$matched_rules[ $firewall_id ]['original_condition'] = $rule_options['rule_condition'];
		}
		if ( empty( $matched_rules[ $firewall_id ]['condition'] ) ) {
			$condition = $rule_options['rule_condition'];
		} else {
			$condition = $matched_rules[ $firewall_id ]['condition'];
		}
		$matched_rules[ $firewall_id ]['condition'] = $this->map_condition( $result, $condition, $request_type, $rule_ref );
		if ( empty( $matched_rules[ $firewall_id ][ $request_type ] ) ) {
			$matched_rules[ $firewall_id ][ $request_type ] = array();
		}

		if ( empty( $matched_rules[ $firewall_id ][ $request_type ][ $request_type . $rule_ref ] ) ) {
			$matched_rules[ $firewall_id ][ $request_type ][ $request_type . $rule_ref ] = array();
		}

		$matched_rules[ $firewall_id ][ $request_type ][ $request_type . $rule_ref ] = array(
			'data'     => $data,
			'data_key' => $data_key,
			'result'   => $result,
		);

		if ( $this->is_condition_filled( $matched_rules[ $firewall_id ]['condition'] ) ) {
			$is_condition_satisfied = $this->check_matched_condition( $matched_rules[ $firewall_id ]['condition'] );
			if ( $is_condition_satisfied ) {
				$matched_rules[ $firewall_id ]['result'] = $is_condition_satisfied;
				return $firewall_id;
			}
		}

		return false;
	}

	private function map_condition( $result, $condition, $request_type, $rule_ref ) {
		$maped_string = $request_type . $rule_ref;
		return str_replace( $maped_string, $result, $condition );
	}

	private function is_condition_filled( $condition ) {
		foreach ( $this->available_request_methods as $value ) {
			if ( strpos( $condition, $value ) !== false ) {
				return false;
			}
		}
		return true;
	}

	private function check_matched_condition( $condition ) {

		try {
			$expression_language = new ExpressionLanguage();
			if ( $expression_language->evaluate( $condition ) ) {
				return true;
			}
		} catch ( \Throwable $th ) {
			return false;
		}

		return false;
	}

	private function run_unfinished_matched_rules( &$matched_rules ) {
		if ( empty( $matched_rules ) ) {
			return false;
		}

		$unfinished_rule_ids = array();
		foreach ( $matched_rules as $firewall_id => $rule ) {
			if ( ! empty( $rule['result'] ) ) {
				continue;
			}

			$condition = $rule['condition'];

			$matched_rules[ $firewall_id ]['unmatched_condition'] = $condition;
			foreach ( $this->available_request_methods as $request_type ) {
				$condition = preg_replace( '/' . $request_type . '\d+/', 0, $condition );
			}

			$matched_rules[ $firewall_id ]['condition'] = $condition;
			if ( $this->is_condition_filled( $condition ) ) {
				$is_condition_satisfied = $this->check_matched_condition( $condition );
				if ( $is_condition_satisfied ) {
					$matched_rules[ $firewall_id ]['result'] = $is_condition_satisfied;
					$unfinished_rule_ids []                  = $firewall_id;
				}
			}
		}
		return $unfinished_rule_ids;
	}

	private function block_request_matched_rules( $matched_rule_data, $firewall_id ) {
		do_action( 'defend_wp_firewall_matched_rule_action', $matched_rule_data['matched_rule'], $firewall_id, $matched_rule_data );
		$matched_rule = $matched_rule_data['matched_rule'];
		unset( $matched_rule_data['matched_rule'] );
		$title = $this->format_firewall_title( $matched_rule['options'] );
		defend_wp_firewall_die(
			array(
				'type'        => 'firewall',
				'firewall_id' => $firewall_id,
				'title'       => $title . ' (ID #' . ( $firewall_id ) . ')',
				'message'     => 'Access denied by firewall.',
				'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
			),
			$matched_rule['options']['log'],
			$matched_rule['options']['block'],
		);
	}

	private function process_matched_sanitize_rule( $firewall_id ) {
		if ( $firewall_id !== false ) {
			if ( empty( $this->sanitize_matched_rules[ $firewall_id ] ) && empty( $this->request_matched_rules[ $firewall_id ]['result'] ) ) {
				return false;
			}

			$matched_rule_data = $this->sanitize_matched_rules[ $firewall_id ];
			$matched_rule      = $matched_rule_data['matched_rule'];
			$condition         = $matched_rule_data['original_condition'];
			foreach ( $this->sanitize_supported_request_methods as $request_type ) {
				if ( strpos( $condition, $request_type ) !== false && empty( $this->do_full_sanitize[ $request_type ] ) ) {
					$matched_rules = array(
						'matched_rule'      => $matched_rule,
						'result'            => true,
						'request_type'      => $request_type,
						'matched_rule_data' => $matched_rule_data,
					);

					$this->do_full_sanitize[ $request_type ] = $matched_rules;
				}
			}
			return true;
		}
	}

	public function filter_rules_by_hook_and_conditions( $data, $needle ) {
		$return_array = array();
		foreach ( $data as $a_key => $value ) {
			if ( ! empty( $data[ $a_key ]['options'] ) && ! empty( $data[ $a_key ]['options']['hook'] ) ) {
				if ( ( $data[ $a_key ]['options']['hook'] == $needle ) ) {
					if ( $this->check_common_rule_conditions( $data[ $a_key ] ) === false ) {
						$return_array[] = $value;
					}
				}
			}
			foreach ( $value['options'] as $options_key => $options_value ) {
				if ( in_array( $options_key, $this->callable_action_hooks, true ) && ! empty( $options_value ) ) {
					do_action( 'defend_wp_firewall_' . $options_key, $value );
				}
			}
		}
		return $return_array;
	}

	public function filter_rules_by_nonce( $data ) {
		$return_array = array();
		foreach ( $data as $a_key => $value ) {
			if ( ! empty( $data[ $a_key ]['options']['nonce_check'] ) ) {
				if ( isset( $data[ $a_key ]['options']['nonce_check']['is_dfwp'] ) && $data[ $a_key ]['options']['nonce_check']['is_dfwp'] && $this->is_nonce_enabled() === true ) {
					$return_array[] = $value;
				}
				if ( ! empty( $data[ $a_key ]['options']['nonce_check']['nonce'] ) ) {
					$return_array[] = $value;
				}
			}
		}
		return $return_array;
	}

	public function plugin_checker( $plugin ) {
		if ( empty( $plugin['fixed_in'] ) ) {
			return true;
		}
		if ( empty( $this->dfwp_get_plugin ) ) {
			return false;
		}

		if ( array_key_exists( $plugin['slug'], $this->dfwp_get_plugin ) ) {
			if ( ! empty( $this->dfwp_get_plugin[ $plugin['slug'] ] ) &&
			! empty( $this->dfwp_get_plugin[ $plugin['slug'] ]['Version'] ) &&
			version_compare( $this->dfwp_get_plugin[ $plugin['slug'] ]['Version'], $plugin['fixed_in'], '<' ) ) {
				return true;
			}
		}

		return false;
	}

	public function theme_checker( $theme ) {
		if ( empty( $theme['fixed_in'] ) ) {
			return true;
		}
		if ( empty( $this->dfwp_get_theme ) ) {
			return false;
		}

		if ( array_key_exists( $theme['slug'], $this->dfwp_get_theme ) ) {
			if ( ! empty( $this->dfwp_get_theme[ $theme['slug'] ] ) &&
			! empty( $this->dfwp_get_theme[ $theme['slug'] ]->get( 'Version' ) ) &&
			version_compare( $this->dfwp_get_theme[ $theme['slug'] ]->get( 'Version' ), $theme['fixed_in'], '<' ) ) {
				return true;
			}
		}

		return false;
	}

	public function core_checker( $core ) {
		if ( empty( $core['fixed_in'] ) ) {
			return true;
		}
		global $wp_version;
		if ( empty( $wp_version ) ) {
			return false;
		}
		if ( ! empty( $core['version'] ) &&
			version_compare( $wp_version, $core['fixed_in'], '<' ) ) {
				return true;
		}
		return false;
	}

	public function nonce_checker( $dfwp_firewall_rules, $request, $request_type ) {
		$is_dfwp_checked = false;
		foreach ( $request as $data_key => $data ) {
			if ( is_string( $data ) ) {
				$firewall_id = $this->find_matched_rule( $dfwp_firewall_rules, $data, $data_key, $request_type, $this->nonce_matched_rules, true );
				if ( $firewall_id !== false ) {
					if ( empty( $this->nonce_matched_rules[ $firewall_id ] ) && empty( $this->nonce_matched_rules[ $firewall_id ]['result'] ) ) {
						continue;
					}
					$matched_rule_data = $this->nonce_matched_rules[ $firewall_id ];
					$matched_rule      = $matched_rule_data['matched_rule'];
					$nonce_check       = $matched_rule['options']['nonce_check'];
					if ( ! empty( $nonce_check ) ) {
						if ( $is_dfwp_checked === false && isset( $nonce_check['is_dfwp'] ) && $nonce_check['is_dfwp'] ) {
							$is_valid = check_ajax_referer( 'defend-wp-firewall-nonce', 'defend_wp_firewall_nonce', false );
							if ( $is_valid === false ) {
								defend_wp_firewall_die(
									array(
										'type'    => 'nonce_checker',
										'title'   => 'AJAX request restriction',
										'message' => 'Missing DefendWP nonce',
									),
									true,
									true
								);
								wp_die( -1, 403 );
							}
							$is_dfwp_checked = true;
						} else {
							$is_valid = check_ajax_referer( $nonce_check['nonce'], $nonce_check['key'], false );
							if ( $is_valid === false ) {
								defend_wp_firewall_die(
									array(
										'type'    => 'nonce_checker',
										'title'   => 'AJAX request restriction',
										'message' => 'Missing nonce',
									),
									true,
									true
								);
								wp_die( -1, 403 );
							}
						}
					}
				}
			}
		}
	}

	public function check_common_rule_conditions( $rule_item ) {
		if ( empty( $rule_item['options'] ) ) {
			return false;
		}
		if ( ! empty( $rule_item['options']['is_logged_in'] ) ) {
			if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() === false ) {
				return true;
			}
		}

		if ( ! empty( $rule_item['options']['allowed_cap'] ) && $this->current_user_can( $rule_item['options']['allowed_cap'] ) ) {
			return true;
		}
		if ( ! empty( $rule_item['options']['allowed_role'] ) && $this->allowed_role_block( $rule_item['options']['allowed_role'] ) ) {
			return true;
		}
		if ( ! empty( $rule_item['type'] ) ) {
			if ( $rule_item['type'] === 'plugin' && $this->plugin_checker( $rule_item ) === false ) {
				return true;
			} elseif ( $rule_item['type'] === 'theme' && $this->theme_checker( $rule_item ) === false ) {
				return true;
			} elseif ( $rule_item['type'] === 'core' && $this->core_checker( $rule_item ) === false ) {
				return true;
			}
		}

		if ( ! empty( $rule_item['options']['run'] ) && ! empty( $rule_item['options']['run']['skip_firewall'] ) ) {
			return true;
		}

		if ( ! empty( $rule_item['options']['remove_action'] ) && ! empty( $rule_item['options']['remove_action']['skip_firewall'] ) ) {
			return true;
		}

		if ( ! empty( $rule_item['options']['remove_filter'] ) && ! empty( $rule_item['options']['remove_filter']['skip_firewall'] ) ) {
			return true;
		}

		return false;
	}

	public function current_user_can( $caps ) {
		foreach ( $caps as $value ) {
			if ( current_user_can( $value ) ) {
				return true;
			}
		}
		return false;
	}

	public function allowed_role_block( $allowed_role ) {
		foreach ( $allowed_role as $value ) {
			if ( $this->role_block( $value ) ) {
				return true;
			}
		}
		return false;
	}

	public function format_firewall_title( $options ) {
		if ( $options['block'] ) {
			return 'Firewall block';
		} elseif ( $options['do_sanitize'] ) {
			return 'Firewall sanitize';
		} elseif ( $options['do_full_sanitize'] ) {
			return 'Firewall full sanitize';
		} elseif ( $options['shortcode_rules'] ) {
			return 'Firewall shortcode';
		} elseif ( ! empty( $options['wp_logout'] ) ) {
			return 'Firewall user logout';
		} elseif ( ! empty( $options['wp_user_restrictions'] ) && ! empty( $options['wp_user_restrictions']['wp_delete_user'] ) ) {
			return 'Firewall WP User Delete';
		} elseif ( ! empty( $options['wp_post_restrictions'] ) && ! empty( $options['wp_post_restrictions']['wp_delete_post'] ) ) {
			return 'Firewall WP Delete Post';
		} elseif ( $options['log'] ) {
			return 'Firewall Log';
		}

		return '';
	}


	public function fetch_request() {

		$requests           = array();
		$requests['post']   = array();
		$requests['get']    = array();
		$requests['header'] = array();
		$requests['cookie'] = array();
		$requests['raw']    = array();
		$requests['file']   = array();

		if ( ! empty( $_SERVER ) ) {
			$requests['header'] = $_SERVER; // We need unsanitized value to find out if there is any vulnerability and prevent it
		}

		if ( ! empty( $_COOKIE ) ) {
			$requests['cookie'] = $_COOKIE; // We need unsanitized value to find out if there is any vulnerability and prevent it
		}
		// We need to process the data without verifying nonce
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$requests['post'] = $_POST; // We need unsanitized value to find out if there is any vulnerability and prevent it
		}

		if ( ! empty( $_GET ) ) {
			$requests['get'] = $_GET; // We need unsanitized value to find out if there is any vulnerability and prevent it
		}
		$raw_post = file_get_contents( 'php://input' );
		if ( ! empty( $raw_post ) ) {
			$requests['raw'][] = $raw_post;
		}

		$requests['url'][] = defend_wp_firewall_get_request_uri();

		$files = $this->filter_files_variable();
		if ( ! empty( $files ) ) {
			$requests['file'] = $files;
		}
		return $requests;
	}


	public function check_non_admin_rules() {

		if ( is_user_logged_in() && ! defend_wp_firewall_is_admin() ) {
			$request_uri = defend_wp_firewall_get_request_uri();
			if ( strpos( $request_uri, '/wp-admin/' ) !== false &&
				! ( strpos( $request_uri, 'admin-ajax.php' )
					|| strpos( $request_uri, 'profile.php' )
					)
				) {
				defend_wp_firewall_die(
					array(
						'type'    => 'user_role',
						'title'   => 'Page access restriction',
						'message' => 'You are not allowed to access this page.',
					),
					false
				);
			}
		}
	}

	public function role_block( $role ) {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			return false;
		}
		$user = wp_get_current_user();
		if ( ! isset( $user->roles ) || count( (array) $user->roles ) == 0 ) {
			return false;
		}
		$role_count = array_intersect( $user->roles, array( $role ) );
		return count( $role_count ) != 0;
	}

	private function filter_files_variable() {
		// We need to process the data without verifiying nonce
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES ) || ! is_array( $_FILES ) ) {
			return '';
		}
		$converted_files = array();
		// We need to process the data without verifiying nonce
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( $_FILES as $file_key => $file ) {
			$temp = array();
			// We need unsanitized value to find out if there is any vulnerability and prevent it
			if ( ! empty( $file['name'] ) && is_array( $file['name'] ) ) {
				foreach ( $file['name'] as $key => $value ) {
					if ( empty( $temp[ $key ] ) ) {
						$temp[ $key ] = array();
					}
					$temp[ $key ]['name'] = $value;
					if ( ! empty( $file['type'][ $key ] ) ) {
						$temp[ $key ]['type'] = $file['type'][ $key ];// We need unsanitized value to find out if there is any vulnerability and prevent it
					}
					if ( ! empty( $file['size'][ $key ] ) ) {
						$temp[ $key ]['size'] = $file['size'][ $key ]; // We need unsanitized value to find out if there is any vulnerability and prevent it
					}
					if ( ! empty( $file['tmp_name'][ $key ] ) ) {
						$temp[ $key ]['tmp_name'] = $file['tmp_name'][ $key ]; // We need unsanitized value to find out if there is any vulnerability and prevent it
					}
					$temp [ $key ] ['file_key'] = $file_key; // We need unsanitized value to find out if there is any vulnerability and prevent it
				}
			} elseif ( is_string( $file['name'] ) ) {
				$temp_string             = array();
				$temp_string['name']     = $file['name']; // We need unsanitized value to find out if there is any vulnerability and prevent it
				$temp_string['type']     = $file['type']; // We need unsanitized value to find out if there is any vulnerability and prevent it
				$temp_string['tmp_name'] = $file['tmp_name']; // We need unsanitized value to find out if there is any vulnerability and prevent it
				$temp_string['size']     = $file['size']; // We need unsanitized value to find out if there is any vulnerability and prevent it
				$temp_string['file_key'] = $file_key; // We need unsanitized value to find out if there is any vulnerability and prevent it
				$temp[]                  = $temp_string;
			}
			if ( ! empty( $temp ) ) {

				$converted_files = $temp;
			}
		}
		return $converted_files;
	}

	public function process_incoming_new_rules() {

		if ( ! empty( $_GET ) && ! empty( $_GET['dfwp_service_action'] ) && $_GET['dfwp_service_action'] === 'incoming_new_rules' ) {
			global $defend_wp_firewall_all_configs;
			if ( empty( $defend_wp_firewall_all_configs['dfwp_activation_key'] ) || empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'site_not_performed_activation',
							'error_message' => 'Site did not perform add site',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
			$raw_post = file_get_contents( 'php://input' );
			if ( ! empty( $raw_post ) ) {
				$decode_request = base64_decode( $raw_post );
				$request        = ! empty( $decode_request ) ? json_decode( $decode_request, true ) : array();
				$verify         = $this->verify_request( $request );
				if ( $verify ) {
					$rules          = ( ! empty( $request ) && ! empty( $request['rules'] ) ) ? $request['rules'] : array();
					$firewall_rules = $this->get_firewall_rules();
					if ( empty( $firewall_rules ) ) {
						$firewall_rules = array();
					}
					if ( ! empty( $firewall_rules ) && ! empty( $rules ) ) {
						foreach ( $firewall_rules as $old_key => $old_value ) {
							foreach ( $rules as $new_key => $new_value ) {
								if ( $new_value['id'] === $old_value['id'] ) {
									$firewall_rules[ $old_key ] = $new_value;
									unset( $rules[ $new_key ] );
								}
							}
						}
					}
					if ( ! empty( $rules ) ) {
						$firewall_rules = array_merge( $firewall_rules, $rules );
					}
						$result = $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', sanitize_text_field( wp_json_encode( $firewall_rules ) ) );
						$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
					if ( $result !== false ) {
						$this->defend_wp_firewall_options->set_option( 'dfwp_cron_id', sanitize_text_field( $request['cron_id'] ) );
						die( '<DFWP_RESPONSE>' . wp_json_encode( array( 'success' => 1 ) ) . '</DFWP_RESPONSE>' );
					}
				}
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'empty_request',
							'error_message' => 'Empty request',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
	}

	public function process_verify_add_site() {
		if ( ! empty( $_GET ) && ! empty( $_GET['dfwp_service_action'] ) && $_GET['dfwp_service_action'] === 'verify_add_site' ) {
			global $defend_wp_firewall_all_configs;
			if ( ! empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'site_already_connected',
							'error_message' => 'Site already connected',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
			if ( empty( $defend_wp_firewall_all_configs['dfwp_activation_key'] ) ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'site_not_performed_activation',
							'error_message' => 'Site did not perform add site',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
			$raw_post = file_get_contents( 'php://input' );
			if ( ! empty( $raw_post ) ) {
				$decode_request = base64_decode( $raw_post );
				$request        = ! empty( $decode_request ) ? json_decode( $decode_request, true ) : array();
				if ( ! empty( $request ) ) {
					$activation_key = $defend_wp_firewall_all_configs['dfwp_activation_key'];
					if ( ! empty( $request['activation_key'] ) && $activation_key === $request['activation_key'] ) {
						$verify = $this->verify_add_site_request( $request );
						if ( $verify ) {
							$this->defend_wp_firewall_options->set_option( 'dfwp_pub_key', sanitize_text_field( $request['pub_key'] ) );
							$service_object    = new Defend_WP_Firewall_Service();
							$params            = $service_object->collect_ptc_details();
							$urls              = $service_object->collect_urls();
							$params            = array_merge( $params, $urls );
							$params['success'] = 1;
							die(
								'<DFWP_RESPONSE>' . wp_json_encode(
									$params
								) . '</DFWP_RESPONSE>'
							);
						} else {
							die(
								'<DFWP_RESPONSE>' . wp_json_encode(
									array(
										'success'       => 0,
										'error_code'    => 'invalid_signature',
										'error_message' => 'Invalid signature',
									)
								) . '</DFWP_RESPONSE>'
							);
						}
					} else {
						die(
							'<DFWP_RESPONSE>' . wp_json_encode(
								array(
									'success'       => 0,
									'error_code'    => 'invalid_activation_key',
									'error_message' => 'Invalid activation key',
								)
							) . '</DFWP_RESPONSE>'
						);
					}
				} else {
					die(
						'<DFWP_RESPONSE>' . wp_json_encode(
							array(
								'success'       => 0,
								'error_code'    => 'unable_decode_request',
								'error_message' => 'Unable to decode',
							)
						) . '</DFWP_RESPONSE>'
					);
				}
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'empty_request',
							'error_message' => 'Empty request',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
	}

	public function process_verify_sync_site() {
		if ( ! empty( $_GET ) && ! empty( $_GET['dfwp_service_action'] ) && $_GET['dfwp_service_action'] === 'verify_sync_site' ) {
			global $defend_wp_firewall_all_configs;
			if ( empty( $defend_wp_firewall_all_configs['dfwp_activation_key'] ) || empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'site_not_performed_activation',
							'error_message' => 'Site did not perform add site',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
			$raw_post = file_get_contents( 'php://input' );
			if ( ! empty( $raw_post ) ) {
				$decode_request = base64_decode( $raw_post );
				$request        = ! empty( $decode_request ) ? json_decode( $decode_request, true ) : array();
				$verify         = $this->verify_sync_request( $request );
				if ( $verify ) {
					die( '<DFWP_RESPONSE>' . wp_json_encode( array( 'success' => 1 ) ) . '</DFWP_RESPONSE>' );
				}
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'empty_request',
							'error_message' => 'Empty request',
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
	}

	private function verify_add_site_request( $request ) {
		global $defend_wp_firewall_all_configs;
		$url            = site_url();
		$url            = explode( '://', $url );
		$url            = $url[1];
		$activation_key = $defend_wp_firewall_all_configs['dfwp_activation_key'];
		$signature      = base64_decode( $request['signature'] );
		$pub_key        = base64_decode( $request['pub_key'] );
		$data           = $url . $activation_key;
		$is_openssl     = defend_wp_firewall_check_openssl();
		if ( $is_openssl ) {
			$ok = openssl_verify( $data, $signature, $pub_key, OPENSSL_ALGO_SHA256 );

			if ( $ok == 1 ) {
				return true;
			} elseif ( $ok == 0 ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_signature',
							'error_message' => 'Invalid signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'signature_verify_failed',
							'error_message' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		} else {

			$hash = hash( 'sha256', $pub_key . $data );

			if ( $hash === $signature ) {
				return true;
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_sha_signature',
							'error_message' => 'Invalid sha signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
		return false;
	}

	private function verify_sync_request( $request ) {
		global $defend_wp_firewall_all_configs;
		$url            = site_url();
		$url            = explode( '://', $url );
		$url            = $url[1];
		$activation_key = $defend_wp_firewall_all_configs['dfwp_activation_key'];
		$signature      = base64_decode( $request['signature'] );
		$pub_key        = base64_decode( $defend_wp_firewall_all_configs['dfwp_pub_key'] );
		$data           = $url . $activation_key;
		$is_openssl     = defend_wp_firewall_check_openssl();
		if ( $is_openssl ) {
			$ok = openssl_verify( $data, $signature, $pub_key, OPENSSL_ALGO_SHA256 );

			if ( $ok == 1 ) {
				return true;
			} elseif ( $ok == 0 ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_signature',
							'error_message' => 'Invalid signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'signature_verify_failed',
							'error_message' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		} else {

			$hash = hash( 'sha256', $pub_key . $data );

			if ( $hash === $signature ) {
				return true;
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_sha_signature',
							'error_message' => 'Invalid sha signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
		return false;
	}

	private function verify_request( $request ) {
		global $defend_wp_firewall_all_configs;

		$cron_id = $request['cron_id'];
		if ( ! empty( $defend_wp_firewall_all_configs['dfwp_cron_id'] ) && $cron_id === $defend_wp_firewall_all_configs['dfwp_cron_id'] ) {
			die(
				'<DFWP_RESPONSE>' . wp_json_encode(
					array(
						'success'       => 0,
						'error_code'    => 'invalid_cron_id',
						'error_message' => 'Already performed request',
					)
				) . '</DFWP_RESPONSE>'
			);
		}
		$url            = site_url();
		$url            = explode( '://', $url );
		$url            = $url[1];
		$activation_key = $defend_wp_firewall_all_configs['dfwp_activation_key'];
		$signature      = base64_decode( $request['signature'] );
		$pub_key        = base64_decode( $defend_wp_firewall_all_configs['dfwp_pub_key'] );
		$data           = $url . $activation_key . $cron_id;

		$is_openssl = defend_wp_firewall_check_openssl();

		if ( $is_openssl ) {
			$ok = openssl_verify( $data, $signature, $pub_key, OPENSSL_ALGO_SHA256 );

			if ( $ok == 1 ) {
				return true;
			} elseif ( $ok == 0 ) {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_signature',
							'error_message' => 'Invalid signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'signature_verify_failed',
							'error_message' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		} else {

			$hash = hash( 'sha256', $pub_key . $data );

			if ( $hash === $signature ) {
				return true;
			} else {
				die(
					'<DFWP_RESPONSE>' . wp_json_encode(
						array(
							'success'       => 0,
							'error_code'    => 'invalid_sha_signature',
							'error_message' => 'Invalid sha signature',
							'openssl_error' => openssl_error_string(),
						)
					) . '</DFWP_RESPONSE>'
				);
			}
		}
		return false;
	}

	public function block_xml_rpc() {
		global $defend_wp_firewall_all_configs;
		if ( ( ! empty( $defend_wp_firewall_all_configs['disable_xml_rpc_request'] ) && $defend_wp_firewall_all_configs['disable_xml_rpc_request'] == 'yes' ) ) {
			return false;
		}

		return true;
	}

	public function is_sanitize_enabled() {
		global $defend_wp_firewall_all_configs;
		if ( ! empty( $defend_wp_firewall_all_configs['enable_sanitize_request'] ) && $defend_wp_firewall_all_configs['enable_sanitize_request'] == 'yes' ) {
			return true;
		}
		return false;
	}

	public function is_nonce_enabled() {
		global $defend_wp_firewall_all_configs;
		if ( ! empty( $defend_wp_firewall_all_configs['enable_defendwp_nonce'] ) && $defend_wp_firewall_all_configs['enable_defendwp_nonce'] == 'yes' ) {
			return true;
		}
		return false;
	}

	public function do_sanitize( $request_value, $request_key, $options ) {
		if ( empty( $options['functions'] ) ) {
			return array(
				'request_value' => $request_value,
				'request_key'   => $request_key,
			);
		}
		if ( is_array( $request_value ) ) {
			foreach ( $request_value as $r_key => $value ) {
				$return_array = $this->do_sanitize( $value, $r_key, $options );
				if ( $return_array['request_key'] != $r_key ) {
					unset( $request_value[ $r_key ] );
					if ( isset( $return_array['request_key'] ) ) {
						$request_value[ $return_array['request_key'] ] = $return_array['request_value'];
					}
				} else {
					$request_value[ $r_key ] = $return_array['request_value'];
				}
			}
		} else {
			$func_names = $options['functions'];
			$funcs      = $this->get_registered_sanitize_functions();
			foreach ( $func_names as $func_name ) {
				if ( in_array( $func_name, $funcs, true ) && function_exists( $func_name ) ) {
					if ( ! empty( $options['value_sanitize'] ) ) {
						if ( ! empty( $options['value_args'] ) ) {
							$request_value = call_user_func( $func_name, $request_value, $options['value_args'] );
						} else {
							$request_value = call_user_func( $func_name, $request_value );
						}
					}
					if ( ! empty( $options['key_sanitize'] ) ) {
						if ( ! empty( $options['key_args'] ) ) {
							$request_key = call_user_func( $func_name, $request_key, $options['key_args'] );
						} else {
							$request_key = call_user_func( $func_name, $request_key );
						}
						$request_key = call_user_func( $func_name, $request_key );
					}
				}
			}
		}
		return array(
			'request_value' => $request_value,
			'request_key'   => $request_key,
		);
	}

	public function get_registered_sanitize_functions() {
		$func = array( 'sanitize_email', 'sanitize_file_name', 'sanitize_hex_color', 'sanitize_hex_color_no_hash', 'sanitize_html_class', 'sanitize_key', 'sanitize_meta', 'sanitize_mime_type', 'sanitize_option', 'sanitize_sql_orderby', 'sanitize_term', 'sanitize_term_field', 'sanitize_text_field', 'sanitize_textarea_field', 'sanitize_title', 'sanitize_title_for_query', 'sanitize_locale_name', 'sanitize_title_with_dashes', 'sanitize_user', 'sanitize_url', 'sanitize_trackback_urls', 'wp_kses', 'wp_kses_post', 'wp_kses_data', 'esc_sql', 'esc_url', 'esc_url_raw', 'esc_js', 'esc_html', 'esc_attr', 'esc_textarea', 'esc_xml', 'wp_unslash', 'intval', 'defend_wp_firewall_esc_like', 'defend_wp_firewall_wpdb_real_escape', 'absint', 'defend_wp_firewall_detect_and_sanitize_sql_injection', 'defend_wp_firewall_do_sql_sanitize', 'defend_wp_firewall_prepare_in_int', '__return_empty_string', 'defend_wp_firewall_delete_cookie', 'defend_wp_firewall_wp_safe_redirect_check', 'floatval', 'escapeshellarg', 'defend_wp_firewall_delete_not_allowed_shortcodes', 'defend_wp_sanitize_file_name' );

		return apply_filters( 'defend_wp_get_registered_sanitize_functions', $func );
	}

	private function filter_rules_by_full_sanitize( $dfwp_firewall_rules ) {
		$return_array = array();
		foreach ( $dfwp_firewall_rules as $rule_key => $value ) {
			if ( ! empty( $value['options']['do_full_sanitize'] ) ) {
				$return_array[] = $value;
			}
		}
		return $return_array;
	}

	public function run_full_sanitize_checker( $dfwp_firewall_rules, $request, $request_type ) {
		if ( ! is_array( $request ) ) {
			return false;
		}
		$matched_rules = false;
		foreach ( $request as $data_key => $data ) {
			if ( is_string( $data ) ) {

				$firewall_id = $this->find_matched_rule( $dfwp_firewall_rules, $data, $data_key, $request_type, $this->sanitize_matched_rules, true );
				if ( $firewall_id !== false ) {
					$this->process_matched_sanitize_rule( $firewall_id );
					return true;
				}
			} else {
				$firewall_id = $this->find_matched_rule( $dfwp_firewall_rules, '_dfwp_dummy_value', $data_key, $request_type, $this->sanitize_matched_rules, true );
				if ( $firewall_id !== false ) {
					$this->process_matched_sanitize_rule( $firewall_id );
					return true;
				}
			}
		}
		return $matched_rules;
	}

	public function do_full_sanitize( $request_value, $request_key, $request_type ) {
		$return_array = array(
			'request_value' => $request_value,
			'request_key'   => $request_key,
		);
		if ( ! defend_wp_firewall_is_string( $request_value ) ) {
			return $return_array;
		}

		if ( empty( $this->do_full_sanitize[ $request_type ] ) && empty( $this->do_full_sanitize[ $request_type ]['matched_rule'] ) ) {
			return $return_array;
		}
		if ( empty( $this->do_full_sanitize[ $request_type ]['matched_rule']['options'] ) ) {
			return $return_array;
		}
		$do_full_sanitize = $this->do_full_sanitize[ $request_type ]['matched_rule']['options']['do_full_sanitize'];
		$full             = false;
		$partial          = false;
		$is_partial       = false;
		if ( ! empty( $do_full_sanitize['partial'] ) ) {
			$partial = $do_full_sanitize['partial'];
			foreach ( $partial as $p_value ) {
				if ( $this->check_rule( $request_key, $p_value, 'key' ) ) {
					$return_array = $this->do_sanitize( $request_value, $request_key, $p_value );
					$is_partial   = true;
				}
			}
		}
		if ( $is_partial === false && ! empty( $do_full_sanitize['full'] ) ) {
			$full         = $do_full_sanitize['full'];
			$return_array = $this->do_sanitize( $request_value, $request_key, $full );
		}

		return $return_array;
	}

	public function defend_wp_firewall_matched_rule( $formated_array ) {
		if ( isset( $formated_array['is_sanitized'] ) && $formated_array['is_sanitized'] === true ) {
			return $formated_array;
		}

		if ( empty( $formated_array['request_value'] ) ) {
			return $formated_array;
		}

		$request_value = $formated_array['request_value'];

		if ( empty( $formated_array['matched_rule_data'] ) ) {
			return $formated_array;
		}

		$matched_rule_data = $formated_array['matched_rule_data'];
		if ( empty( $matched_rule_data['matched_rule'] ) ) {
			return $formated_array;
		}

		$matched_rule = $matched_rule_data['matched_rule'];
		if ( $this->is_sanitize_enabled() && ! empty( $matched_rule['options']['do_sanitize'] ) && in_array( $formated_array['request_type'], $this->sanitize_supported_request_methods, true ) ) {
			$formated_array['is_sanitized']  = true;
			$filtered_value                  = $this->do_sanitize( $request_value, $formated_array['request_key'], $matched_rule['options']['do_sanitize'] );
			$formated_array['request_value'] = $filtered_value['request_value'];
			$formated_array['request_key']   = $filtered_value['request_key'];
		}
		return $formated_array;
	}

	public function defend_wp_firewall_request_after_run_all_rules( $formated_array ) {
		if ( empty( $formated_array['request_value'] ) ) {
			return $formated_array;
		}

		$request_value = $formated_array['request_value'];

		if ( ! isset( $formated_array['request_key'] ) ) {
			return $formated_array;
		}

		$request_key = $formated_array['request_key'];

		if ( empty( $formated_array['request_type'] ) ) {
			return $formated_array;
		}

		$request_type = $formated_array['request_type'];
		$return_array = $this->do_full_sanitize( $request_value, $request_key, $request_type );
		return $return_array;
	}
}
