<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Shortcode_Functions {

	private $matched_shortcodes = array();
	private $firewall_obj;
	private $do_shortcode_rules = array();

	public function __construct() {
		$this->firewall_obj = new Defend_WP_Firewall_Functions();
	}

	public function defend_wp_firewall_shortcode_rules( $rule ) {
		if ( ! empty( $this->do_shortcode_rules[ $rule['id'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['shortcode_rules'] ) ) {
			$this->do_shortcode_rules[ $rule['id'] ] = $rule['options']['shortcode_rules'];
		}
	}

	public function get_shortcode( $content ) {
		global $shortcode_tags;
		if ( ! str_contains( $content, '[' ) ) {
			return false;
		}
		if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
			return false;
		}
		preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
		$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );
		if ( empty( $tagnames ) ) {
			return false;
		}
		$content = do_shortcodes_in_html_tags( $content, false, $tagnames );
		$pattern = get_shortcode_regex( $tagnames );
		$data    = preg_replace_callback( "/$pattern/", array( $this, 'get_shortcode_tag' ), $content );

		if ( ! empty( $this->matched_shortcodes ) ) {
			$return_array             = $this->matched_shortcodes;
			$this->matched_shortcodes = array();
			return $return_array;
		}

		return array();
	}

	private function get_shortcode_tag( $m ) {

		if ( '[' === $m[1] && ']' === $m[6] ) {
			return substr( $m[0], 1, -1 );
		}

		$tag  = $m[2];
		$attr = shortcode_parse_atts( $m[3] );

		$this->matched_shortcodes [] = array(
			'tag'  => $tag,
			'attr' => $attr,
		);
	}

	public function defend_wp_firewall_matched_rule( $formated_array ) {
		if ( empty( $formated_array ) ) {
			return $formated_array;
		}
		if ( empty( $formated_array['request_value'] ) ) {
			return $formated_array;
		}

		$request_value = $formated_array['request_value'];

		if ( empty( $formated_array['matched_rule_data'] ) && empty( $matched_rule_data['matched_rule'] ) ) {
			return $formated_array;
		}

		$matched_rule_data = $formated_array['matched_rule_data'];

		if ( empty( $matched_rule_data['matched_rule'] ) ) {
			return $formated_array;
		}

		if ( empty( $matched_rule_data['matched_rule']['options'] ) ) {
			return $formated_array;
		}

		if ( empty( $matched_rule_data['matched_rule']['options']['shortcode_rules'] ) ) {
			return $formated_array;
		}

		$shortcode_data = $this->get_shortcode( wp_unslash( $request_value ) );
		if ( empty( $shortcode_data ) ) {
			return $formated_array;
		}
		$shortcode_rules = $matched_rule_data['matched_rule']['options']['shortcode_rules'];
		foreach ( $shortcode_rules as $shortcode_rule ) {
			foreach ( $shortcode_data as $shortcode ) {
				if ( $shortcode_rule['tag'] === $shortcode['tag'] ) {
					$this->run_firewall_shortcode_check( $shortcode['attr'], $shortcode_rule['attr'], $shortcode_rule['log'], $shortcode_rule['block'], $matched_rule_data );
				}
			}
		}
		return $formated_array;
	}

	private function run_firewall_shortcode_check( $shortcode, $shortcode_rule, $log, $block, $matched_rule_data ) {
		foreach ( $shortcode as $attr_key => $attr_value ) {
			foreach ( $shortcode_rule as $condition ) {
				if ( empty( $condition['do_firewall'] ) ) {
					continue;
				}
				if ( $this->firewall_obj->check_rule( $attr_key, $condition, 'key' ) ) {
					$result = $this->firewall_obj->check_rule( $attr_value, $condition );
					if ( $result !== false ) {
						$firewall_id = $matched_rule_data['matched_rule']['id'];
						$title       = $this->firewall_obj->format_firewall_title( $matched_rule_data['matched_rule']['options'] );
						unset( $matched_rule_data['matched_rule'] );
						$matched_rule_data['attr_key']   = $attr_key;
						$matched_rule_data['attr_value'] = $attr_value;
						defend_wp_firewall_die(
							array(
								'type'        => 'firewall',
								'firewall_id' => $firewall_id,
								'title'       => $title . ' (ID #' . ( $firewall_id ) . ')',
								'message'     => 'Access denied by firewall.',
								'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
							),
							$log,
							$block
						);
					}
				}
			}
		}
	}

	public function pre_do_shortcode_tag( $return, $tag, $attr, $m ) {
		if ( empty( $this->do_shortcode_rules ) ) {
			return $return;
		}
		if ( empty( $attr ) ) {
			return $return;
		}
		$is_sanitized = false;
		foreach ( $this->do_shortcode_rules as $shortcode_rules ) {
			foreach ( $shortcode_rules as $shortcode_rule ) {
				if ( ! empty( $shortcode_rule['tag'] ) && $shortcode_rule['tag'] === $tag ) {
					$result = $this->run_do_shortcode_check( $attr, $shortcode_rule['attr'] );
					if ( $result === true ) {
						return '';
					} elseif ( is_array( $result ) ) {
						$is_sanitized = true;
						$attr         = $result;
						break;
					}
				}
			}
			if ( $is_sanitized ) {
				break;
			}
		}
		if ( $is_sanitized ) {
			global $shortcode_tags;
			$content = isset( $m[5] ) ? $m[5] : null;

			$output = $m[1] . call_user_func( $shortcode_tags[ $tag ], $attr, $content, $tag ) . $m[6];

			/**
			 * Filters the output created by a shortcode callback.
			 *
			 * @since 4.7.0
			 *
			 * @param string       $output Shortcode output.
			 * @param string       $tag    Shortcode name.
			 * @param array|string $attr   Shortcode attributes array or the original arguments string if it cannot be parsed.
			 * @param array        $m      Regular expression match array.
			 */
			return apply_filters( 'do_shortcode_tag', $output, $tag, $attr, $m );
		}
		return $return;
	}

	private function run_do_shortcode_check( $attr, $shortcode_rule ) {
		$is_sanitized = false;
		foreach ( $attr as $attr_key => $attr_value ) {
			foreach ( $shortcode_rule as $condition ) {
				if ( empty( $condition['do_shortcode'] ) ) {
					continue;
				}
				if ( $this->firewall_obj->check_rule( $attr_key, $condition, 'key' ) ) {
					$result = $this->firewall_obj->check_rule( $attr_value, $condition );
					if ( $result !== false ) {
						if ( ! empty( $condition['block'] ) ) {
							return true;
						} elseif ( ! empty( $condition['do_sanitize'] ) ) {
							$is_sanitized      = true;
							$shortcode_return  = $this->firewall_obj->do_sanitize( $attr_value, $attr_key, $condition['do_sanitize'] );
							$attr[ $attr_key ] = $shortcode_return['request_value'];
						}
					}
				}
			}
		}

		if ( $is_sanitized === true ) {
			return $attr;
		}

		return false;
	}
}
