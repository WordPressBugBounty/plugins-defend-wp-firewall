<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Service {

	private $url              = DEFEND_WP_FIREWALL_SERVICE_URL;
	private $timeout          = 300;
	private $add_site_url     = DEFEND_WP_FIREWALL_SERVICE_URL . '/add-site';
	private $update_ptc       = DEFEND_WP_FIREWALL_SERVICE_URL . '/update-site';
	private $update_site_meta = DEFEND_WP_FIREWALL_SERVICE_URL . '/update-site-meta';
	private $sync_site        = DEFEND_WP_FIREWALL_SERVICE_URL . '/sync-site';
	public $defend_wp_firewall_options;

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$this->define_hooks();
	}

	public function define_hooks() {
		add_action( 'admin_notices', array( $this, 'admin_notice__error' ) );
		add_action( 'activate_plugin', array( $this, 'send_ptc_details' ), -1, 2 );
		add_action( 'deactivate_plugin', array( $this, 'send_ptc_details' ), -1, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'send_ptc_details' ), -1, 2 );
		add_action( 'pre_uninstall_plugin', array( $this, 'send_ptc_details' ), -1, 2 );
		add_action( 'delete_plugin', array( $this, 'send_ptc_details' ), -1 );
		add_action( 'delete_theme', array( $this, 'send_ptc_details' ), -1 );
		add_action( 'after_switch_theme', array( $this, 'send_ptc_details' ), -1 );
		add_action( 'wp_ajax_dfwp_firewall_init_setup', array( $this, 'dfwp_firewall_init_setup' ) );
		add_action( 'wp_ajax_dfwp_firewall_join_email', array( $this, 'dfwp_firewall_join_email' ) );
		add_action( 'wp_ajax_dfwp_firewall_sync_firewall', array( $this, 'dfwp_firewall_sync_firewall' ) );
		add_action( 'wp_ajax_dfwp_firewall_revoke_connect_firewall', array( $this, 'dfwp_firewall_revoke_connect_firewall' ) );
		add_action( 'defend_wp_login_success', array( $this, 'defend_wp_login_success' ), 10, 1 );
		add_action( 'setup_theme', array( $this, 'process_iwp_request' ), 200 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_nopriv_firewall_sync_ptc', array( $this, 'check_send_ptc_update' ) );
		add_action( 'wp_ajax_firewall_sync_ptc', array( $this, 'check_send_ptc_update' ) );
	}

	public function enqueue_scripts() {
		if ( $this->is_connected() && $this->can_send_ptc_update() ) {
			wp_enqueue_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-ptc-update', plugin_dir_url( __FILE__ ) . 'js/defend-wp-firewall-sync.js', array( 'jquery' ), DEFEND_WP_FIREWALL_VERSION, false );
			wp_localize_script(
				DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-ptc-update',
				'defend_wp_firewall_sync_obj',
				array(
					'security' => wp_create_nonce( 'dwp_firewall_revmakx' ),
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	public function send_sevice_request( $request_data = array(), $url = '' ) {
		$body      = apply_filters( 'defend_wp_firewall_service_request', $request_data );
		$http_args = array(
			'headers'   => array( 'Content-Type' => 'application/json' ),
			'method'    => 'POST',
			'verify'    => false,
			'sslverify' => false,
			'timeout'   => $this->timeout,
			'body'      => wp_json_encode( $body ),
		);

		if ( empty( $url ) ) {
			$url = $this->url;
		}
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
				if ( empty( $request_data['skip_notice'] ) ) {
					$response['url']           = $url;
					$response['full_response'] = $full_response;
					$this->set_error( wp_json_encode( $response ) );
				}
				$log['response'] = $response;

			} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				$code                   = wp_remote_retrieve_response_code( $response );
				$error_message          = wp_remote_retrieve_response_message( $response );
				$response_body          = wp_remote_retrieve_body( $response );
				$response_data          = json_decode( $response_body, true );
				$response               = array();
				$response               = $response_data;
				$response['error_msg']  = $error_message;
				$response['error_code'] = $code;
				if ( empty( $request_data['skip_notice'] ) ) {
					$response['url']           = $url;
					$response['full_response'] = $full_response;
					$this->set_error( wp_json_encode( $response ) );
				}
				$log['response'] = $response;
			} else {
				$response_body   = wp_remote_retrieve_body( $response );
				$response        = json_decode( $response_body, true );
				$log['response'] = 'success';
			}
		} catch ( Exception $e ) {
			$response              = array();
			$response['status']    = 'error';
			$response['error_msg'] = $e->getMessage();
			if ( empty( $request_data['skip_notice'] ) ) {
				$response['url']           = $url;
				$response['full_response'] = $full_response;
				$this->set_error( wp_json_encode( $response ) );
			}
			$response['full_response'] = $full_response;
			$log['response']           = $response;
			return $response;
		}
		$response['full_response'] = $full_response;
		$this->system_run_log( $log );
		return $response;
	}

	public function admin_notice__error() {

		$class   = 'notice notice-error';
		$message = get_transient( 'defend_wp_firewall_error_notice' );
		if ( ! empty( $message ) ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( 'DefendWP firewall service error: ' . $message ) );
		}
	}

	private function set_error( $error_message ) {
		set_transient( 'defend_wp_firewall_error_notice', $error_message, 30 );
	}

	public function collect_ptc_details() {
		$ptc_details = array(
			'plugins' => array(),
			'themes'  => array(),
		);
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$dfwp_get_plugin = get_plugins();
		if ( ! empty( $dfwp_get_plugin ) ) {
			foreach ( $dfwp_get_plugin as $p_slug => $p_value ) {
				$temp            = array();
				$temp['slug']    = $p_slug;
				$temp['version'] = $p_value['Version'];
				$is_active       = false;
				if ( is_plugin_active( $p_slug ) ) {
					$is_active = true;
				}
				$temp['active']           = $is_active;
				$ptc_details['plugins'][] = $temp;
			}
		}
		if ( ! function_exists( 'wp_get_themes' ) ) {
			include_once ABSPATH . 'wp-includes/theme.php';
		}
		$dfwp_get_theme = wp_get_themes();
		if ( ! empty( $dfwp_get_theme ) ) {
			$current_slug = get_stylesheet();
			foreach ( $dfwp_get_theme as $t_slug => $t_value ) {
				$temp            = array();
				$temp['slug']    = $t_slug;
				$temp['version'] = $t_value->get( 'Version' );
				$is_active       = false;
				if ( $current_slug === $t_slug ) {
					$is_active = true;
				}
				$temp['active']          = $is_active;
				$ptc_details['themes'][] = $temp;
			}
		}
		global $wp_version;
		$ptc_details['core']         = $wp_version;
		$ptc_details['dfwp_version'] = DEFEND_WP_FIREWALL_VERSION;
		return $ptc_details;
	}

	public function collect_urls() {
		return defend_wp_firewall_collect_urls();
	}

	public function fetch_all_firewall_rules() {
		$params   = $this->collect_ptc_details();
		$urls     = $this->collect_urls();
		$params   = array_merge( $params, $urls );
		$response = $this->send_sevice_request( $params, $this->add_site_url );
		if ( ! empty( $response ) && ! empty( $response['status'] ) && $response['status'] === 'success' ) {
			if ( ! empty( $response['rules'] ) ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
				return $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', wp_json_encode( $response['rules'] ), true );
			}
		}

		return false;
	}

	private function add_site() {
		if ( $this->is_connected() ) {
			return;
		}
		$params = $this->collect_ptc_details();
		$urls   = $this->collect_urls();

		$params['skip_notice']    = true;
		$params['activation_key'] = $this->get_activation_key();
		$params['is_openssl']     = defend_wp_firewall_check_openssl();
		$params                   = array_merge( $params, $urls );
		$response                 = $this->send_sevice_request( $params, $this->add_site_url );
		if ( ! empty( $response ) && ! empty( $response['status'] ) && $response['status'] === 'success' ) {
			if ( ! empty( $response['rules'] ) ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
				return $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', wp_json_encode( $response['rules'] ), true );
			} elseif ( $this->is_connected() ) {
					return true;
			}
		}
		return $response;
	}

	public function sync_site() {
		if ( $this->is_connected() == false ) {
			return;
		}
		$params               = $this->collect_ptc_details();
		$urls                 = $this->collect_urls();
		$params['signature']  = $this->get_signature();
		$params['is_openssl'] = defend_wp_firewall_check_openssl();
		$params               = array_merge( $params, $urls );
		$response             = $this->send_sevice_request( $params, $this->sync_site );
		if ( ! empty( $response ) && ! empty( $response['status'] ) && $response['status'] === 'success' ) {
			$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
			if ( ! empty( $response['rules'] ) ) {
				return $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', wp_json_encode( $response['rules'] ), true );
			} else {
				$this->defend_wp_firewall_options->delete_option( 'dfwp_firewall' );
			}
		}

		return $response;
	}

	public function send_ptc_details_server() {
		$params               = $this->collect_ptc_details();
		$urls                 = $this->collect_urls();
		$params['signature']  = $this->get_signature();
		$params['is_openssl'] = defend_wp_firewall_check_openssl();
		$params               = array_merge( $params, $urls );

		$response = $this->send_sevice_request( $params, $this->update_ptc );
		if ( ! empty( $response ) && ! empty( $response['status'] ) && $response['status'] === 'success' ) {
			if ( ! empty( $response['rules'] ) ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_firewall_last_sync', time() );
				return $this->defend_wp_firewall_options->set_option( 'dfwp_firewall', wp_json_encode( $response['rules'] ), true );
			} else {
				$this->defend_wp_firewall_options->delete_option( 'dfwp_firewall' );
			}
		} else {
			$this->set_dfwp_send_ptc_update_init_time_option();
		}
		return $response;
	}

	public function send_ptc_details() {
		if ( $this->is_connected() && $this->is_scheduled_ptc_update() == false ) {
			$this->set_dfwp_send_ptc_update_init_time_option();
		}
	}

	public function is_connected() {
		$pub_key = $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' );
		if ( ! empty( $pub_key ) ) {
			return true;
		}
		return false;
	}

	public function is_scheduled_ptc_update() {
		$init_time = $this->defend_wp_firewall_options->get_option( 'dfwp_send_ptc_update_init_time' );
		if ( ! empty( $init_time ) ) {
			return true;
		}
		return false;
	}

	public function can_send_ptc_update() {
		$init_time = $this->defend_wp_firewall_options->get_option( 'dfwp_send_ptc_update_init_time' );
		if ( ! empty( $init_time ) && ( time() > $init_time ) ) {
			return true;
		}
		return false;
	}

	private function set_dfwp_send_ptc_update_init_time_option() {
		$interval = 12 * 60 * 60;
		if ( defined( 'DEFEND_WP_FIREWALL_PTC_UPDATE_INTERVAL' ) && is_numeric( DEFEND_WP_FIREWALL_PTC_UPDATE_INTERVAL ) && DEFEND_WP_FIREWALL_PTC_UPDATE_INTERVAL > 0 ) {
			$interval = DEFEND_WP_FIREWALL_PTC_UPDATE_INTERVAL;
		}
		$this->defend_wp_firewall_options->set_option( 'dfwp_send_ptc_update_init_time', time() + $interval );
	}


	public function dfwp_firewall_init_setup() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		if ( ! empty( $pub_key ) ) {
			return;
		}
		$result = $this->add_site();

		if ( ! is_array( $result ) ) {
			if ( $result == false ) {
				defend_wp_firewall_die_with_json_encode_simple(
					array(
						'success' => false,
						'result'  => array(
							'status'   => 'error',
							'res_desc' => 'Unable to save activation key',
						),
					)
				);
			} elseif ( $result == true ) {
				if ( $this->is_connected() ) {
					defend_wp_firewall_die_with_json_encode_simple(
						array(
							'success'          => true,
							'is_pro_activated' => defend_wp_firewall_is_dfwp_pro_activated(),
						)
					);
				}
			}
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => false,
				'result'  => $result,
			)
		);
	}

	private function join_email( $email ) {
		$params              = array();
		$urls                = $this->collect_urls();
		$params['email']     = $email;
		$params['signature'] = $this->get_signature();
		$params              = array_merge( $params, $urls );
		$response            = $this->send_sevice_request( $params, $this->update_site_meta );
		if ( ! empty( $response ) && ! empty( $response['status'] ) && $response['status'] === 'success' ) {
			return $this->defend_wp_firewall_options->set_option( 'dfwp_join_email', $email, true );
		}

		return $response;
	}

	private function get_signature() {
		$pub_key             = $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' );
		$dfwp_activation_key = $this->defend_wp_firewall_options->get_option( 'dfwp_activation_key' );
		return hash( 'sha256', $pub_key . $dfwp_activation_key );
	}

	public function dfwp_firewall_join_email() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		if ( empty( $_POST['email'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'success' => false,
					'result'  => array(
						'status'   => 'error',
						'res_desc' => 'Invalid email address',
					),
				)
			);
		}
		$email = sanitize_email( wp_unslash( $_POST['email'] ) );
         // phpcs:enable WordPress.Security.NonceVerification.Missing
		$email = is_email( $email );
		if ( $email === false ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'success' => false,
					'result'  => array(
						'status'   => 'error',
						'res_desc' => 'Invalid email address',
					),
				)
			);
		}
		$result = $this->join_email( $email );

		if ( $result == false ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'success' => false,
					'result'  => array(
						'status'   => 'error',
						'res_desc' => 'Unable to save email',
					),
				)
			);
		} elseif ( $result == true ) {
			if ( ! empty( $this->defend_wp_firewall_options->get_option( 'dfwp_join_email' ) ) ) {
				defend_wp_firewall_die_with_json_encode_simple(
					array(
						'success' => true,
					)
				);
			}
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => false,
				'result'  => $result,
			)
		);
	}

	public function get_activation_key() {
		$key = hash( 'sha256', wp_rand( 1, 99999 ) . uniqid( '', true ) . get_option( 'siteurl' ) );
		$this->defend_wp_firewall_options->set_option( 'dfwp_activation_key', $key );
		return $key;
	}

	public function defend_wp_login_success( $email ) {
		$result = $this->join_email( $email );
	}

	public function dfwp_firewall_sync_firewall() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		$result = $this->sync_site();

		if ( ! is_array( $result ) ) {
			if ( $result == true ) {
				defend_wp_firewall_die_with_json_encode_simple(
					array(
						'success' => true,
					)
				);
			}
		}

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => false,
				'result'  => $result,
			)
		);
	}

	public function system_run_log( $data ) {
		$recent_logs = array();

		$get_recent_log = $this->defend_wp_firewall_options->get_option( 'dfwp_request_log' );

		if ( ! empty( $get_recent_log ) ) {
			$recent_logs = json_decode( $get_recent_log );
		}

		if ( count( $recent_logs ) >= 10 ) {
			array_shift( $recent_logs );
		}

		array_push( $recent_logs, $data );

		$this->defend_wp_firewall_options->set_option( 'dfwp_request_log', wp_json_encode( $recent_logs ), true );
	}

	public function dfwp_firewall_revoke_connect_firewall() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		$this->revoke();
	}

	private function revoke() {
		$this->defend_wp_firewall_options->delete_option( 'dfwp_pub_key' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_send_ptc_update' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_activation_key' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_firewall' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_firewall_last_sync' );
		$this->defend_wp_firewall_options->delete_option( 'dfwp_send_ptc_update_init_time' );
	}

	public function check_send_ptc_update() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_verify_ajax_requests( false ); // This function handles nonce verification

		if ( $this->is_connected() && $this->can_send_ptc_update() ) {
			$this->defend_wp_firewall_options->delete_option( 'dfwp_send_ptc_update_init_time' );
			$this->send_ptc_details_server();
		}

         // phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public function process_iwp_request() {
		if ( ! function_exists( 'iwp_mmb_set_request' ) ) {
			return;
		}
		global $iwp_mmb_core;

		if ( ! is_object( $iwp_mmb_core ) && empty( $iwp_mmb_core->request_params ) ) {
			return;
		}
		if ( empty( $iwp_mmb_core->request_params ) ) {
			return;
		}

		if ( $iwp_mmb_core->request_params['iwp_action'] === 'defendwp_activate' ) {
			remove_action( 'init', 'iwp_mmb_plugin_actions', 99999 );

			if ( $this->is_connected() ) {
				iwp_mmb_response(
					array(
						'success' => 'Site successfully protected with DefendWP.',
						'code'    => 'already_installed',
					),
					true
				);
			}
			if ( ! empty( $iwp_mmb_core->request_params['isBulkAction'] ) ) {
				$this->sendBulkIWPAddSiteDetails();
			} else {
				return $this->directIWPAddSite();
			}
		} elseif ( $iwp_mmb_core->request_params['iwp_action'] === 'defendwp_fetch_log' ) {
			remove_action( 'init', 'iwp_mmb_plugin_actions', 99999 );

			if ( ! $this->is_connected() ) {
				iwp_mmb_response(
					array(
						'error' => 'Site not connected to DefendWP.',
						'code'  => 'iwp_mmb_defendwp_site_not_connected',
					),
					true
				);
			}

			$log      = new Defend_WP_Firewall_Logs();
			$limit    = $iwp_mmb_core->request_params['limit'] ?? 5;
			$limit    = intval( $limit );
			$all_logs = $log->get_all_logs( '', $limit );

			iwp_mmb_response(
				array(
					'success'  => 'Site logs.',
					'all_logs' => $all_logs,
				),
				true
			);

		}
	}

	private function directIWPAddSite() {
		$result = $this->add_site();
		if ( ! is_array( $result ) ) {
			if ( $result == false ) {

				iwp_mmb_response(
					array(
						'error'      => 'Unable to save activation key.',
						'error_code' => 'iwp_mmb_defendwp_unable_to_save_activation_key',
					),
					false
				);
				return;

			} elseif ( $result == true ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_send_ptc_update', 'yes' );
				if ( ! empty( $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' ) ) ) {
					iwp_mmb_response(
						array(
							'success' => 'Site successfully protected with DefendWP.',
							'code'    => 'already_installed',
						),
						true
					);
					return;
				}
			}
		}

		iwp_mmb_response(
			array(
				'error'      => $result['error_msg'] . '<br>' . $result['res_desc'],
				'error_code' => 'iwp_mmb_defendwp_unable_to_connect',
			),
			false
		);
	}

	private function sendBulkIWPAddSiteDetails() {
		$params = array();
		$urls   = $this->collect_urls();

		$params['activation_key'] = $this->get_activation_key();
		$params['is_openssl']     = defend_wp_firewall_check_openssl();
		$params                   = array_merge( $params, $urls );
		if ( is_array( $params ) ) {
			iwp_mmb_response(
				array(
					'success'       => 'Site prepared for activation.',
					'site_detaials' => $params,
					'code'          => 'bulk_install',
				),
				true
			);
			return;
		}

		iwp_mmb_response(
			array(
				'error'      => 'Unable to create activation.',
				'error_code' => 'iwp_mmb_defendwp_unable_to_connect',
			),
			false
		);
	}
}
