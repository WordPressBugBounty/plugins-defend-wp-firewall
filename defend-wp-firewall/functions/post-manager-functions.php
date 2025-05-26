<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Post_Manager_Functions {

	private $firewall_obj;
	private $dfwp_wp_delete_post_firewall_rules = array();
	private $get_post_metadata                  = array();

	public function __construct() {
		$this->firewall_obj = new Defend_WP_Firewall_Functions();
	}

	public function defend_wp_matched_rules( $dfwp_firewall_rule ) {
		$this->filter_rules_by_wp_delete_post_rules( $dfwp_firewall_rule );
	}

	private function filter_rules_by_wp_delete_post_rules( $dfwp_firewall_rule ) {
		if ( ! empty( $dfwp_firewall_rule['options'] ) && ! empty( $dfwp_firewall_rule['options']['wp_post_restrictions'] ) ) {
			if ( ! empty( $dfwp_firewall_rule['options']['wp_post_restrictions']['wp_delete_post'] ) && empty( $this->dfwp_wp_delete_post_firewall_rules[ $dfwp_firewall_rule['id'] ] ) ) {
				$this->dfwp_wp_delete_post_firewall_rules[ $dfwp_firewall_rule['id'] ] = $dfwp_firewall_rule;
			}
		}
	}

	public function wp_post_restrictions( $rule ) {
		if ( ! empty( $this->get_post_metadata[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['wp_post_restrictions'] ) && ! empty( $rule['options']['wp_post_restrictions']['get_post_metadata'] ) ) {
			$this->get_post_metadata[ $rule['id'] ] = $rule['options']['wp_post_restrictions']['get_post_metadata'];
		}
	}

	public function pre_delete_post( $check, $post, $force_delete ) {
		if ( empty( $this->dfwp_wp_delete_post_firewall_rules ) ) {
			return $check;
		}
		$post_id       = $post->ID;
		$block_request = false;
		foreach ( $post as $attr_key => $attr_value ) {
			if ( empty( $attr_value ) ) {
				continue;
			}
			foreach ( $this->dfwp_wp_delete_post_firewall_rules as $firewall_id => $dfwp_firewall_rule ) {
				if ( ! empty( $dfwp_firewall_rule['options'] ) && ! empty( $dfwp_firewall_rule['options']['wp_post_restrictions'] ) ) {
					if ( ! empty( $dfwp_firewall_rule['options']['wp_post_restrictions']['wp_delete_post'] ) ) {
						$wp_delete_post = $dfwp_firewall_rule['options']['wp_post_restrictions']['wp_delete_post'];
						if ( ! empty( $wp_delete_post['block_immediately'] ) ) {
							$block_request = true;
						} elseif ( $this->firewall_obj->check_rule( $attr_key, $wp_delete_post['rules'], 'key' ) ) {
							$result = $this->firewall_obj->check_rule( $attr_value, $wp_delete_post['rules'] );
							if ( $result === false ) {
								if ( ! empty( $wp_delete_post['allowed_cap'] ) && $this->allowed_cap( $wp_delete_post['allowed_cap'], $post_id ) ) {
									return $check;
								}
							}
							$block_request = true;
						}
						if ( $block_request ) {
							$title                               = $this->firewall_obj->format_firewall_title( $dfwp_firewall_rule['options'] );
								$matched_post_data               = array();
								$matched_post_data['post_id']    = $post->ID;
								$matched_post_data['attr_key']   = $attr_key;
								$matched_post_data['attr_value'] = $attr_value;
								$block                           = $wp_delete_post['block'];
								$log                             = $wp_delete_post['log'];
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
				}
			}
		}
		return $check;
	}

	private function allowed_cap( $caps, $post_id ) {

		foreach ( $caps as $value ) {
			if ( current_user_can( $value, $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_post_metadata( $meta_value, $object_id, $meta_key, $single, $meta_type ) {
		if ( empty( $this->get_post_metadata ) ) {
			return $meta_value;
		}
		$matched_rule = array();
		foreach ( $this->get_post_metadata  as $firewall_id => $meta_rule ) {
			foreach ( $meta_rule['rules'] as $meta_rule_values ) {
				$rule = $meta_rule_values['rule'];
				if ( isset( $rule['key'] ) && $this->firewall_obj->check_rule( $meta_key, $rule, 'key' ) ) {
					$matched_rule = $meta_rule_values;
					break;
				} elseif ( isset( $rule['rule'] ) && $this->firewall_obj->check_rule( $meta_key, $rule ) ) {
					$matched_rule = $meta_rule_values;
					break;
				}
			}
			if ( ! empty( $matched_rule ) ) {
				break;
			}
		}
		if ( empty( $matched_rule ) ) {
			return $meta_value;
		}

		$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

		if ( ! $meta_cache ) {
			$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
			if ( isset( $meta_cache[ $object_id ] ) ) {
				$meta_cache = $meta_cache[ $object_id ];
			} else {
				$meta_cache = null;
			}
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset( $meta_cache[ $meta_key ] ) ) {
			if ( $single ) {
				$processed_value = $this->run_meta_rule_against_meta_value( $meta_cache[ $meta_key ][0], $matched_rule, $firewall_id );
				return maybe_unserialize( $processed_value );
			} else {
				$processed_value = $this->run_meta_rule_against_meta_value( $meta_cache[ $meta_key ], $matched_rule, $firewall_id );
				return array_map( 'maybe_unserialize', $processed_value );
			}
		}

		return null;
	}

	public function run_meta_rule_against_meta_value( $meta_value, $matched_rule, $firewall_id ) {
		$rule = $matched_rule['rule'];
		if ( is_array( $meta_value ) ) {
			foreach ( $meta_value as $request_key => $request_value ) {
				if ( isset( $rule['key'] ) && $this->firewall_obj->check_rule( $request_value, $rule ) ) {
					$meta_value[ $request_key ] = $this->process_meta_rule( $matched_rule, $request_value, $request_key, $meta_value, $firewall_id );
				} elseif ( ! isset( $rule['key'] ) ) {
					$meta_value[ $request_key ] = $this->process_meta_rule( $matched_rule, $request_value, $request_key, $meta_value, $firewall_id );
				}
			}
		} elseif ( is_string( $matched_rule ) ) {
			return $this->process_meta_rule( $matched_rule, $meta_value, '', $meta_value, $firewall_id );
		}

		return $meta_value;
	}

	private function process_meta_rule( $rule, $request_value, $request_key, $meta_value, $firewall_id ) {
		if ( ! empty( $rule['do_sanitize'] ) ) {
			$return_array = $this->firewall_obj->do_sanitize( $request_value, $request_key, $rule['do_sanitize'] );
			return $return_array['request_value'];
		} else {
			if ( $rule['log'] ) {
				defend_wp_firewall_die(
					array(
						'type'        => 'firewall',
						'firewall_id' => $firewall_id,
						'title'       => 'Get Post Meta (ID #' . ( $firewall_id ) . ')',
						'message'     => 'Access denied by firewall.',
						'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $meta_value ) ),
					),
					true,
					false,
				);
			}
			if ( $rule['block'] ) {
				return '';
			}
		}
		return $request_value;
	}
}
