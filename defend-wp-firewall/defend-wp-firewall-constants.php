<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `Defend_WP_Firewall_Constants` class in PHP initializes various components and constants for a
 * live plugin.
 */
class Defend_WP_Firewall_Constants {

	/**
	 * The function `init_live_plugin` initializes various components of a live plugin in PHP.
	 */
	public function init_live_plugin() {
		$this->path();
		$this->set_env();
		$this->general();
		$this->versions();
		$this->debug();
		$this->set_mode();
	}
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function path() {

		$this->define( 'DEFEND_WP_FIREWALL_ABSPATH', wp_normalize_path( ABSPATH ) );
		$this->define( 'DEFEND_WP_FIREWALL_RELATIVE_ABSPATH', '/' );
		$this->define( 'DEFEND_WP_FIREWALL_WP_CONTENT_DIR', wp_normalize_path( WP_CONTENT_DIR ) );
		$this->define( 'DEFEND_WP_FIREWALL_WP_CONTENT_BASENAME', basename( DEFEND_WP_FIREWALL_WP_CONTENT_DIR ) );
		$this->define( 'DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR', '/' . DEFEND_WP_FIREWALL_WP_CONTENT_BASENAME );

		// Before modifying these, think about existing users
		$this->define( 'DEFEND_WP_FIREWALL_TEMP_DIR_BASENAME', 'defend_wp' );

		$plugin_dir_path = wp_normalize_path( plugin_dir_path( __FILE__ ) );
		$this->define( 'DEFEND_WP_FIREWALL_RELATIVE_PLUGIN_DIR', str_replace( DEFEND_WP_FIREWALL_ABSPATH, DEFEND_WP_FIREWALL_RELATIVE_ABSPATH, $plugin_dir_path ) );
		$this->define( 'DEFEND_WP_FIREWALL_PLUGIN_DIR', $plugin_dir_path );

		$uploads_meta = wp_upload_dir();
		$basedir_path = wp_normalize_path( $uploads_meta['basedir'] );
		$this->define( 'DEFEND_WP_FIREWALL_RELATIVE_UPLOADS_DIR', str_replace( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/', DEFEND_WP_FIREWALL_RELATIVE_ABSPATH, $basedir_path ) );
		$this->define( 'DEFEND_WP_FIREWALL_UPLOADS_DIR', $basedir_path );
		$this->define( 'DEFEND_WP_FIREWALL_PLUGIN_URL', plugin_dir_url( DEFEND_WP_FIREWALL_MAIN_FILE ) );
		$this->define( 'DEFEND_WP_FIREWALL_RULES_PLACEHOLDER', '|replace|' );
	}

	public function set_env( $type = false ) {
		$path = ( $type === 'bridge' ) ? '' : DEFEND_WP_FIREWALL_PLUGIN_DIR;

		if ( file_exists( $path . 'defend-wp-firewall-env.php' ) ) {
			include_once $path . 'defend-wp-firewall-env.php';
		}

		$this->define( 'DEFEND_WP_FIREWALL_ENV', 'production' );
	}

	public function set_mode() {
		switch ( DEFEND_WP_FIREWALL_ENV ) {
			case 'production':
				$this->production_mode();
				break;
			case 'staging':
				$this->production_mode();
				break;
			case 'local':
			default:
				$this->production_mode();
		}
	}

	public function debug() {
		$this->define( 'DEFEND_WP_FIREWALL_DEBUG', false );
	}

	public function versions() {
		$this->define( 'DEFEND_WP_FIREWALL_VERSION', '1.1.5' );
		$this->define( 'DEFEND_WP_FIREWALL_DATABASE_VERSION', '1.0' );
	}

	public function general() {

		$this->define( 'DEFEND_WP_FIREWALL_MINUMUM_PHP_VERSION', '8.0' );
		$this->define( 'DEFEND_WP_FIREWALL_NO_ACTIVITY_WAIT_TIME', 60 ); // 5 mins to allow for socket timeouts and long uploads
		$this->define( 'DEFEND_WP_FIREWALL_PLUGIN_PREFIX', 'defend_wp' );
		$this->define( 'DEFEND_WP_FIREWALL_PLUGIN_NAME', 'DefendWP' );
		$this->define( 'DEFEND_WP_FIREWALL_PLUGIN_SLUG', 'defend-wp-firewall' );
		$this->define( 'DEFEND_WP_FIREWALL_TIMEOUT', 23 );
		$this->define( 'DEFEND_WP_FIREWALL_POST_META_BRUTE_FORCE_THRESHOLD', 900 );
		$this->define( 'DEFEND_WP_FIREWALL_LOGS_MAX_NUM_OF_LOGS', 1000 );
		$this->define( 'DEFEND_WP_FIREWALL_RULES_VERSION', '1.0.0' );

		$this->define( 'DEFEND_WP_FIREWALL_VALIDATE_FREQUENCY', 86400 );
		$this->define( 'DEFEND_WP_FIREWALL_SERVICE_URL', 'https://cron.defendwp.org' );
		$this->define( 'DEFEND_WP_FIREWALL_LIMIT_LOGIN_TRIES_COUNT', 15 );
		$this->define( 'DEFEND_WP_FIREWALL_SETTINGS_PAGE_URL', ( admin_url( 'admin.php?page=dfwp_settings' ) ) );
		$this->define( 'DEFEND_WP_FIREWALL_LATER_URL', ( admin_url( 'admin.php?page=dfwp_settings&dfwp_join=later' ) ) );
	}

	public function production_mode() {
		$this->define( 'DEFEND_WP_FIREWALL_CURL_TIMEOUT', 20 );

		$this->define( 'DEFEND_WP_FIREWALL_COOKIE_EXP_TIME', ( 3600 * 12 * 30 ) );
	}
}
