<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Logs {

	public $logs_table_name;
	public $wpdb;
	public $current_wp_user;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->logs_table_name = $this->wpdb->base_prefix . 'dfwp_logs';
	}

	public function set_log( $data, $sanitize = true, $send_mail = false ) {
		global $userdata;
		$this->current_wp_user = $userdata;

		if ( empty( $data['extra'] ) ) {
			$data['extra'] = array();
		}
		// This is required for logging and debugging purposes, the input will be sanitized and escaped.
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
		$POSTT = $_POST; // Values are sanitized below on line number 79
		if ( ! empty( $POSTT ) && ! empty( $POSTT['pwd'] ) ) {
			$POSTT['pwd'] = '******';
		}

		if ( empty( $POSTT ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
			if ( ! empty( $HTTP_RAW_POST_DATA ) ) {
				$POSTT = array(
					'raw' => $HTTP_RAW_POST_DATA,
				);
			}
		}

		$HEADER = array();
		if ( $data['type'] == 'firewall' ) {
			$HEADER = defend_wp_firewall_get_server_headers(); // Values are sanitized below on line number 79
		}

		$this_user_login = $this->current_wp_user->user_login ?? 'Not_Logged_In';
		if ( defined( 'DOING_CRON' ) ) {
			$this_user_login = 'WP_Cron';
		}

		$data['extra'] = array_merge(
			array(
				'user_id'    => $this->current_wp_user->ID ?? '',
				'user_login' => $this_user_login,
				'GET'        => $_GET, // Values are sanitized below on line number 79
				'POST'       => $POSTT, // Values are sanitized below on line number 79
				'HEADER'     => $HEADER,
			),
			$data['extra']
		);

		$data['extra'] = wp_json_encode( $data['extra'], JSON_UNESCAPED_SLASHES );

		$data = array_merge(
			array(
				'source_url' => defend_wp_firewall_get_request_uri() ?? '',
				'source_ip'  => defend_wp_firewall_get_remote_address(),
				'hr_time'    => wp_date( 'Y-m-d H:i:s' ),
				'ts'         => time(),
			),
			$data
		);

		$sanitized_data = array();
		foreach ( $data as $kk => $vv ) {
			$sanitized_data[ $kk ] = sanitize_text_field( $vv );
		}

		$email_data                  = $sanitized_data;
		$email_data['local_hr_time'] = wp_date( 'd M @ h:ia' );

		$result = $this->wpdb->insert( $this->logs_table_name, $sanitized_data );
		do_action( 'defend_wp_firewall_after_saving_log', $sanitized_data, $this->wpdb->insert_id );

		if ( $result === false ) {
			defend_wp_firewall_log( $data, '--------set_log-failed-------' );
			defend_wp_firewall_log( $this->wpdb->last_error, '--------set_log-failed----last_error---' );
			defend_wp_firewall_log( $this->wpdb->last_query, '--------set_log-failed----last_query---' );
		}

		$this->clear_old_logs( $data );
	}

	public function get_all_logs( $block_type = '', $limit = 50 ) {
		global $wpdb;
		$limit = intval( $limit );
		if ( ! empty( $block_type ) ) {

			return $this->get_all_logs_by_type( $block_type );
		}

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d;', $this->logs_table_name, $limit ), ARRAY_A );

		return $result;
	}

	public function get_all_logs_by_type( $block_type = '' ) {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE type=%s ORDER BY id DESC LIMIT 50;', $this->logs_table_name, $block_type ), ARRAY_A );

		return $result;
	}

	public function get_all_logs_before_this_log_id( $last_log_id, $block_type = '' ) {
		global $wpdb;

		if ( ! empty( $block_type ) ) {
			return $this->get_all_logs_before_this_log_id_and_type( $last_log_id, $block_type = '' );
		}

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE id<%d ORDER BY id DESC LIMIT 50;', $this->logs_table_name, $last_log_id ), ARRAY_A );

		return $result;
	}

	public function get_all_logs_before_this_log_id_and_type( $last_log_id, $block_type = '' ) {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE id<%d AND type=%s ORDER BY id DESC LIMIT 50;', $this->logs_table_name, $last_log_id, $block_type ), ARRAY_A );

		return $result;
	}

	public function clear_all_logs() {
		global $wpdb;
		$result = $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i; ', $this->logs_table_name ) );

		return true;
	}

	public function clear_old_logs( $data ) {
		global $wpdb;
		$type   = $data['type'];
		$req_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE type= %s  ORDER BY id DESC LIMIT 1 OFFSET %d', $this->logs_table_name, $type, DEFEND_WP_FIREWALL_LOGS_MAX_NUM_OF_LOGS ) );

		if ( empty( $req_id ) ) {
			return false;
		}
		$result = $this->wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE type=%s AND id<%d', $this->logs_table_name, $type, $req_id ) );

		return true;
	}

	public function get_log_by_id( $log_id ) {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE id=%d;', $this->logs_table_name, $log_id ), ARRAY_A );

		if ( empty( $result ) ) {

			return array();
		}

		return $result[0];
	}
}
