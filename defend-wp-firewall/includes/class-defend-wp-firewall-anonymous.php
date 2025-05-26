<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Anonymous {

	private $logs_table_name;
	private $defend_wp_firewall_options;
	private $timeout = 300;
	private $url     = DEFEND_WP_FIREWALL_SERVICE_URL . '/collect-firewall';

	public function __construct() {
		global $wpdb;
		$this->logs_table_name = $wpdb->base_prefix . 'dfwp_logs';
		add_action( 'init', array( $this, 'init' ), 10, 2 );
		add_action( 'defend_wp_firewall_after_saving_log', array( $this, 'collect_send_data' ), 10, 2 );
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		add_action( 'defend_wp_firewall_cron_hook', array( $this, 'defend_wp_firewall_cron' ) );
	}

	public function collect_send_data( $data, $insert_id ) {
		$this->send_firewall_data( $data, $insert_id );
	}

	public function init() {
		$pub_key = $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' );
		if ( ! empty( $pub_key ) ) {
			if ( ! wp_next_scheduled( 'defend_wp_firewall_cron_hook' ) ) {
				wp_schedule_event( time(), 'daily', 'defend_wp_firewall_cron_hook' );
			}
		}
	}

	public function defend_wp_firewall_cron() {
		if ( defined( 'DEFEND_WP_FIREWALL_DONOT_COLLECT_FIREWALL_DATA' ) && DEFEND_WP_FIREWALL_DONOT_COLLECT_FIREWALL_DATA ) {
			return;
		}

		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT `id`, `firewall_id`, `source_ip` FROM %i WHERE `type`=%s AND data_collected="0" AND firewall_id !="0";', $this->logs_table_name, 'firewall' ), ARRAY_A );

		if ( empty( $result ) ) {
			return;
		}

		$firewall_data                  = array();
		$firewall_data                  = defend_wp_firewall_collect_urls();
		$firewall_data['firewall_data'] = $result;
		$firewall_data['signature']     = $this->get_signature();
		$firewall_data['is_openssl']    = defend_wp_firewall_check_openssl();

		$return = $this->send_sevice_request( $firewall_data );

		if ( $return !== false ) {
			foreach ( $result as $key => $value ) {
				$wpdb->update(
					$this->logs_table_name,
					array( 'data_collected' => '1' ),
					array( 'id' => $value['id'] )
				);
			}
		}
	}

	private function send_firewall_data( $data, $insert_id ) {

		if ( defined( 'DEFEND_WP_FIREWALL_DONOT_COLLECT_FIREWALL_DATA' ) && DEFEND_WP_FIREWALL_DONOT_COLLECT_FIREWALL_DATA ) {
			return;
		}
		if ( empty( $data['firewall_id'] ) || $data['type'] !== 'firewall' ) {
			return;
		}

		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( 'SELECT `id` FROM %i WHERE firewall_id=%d AND `source_ip`=%s AND `type`=%s AND data_collected="1";', $this->logs_table_name, $data['firewall_id'], $data['source_ip'], 'firewall' ), ARRAY_A );

		if ( ! empty( $result ) && count( $result ) > 5 ) {
			return;
		}
		$firewall_data = array();
		$needed_data   = array();
		$firewall_data = defend_wp_firewall_collect_urls();

		$needed_data['source_ip']       = $data['source_ip'];
		$needed_data['firewall_id']     = $data['firewall_id'];
		$firewall_data['firewall_data'] = array( $needed_data );
		$firewall_data['signature']     = $this->get_signature();
		$firewall_data['is_openssl']    = defend_wp_firewall_check_openssl();

		$return = $this->send_sevice_request( $firewall_data );

		if ( $return !== false ) {
			$wpdb->update(
				$this->logs_table_name,
				array( 'data_collected' => '1' ),
				array( 'id' => $insert_id )
			);
		}
	}

	public function send_sevice_request( $request_data = array() ) {
		$body      = apply_filters( 'defend_wp_firewall_service_request', $request_data );
		$http_args = array(
			'headers'   => array( 'Content-Type' => 'application/json' ),
			'method'    => 'POST',
			'verify'    => false,
			'sslverify' => false,
			'timeout'   => $this->timeout,
			'body'      => wp_json_encode( $body ),
		);

		$url           = $this->url;
		$log           = array();
		$log ['url']   = $url;
		$log ['start'] = time();

		try {
			$response      = wp_remote_request( $url, $http_args );
			$full_response = $response;
			$log ['end']   = time();
			if ( is_wp_error( $response ) ) {
				$error_message         = $response->get_error_message();
				$response              = array();
				$response['status']    = 'error';
				$response['error_msg'] = $error_message;
				$log['response']       = $response;

			} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				$code                   = wp_remote_retrieve_response_code( $response );
				$error_message          = wp_remote_retrieve_response_message( $response );
				$response_body          = wp_remote_retrieve_body( $response );
				$response_data          = json_decode( $response_body, true );
				$response               = array();
				$response               = $response_data;
				$response['error_msg']  = $error_message;
				$response['error_code'] = $code;
				$log['response']        = $response;
			} else {
				$response_body   = wp_remote_retrieve_body( $response );
				$response        = json_decode( $response_body, true );
				$log['response'] = 'success';
			}
		} catch ( Exception $e ) {
			$response                  = array();
			$response['status']        = 'error';
			$response['error_msg']     = $e->getMessage();
			$response['full_response'] = $full_response;
			$log['response']           = $response;
			return $response;
		}
		$response['full_response'] = $full_response;
		$this->system_run_log( $log );
		if ( $response['status'] === 'error' ) {
			return false;
		}
		return $response;
	}

	public function system_run_log( $data ) {
		$recent_logs = array();

		$get_recent_log = $this->defend_wp_firewall_options->get_option( 'dfwp_anonymous_log' );

		if ( ! empty( $get_recent_log ) ) {
			$recent_logs = json_decode( $get_recent_log );
		}

		if ( count( $recent_logs ) >= 10 ) {
			array_shift( $recent_logs );
		}

		array_push( $recent_logs, $data );

		$this->defend_wp_firewall_options->set_option( 'dfwp_anonymous_log', wp_json_encode( $recent_logs ), true );
	}

	private function get_signature() {
		$pub_key             = $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' );
		$dfwp_activation_key = $this->defend_wp_firewall_options->get_option( 'dfwp_activation_key' );
		return hash( 'sha256', $pub_key . $dfwp_activation_key );
	}
}
