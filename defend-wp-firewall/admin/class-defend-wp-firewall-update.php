<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Update {
	private $defend_wp_firewall_options;

	public function init() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$this->define_hooks();
	}

	private function define_hooks() {
		add_action( 'defend_wp_firewall_daily_auto_update', array( $this, 'auto_update' ) );
		add_action( 'defend_wp_firewall_setttings_updated', array( $this, 'update_setting' ) );
	}

	public function update_setting( $this_settings ) {
		if ( ! empty( $this_settings['enable_auto_update'] ) ) {
			if ( $this_settings['enable_auto_update'] === 'yes' ) {
				$this->enable_auto_update();
			} elseif ( $this_settings['enable_auto_update'] === 'no' ) {
				$this->disable_auto_update();
			}
		}
	}

	private function enable_auto_update() {
		wp_clear_scheduled_hook( 'defend_wp_firewall_daily_auto_update' );
		if ( is_main_site() ) {
			wp_schedule_event( time(), 'hourly', 'defend_wp_firewall_daily_auto_update' );
		}
	}

	private function disable_auto_update() {
		wp_clear_scheduled_hook( 'defend_wp_firewall_daily_auto_update' );
	}

	public function auto_update() {
		global $defend_wp_firewall_all_configs;
		if ( empty( $defend_wp_firewall_all_configs['enable_auto_update'] ) ) {
			return;
		}

		if ( $defend_wp_firewall_all_configs['enable_auto_update'] === 'no' ) {
			return;
		}

		$auto_update_log  = array();
		$auto_update_lock = $this->defend_wp_firewall_options->get_option( 'auto_update_lock' );
		if ( ! empty( $auto_update_lock ) ) {
			$auto_update_log['already_update_progress'] = 1;
			$this->auto_update_run_log( $auto_update_log );
			return;
		}

		if ( version_compare( $this->get_wp_version(), '5.5-x', '>=' ) ) {
			$auto_update_plugins = get_site_option( 'auto_update_plugins' );
			if ( is_array( $auto_update_plugins ) && in_array( DEFEND_WP_FIREWALL_BASENAME, $auto_update_plugins ) ) {
				$auto_update_log['wp_core_auto_update_enabled'] = 1;
				$this->auto_update_run_log( $auto_update_log );
				return;
			}
		}
		wp_update_plugins();
		$update_plugins = get_site_transient( 'update_plugins' );
		if ( empty( $update_plugins ) ) {
			$auto_update_log['update_not_available'] = 1;
			$this->auto_update_run_log( $auto_update_log );
			return;
		}

		if ( ! is_array( $update_plugins->response ) ) {
			return;
		}
		$is_update_available = false;
		if ( isset( $update_plugins->response[ DEFEND_WP_FIREWALL_BASENAME ] ) ) {
			$status = $update_plugins->response[ DEFEND_WP_FIREWALL_BASENAME ];
			if ( is_object( $status ) && property_exists( $status, 'new_version' ) && version_compare( $status->new_version, DEFEND_WP_FIREWALL_VERSION, '>' ) ) {
				$is_update_available = true;
			}
		}

		if ( $is_update_available === false ) {
			return;
		}

		try {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';

			if ( ! function_exists( 'wp_update_plugins' ) ) {
				include_once ABSPATH . 'wp-includes/update.php';
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				return;
			}
			$this->defend_wp_firewall_options->set_option( 'auto_update_lock', time() );
			ob_start();
			$upgrader = new Plugin_Upgrader();
			$response = $upgrader->upgrade( DEFEND_WP_FIREWALL_BASENAME );
			if ( $response ) {
				$cont = file_get_contents( DEFEND_WP_FIREWALL_MAIN_FILE );
				preg_match( '/Version:           (\d+\.\d+\.\d+)/', $cont, $matches );
				$version = ! empty( $matches ) ? $matches[1] : null;
				$this->defend_wp_firewall_options->set_option( 'last_auto_update_version', $version, true );
				$this->defend_wp_firewall_options->set_option( 'last_auto_update_complete', time() );
			}
			$output                             = @ob_get_contents();
			$auto_update_log['update_response'] = $output;
			$this->auto_update_run_log( $auto_update_log );
			@ob_end_clean();
			$this->defend_wp_firewall_options->delete_option( 'auto_update_lock' );
		} catch ( Exception $e ) {
			$auto_update_log['error_exception'] = $output;
			$this->auto_update_run_log( $auto_update_log );
			$this->defend_wp_firewall_options->delete_option( 'auto_update_lock' );
		}
	}

	private function get_wp_version( $forceRecheck = false ) {
		if ( $forceRecheck ) {
			require ABSPATH . 'wp-includes/version.php'; //defines $wp_version
			return $wp_version;
		}

		global $wp_version;
		return $wp_version;
	}

	public function auto_update_run_log( $data ) {
		$recent_logs    = array();
		$get_recent_log = $this->defend_wp_firewall_options->get_option( 'dfwp_auto_update_log' );

		if ( ! empty( $get_recent_log ) ) {
			$recent_logs = json_decode( $get_recent_log );
		}

		if ( count( $recent_logs ) >= 10 ) {
			array_shift( $recent_logs );
		}

		array_push( $recent_logs, $data );

		$this->defend_wp_firewall_options->set_option( 'dfwp_auto_update_log', wp_json_encode( $recent_logs ), true );
	}
}
