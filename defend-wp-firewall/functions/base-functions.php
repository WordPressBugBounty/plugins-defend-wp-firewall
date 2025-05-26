<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Base_Functions {
	public $defend_wp_firewall_options;
	public $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
	}

	public function plugin_installed_init() {
		// This will run during next update.
		$this->handle_dwp_plugin_update();

		$this->check_db_install_and_upgrade();

		$this->set_defend_wp_firewall_globals();

		new Defend_WP_Firewall_Service();
	}

	public function create_fresh_tables() {
		global $wpdb;

		$table_name = $wpdb->base_prefix . 'dfwp_options';

		$query = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`name` varchar(50) NOT NULL PRIMARY KEY,
			`value` text NOT NULL,
			UNIQUE KEY `name` (`name`)
		) COLLATE 'utf8mb4_general_ci' ;";

		$result = $wpdb->query(
			$wpdb->prepare(
				"CREATE TABLE IF NOT EXISTS %i (
			`name` varchar(50) NOT NULL PRIMARY KEY,
			`value` text NOT NULL,
			UNIQUE KEY `name` (`name`)
		) COLLATE 'utf8mb4_general_ci' ;",
				$table_name
			)
		);

		if ( empty( $result ) ) {
			defend_wp_firewall_log( $query, '---create_fresh_tables--query-----' );
			defend_wp_firewall_log( $wpdb->last_error, '--------create table error------' );
		}

		$table_name = $wpdb->base_prefix . 'dfwp_logs';

		$query = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
			`firewall_id` varchar(190) NULL,
			`title` text NOT NULL,
			`message` text NOT NULL,
			`source_url` text NULL,
			`source_ip` text(100) NULL,
			`extra` text NOT NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL,
            `data_collected` enum('0','1') DEFAULT '0'
		  ) COLLATE 'utf8mb4_general_ci';";

		$result = $wpdb->query(
			$wpdb->prepare(
				"CREATE TABLE IF NOT EXISTS %i (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
            `firewall_id` varchar(190) NULL,
			`title` text NOT NULL,
			`message` text NOT NULL,
			`source_url` text NULL,
			`source_ip` text(100) NULL,
			`extra` text NOT NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL,
            `data_collected` enum('0','1') DEFAULT '0'
		  ) COLLATE 'utf8mb4_general_ci';",
				$table_name
			)
		);

		if ( empty( $result ) ) {
			defend_wp_firewall_log( $query, '---create_fresh_tables--query-----' );
			defend_wp_firewall_log( $wpdb->last_error, '--------create table error------' );
		}

		$table_name = $wpdb->base_prefix . 'dfwp_whitelist';

		$query = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
			`action` varchar(190) NOT NULL,
			`value` text NOT NULL,
			`extra` text NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL
		) COLLATE 'utf8mb4_general_ci';";

		$result = $wpdb->query(
			$wpdb->prepare(
				"CREATE TABLE IF NOT EXISTS %i (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
			`action` varchar(190) NOT NULL,
			`value` text NOT NULL,
			`extra` text NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL
		) COLLATE 'utf8mb4_general_ci';",
				$table_name
			)
		);

		if ( empty( $result ) ) {
			defend_wp_firewall_log( $query, '---create_fresh_tables--query-----' );
			defend_wp_firewall_log( $wpdb->last_error, '--------create table error------' );
		}

		$table_name = $wpdb->base_prefix . 'dfwp_blacklist';

		$query = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
			`action` varchar(190) NOT NULL,
			`value` text NOT NULL,
			`extra` text NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL
			) COLLATE 'utf8mb4_general_ci';";

		$result = $wpdb->query(
			$wpdb->prepare(
				"CREATE TABLE IF NOT EXISTS %i (
			`id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`type` varchar(190) NOT NULL,
			`action` varchar(190) NOT NULL,
			`value` text NOT NULL,
			`extra` text NULL,
			`ts` bigint(20) NOT NULL,
			`hr_time` varchar(150) NOT NULL
			) COLLATE 'utf8mb4_general_ci';",
				$table_name
			)
		);

		if ( empty( $result ) ) {
			defend_wp_firewall_log( $query, '---create_fresh_tables--query-----' );
			defend_wp_firewall_log( $wpdb->last_error, '--------create table error------' );
		}
	}

	public function defend_wp_firewall_get_collation() {
		if ( method_exists( $this->wpdb, 'get_charset_collate' ) ) {
			$charset_collate = $this->wpdb->get_charset_collate();
		}

		return ! empty( $charset_collate ) ? $charset_collate : ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ';
	}

	public function handle_dwp_plugin_update() {
		// Only perform direct DB update here.
		global $wpdb;
		$table_name     = $wpdb->base_prefix . 'dfwp_options';
		$query          = $wpdb->esc_like( $table_name ) . '%';
		$is_table_exist = $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', $query ) );

		if ( ! $is_table_exist ) {

			return;
		}

		$old_version = $this->defend_wp_firewall_options->get_option( 'DEFEND_WP_FIREWALL_VERSION' );

		if ( empty( $old_version ) ) {

			return;
		}

		if ( version_compare( $old_version, DEFEND_WP_FIREWALL_VERSION, '<' ) ) {

			defend_wp_firewall_log( '', '----running-----everytime_dwp_plugin_is_updated-----------------' );

			$this->defend_wp_firewall_options->set_option( 'dfwps_last_check', 10 );
		}
	}

	public function check_db_install_and_upgrade() {
		global $wpdb;
		$table_name     = $wpdb->base_prefix . 'dfwp_options';
		$query          = $wpdb->esc_like( $table_name ) . '%';
		$is_table_exist = $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', $query ) );

		if ( ! $is_table_exist ) {

			include_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$this->create_fresh_tables();

			// Setting default values after first time install.
			$this->defend_wp_firewall_options->set_option( 'enable_dfwp_firewall', 'yes' );
			$this->defend_wp_firewall_options->set_option( 'enable_defendwp_nonce', 'yes' );
			$this->defend_wp_firewall_options->set_option( 'enable_sanitize_request', 'yes' );
		}
	}

	public function set_defend_wp_firewall_globals() {
		$this->defend_wp_firewall_options->set_option( 'DEFEND_WP_FIREWALL_VERSION', DEFEND_WP_FIREWALL_VERSION );
		if ( isset( $_GET['dfwp_join'] ) && $_GET['dfwp_join'] === 'later' ) {
			$this->defend_wp_firewall_options->set_option( 'defend_wp_join_later', 'yes' );
			set_transient( 'defend_wp_firewall_setting_redirect_on_activation', true, 30 );
		}
		$upload_dir_meta = wp_upload_dir();

		$enc_site_url    = base64_encode( get_home_url() );
		$enc_admin_url   = base64_encode( network_admin_url() );
		$enc_uploads_url = base64_encode( $upload_dir_meta['baseurl'] );

		$this->defend_wp_firewall_options->set_option( 'defend_wp_firewall_url', get_home_url(), true );
		$this->defend_wp_firewall_options->set_option( 'defend_wp_firewall_url_enc', $enc_site_url, true );
		$this->defend_wp_firewall_options->set_option( 'defend_wp_firewall_admin_url_enc', $enc_admin_url, true );
		$this->defend_wp_firewall_options->set_option( 'defend_wp_firewall_uploads_url_enc', $enc_uploads_url, true );

		$this->defend_wp_firewall_options->set_option( 'initial_flags_set', 1 );

		$ip_site_unique_id_dfwp = $this->defend_wp_firewall_options->get_option( 'ip_site_unique_id_dfwp' );
		if ( empty( $ip_site_unique_id_dfwp ) ) {
			$new_unique_id = hash( 'sha256', uniqid() );
			$this->defend_wp_firewall_options->set_option( 'ip_site_unique_id_dfwp', $new_unique_id );
		}

		global $defend_wp_firewall_all_configs;
		$defend_wp_firewall_all_configs                        = $this->defend_wp_firewall_options->get_all_configs();
		$defend_wp_firewall_all_configs['dfwp_firewall_rules'] = false;
	}
}
