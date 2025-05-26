<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Options {

	public $all_configs;
	public $options_table_name;
	public $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb        = $wpdb;
		$this->all_configs = array();

		$options_table_name       = $this->wpdb->base_prefix . 'dfwp_options';
		$this->options_table_name = $options_table_name;
	}

	public function set_option( $name, $value, $sanitize = false ) {
		global $wpdb;
		if ( $sanitize ) {
			$name  = sanitize_text_field( $name );
			$value = $this->sanitize_input( $value );
		}

		if ( is_array( $value ) ) {
			$value = json_encode( $value );
		}

		$sql = 'REPLACE INTO %i (`name`, `value`) VALUES(%s, %s)';

		$result = $wpdb->query( $wpdb->remove_placeholder_escape( $wpdb->prepare( 'REPLACE INTO %i (`name`, `value`) VALUES(%s, %s)', $this->options_table_name, $name, $value ) ) );

		if ( $result === false ) {
			defend_wp_firewall_log( $name, '--------update_option-failed-------' );
			defend_wp_firewall_log( $wpdb->last_error, '--------update_option-failed----last_error---' );
			defend_wp_firewall_log( $wpdb->last_query, '--------update_option-failed----last_query---' );
		}
		return $result;
	}

	private function sanitize_input( $input ) {
		if ( is_array( $input ) ) {
			// If input is an array, sanitize each element
			return array_map( 'sanitize_text_field', $input );
		} else {
			// If input is a string, sanitize the string
			return sanitize_text_field( $input );
		}
	}

	public function get_option( $name, $value = null ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( 'SELECT `value` FROM %i WHERE `name`=%s', $this->options_table_name, $name ) );

		return $value;
	}

	public function get_all_configs( $refresh = true ) {
		if ( $refresh ) {
			global $wpdb;

			$temp_all_configs = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $this->options_table_name ), ARRAY_A );
			$all_configs      = array();
			foreach ( $temp_all_configs as $key => $value ) {
				if ( $value['name'] == 'dfwp_request_log' ) {
					continue;
				}
				$all_configs[ $value['name'] ] = $value['value'];
			}
			$this->all_configs = $all_configs;
		}

		return $this->all_configs;
	}

	public function get_json_decoded_option( $name, $value = null ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( 'SELECT `value` FROM %i WHERE `name`=%s', $this->options_table_name, $name ) );
		try {
			if ( ! empty( $value ) ) {
				return json_decode( $value, true );
			} else {
				return array();
			}
		} catch ( Exception $e ) {
			return array();
		}

		return $value;
	}

	public function delete_option( $name ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( 'DELETE FROM %i WHERE `name`=%s', $this->options_table_name, $name ) );

		return $value;
	}
}
