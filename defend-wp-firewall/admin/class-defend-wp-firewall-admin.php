<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://revmakx.com
 * @since      1.0.0
 *
 * @package    Defend_WP
 * @subpackage Defend_WP/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Defend_WP
 * @subpackage Defend_WP/admin
 * @author     DefendWP <mohamed@revmakx.com>
 */
class Defend_WP_Firewall_Admin {

	/**
	 * Varaible to store global wpdb
	 *
	 * @var mixed
	 */
	public $wpdb;

	/**
	 * Varaible to store Defend_WP_Firewall_Settings class object
	 *
	 * @var mixed
	 */
	public $settings;

	/**
	 * The function initializes the WordPress database object and creates a new instance of the
	 * Defend_WP_Firewall_Settings class.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb     = $wpdb;
		$this->settings = new Defend_WP_Firewall_Settings();
	}

	/**
	 * The function "init" defines hooks in a PHP class.
	 *
	 * @return void
	 */
	public function init() {
		$this->define_hooks();
	}

	/**
	 * The `define_hooks` function in PHP defines various action hooks for different events in WordPress
	 * administration and AJAX requests.
	 */
	public function define_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( defined( 'MULTISITE' ) && MULTISITE ) {
			add_action( 'network_admin_menu', array( $this, 'register_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}

		add_action( 'wp_ajax_load_more_logs_dwp', array( $this, 'load_more_logs_dwp' ) );
		add_action( 'wp_ajax_clear_all_logs_dwp', array( $this, 'clear_all_logs_dwp' ) );

		add_action( 'defend_wp_firewall_setttings_updated_before_send_response', array( $this, 'clear_cache_on_setting_save' ), 10000, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_ajax_dfwp_dismiss_cache_admin_notice', array( $this, 'dfwp_dismiss_cache_admin_notice' ) );
		add_action( 'defend_wp_firewall_before_setting_start', array( $this, 'check_redirect_login' ) );
		add_action( 'defend_wp_before_login_page_start', array( $this, 'check_redirect_login' ) );
		add_action( 'admin_init', array( $this, 'setting_page_redirect_on_activation' ) );
		add_filter( 'iwp_mmb_stats_filter', array( $this, 'inject_defendwp_data' ), 10, 1 );
	}

	/**
	 * The function `enqueue_scripts` is used to enqueue necessary scripts for the DEFEND WP FIREWALL
	 * plugin in WordPress, including localization and checking for a specific configuration.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-dialog' );

		wp_enqueue_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'js/defend-wp-firewall-admin.js', array( 'jquery' ), DEFEND_WP_FIREWALL_VERSION, false );
		global $defend_wp_firewall_all_configs;

		if ( strpos( $hook, 'page_dfwp_settings' ) !== false || strpos( $hook, 'page_dfwp_logs' ) !== false ) {
			wp_register_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-tailwindcss', 'https://cdn.tailwindcss.com', array( 'jquery' ), DEFEND_WP_FIREWALL_VERSION, false );
			wp_enqueue_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-tailwindcss' );

			wp_enqueue_style( DEFEND_WP_FIREWALL_PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'css/defend-wp-firewall-admin.css', array(), DEFEND_WP_FIREWALL_VERSION, false );
		}

		$is_connected = ! empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) ? '1' : '0';
		wp_localize_script(
			DEFEND_WP_FIREWALL_PLUGIN_SLUG,
			'defend_wp_firewall_admin_obj',
			array(
				'security'     => wp_create_nonce( 'dwp_firewall_revmakx' ),
				'is_connected' => $is_connected,
			)
		);
	}

	/**
	 * The function `register_menu` checks if the WordPress installation is a multisite and then calls
	 * specific methods accordingly to defend firewall logs and settings pages.
	 */
	public function register_menu() {
		if ( defined( 'MULTISITE' ) && MULTISITE ) {
			$this->defend_wp_firewall_logs_page();
		} else {
			$this->defend_wp_firewall_logs_page();
			$this->defend_wp_firewall_settings_page();
		}
	}


	/**
	 * The function `defend_wp_firewall_logs_page` adds a menu page and a submenu page for managing
	 * DefendWP firewall logs in WordPress.
	 */
	public function defend_wp_firewall_logs_page() {
		$menu_name = apply_filters( 'dfwp_menu_name', 'DefendWP' );
		add_menu_page(
			'DefendWP',
			$menu_name,
			'activate_plugins',
			'dfwp_logs',
			array( $this, 'load_log_page' ),
			'dashicons-shield',
			65
		);

		add_submenu_page( 'dfwp_logs', 'Blocked Requests', 'Blocked Requests', 'activate_plugins', 'dfwp_logs', array( $this, 'load_log_page' ) );
	}

	/**
	 * The function `load_setting_page` includes and displays the settings page for the Defend WP Firewall
	 * plugin.
	 */
	public function load_setting_page() {
		include plugin_dir_path( __FILE__ ) . 'views/defend-wp-firewall-settings-display.php';
	}

	/**
	 * The function `defend_wp_firewall_settings_page` adds a submenu page for settings in WordPress.
	 */
	public function defend_wp_firewall_settings_page() {
		add_submenu_page(
			'dfwp_logs',
			'Settings',
			'Settings',
			'activate_plugins',
			'dfwp_settings',
			array( $this, 'load_setting_page' ),
			65
		);
	}

	/**
	 * The function `load_log_page` includes and displays the 'defend-wp-firewall-logs-display.php' view
	 * file.
	 */
	public function load_log_page() {
		include plugin_dir_path( __FILE__ ) . 'views/defend-wp-firewall-logs-display.php';
	}

	/**
	 * The function `clear_all_logs_dwp` clears all logs in a WordPress firewall system and returns a
	 * success message in JSON format.
	 *
	 * @return `false`.
	 */
	public function clear_all_logs_dwp() {

		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification

		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$all_dwp_logs            = $defend_wp_firewall_logs->clear_all_logs();

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
			)
		);

		return false;
	}

	public function load_more_logs_dwp() {

		// phpcs:disable WordPress.Security.NonceVerification.Missing   
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification

		defend_wp_firewall_log( $_POST, '--------_POST----load_more_logs_dwp----' );
		if ( empty( $_POST ) || empty( $_POST['last_log_id'] ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'error' => 'Missing Log ID.',
				)
			);

			return false;
		}

		$last_log_id = intval( sanitize_text_field( wp_unslash( $_POST['last_log_id'] ) ) );
		if ( empty( $_POST['block_type'] ) ) {
			$block_type_dfwp_from_post = '';
		} else {
			$block_type_dfwp_from_post = sanitize_text_field( wp_unslash( $_POST['block_type'] ) );
		}
         // phpcs:enable WordPress.Security.NonceVerification.Missing

		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$all_dwp_logs            = $defend_wp_firewall_logs->get_all_logs_before_this_log_id( $last_log_id, $block_type_dfwp_from_post );

		if ( empty( $all_dwp_logs ) ) {
			defend_wp_firewall_die_with_json_encode_simple(
				array(
					'success' => true,
					'html'    => '',
				)
			);

			return false;
		}

		ob_start();
		include_once WP_PLUGIN_DIR . '/defend-wp-firewall/admin/views/defend-wp-firewall-log-rows-template.php';
		$log_rows_dwp_html = ob_get_clean();

		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'success' => true,
				'html'    => $log_rows_dwp_html,
			)
		);
	}

	/**
	 * The function int_cache_obj() requires a class file and returns a new instance of
	 * Defend_WP_Firewall_Purge_Cache.
	 *
	 * @return An instance of the `Defend_WP_Firewall_Purge_Cache` class is being returned.
	 */
	public function int_cache_obj() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-purge-plugins-cache.php';
		return new Defend_WP_Firewall_Purge_Cache();
	}

	/**
	 * The function admin_notices() calls the admin_notices() method of an instantiated cache object.
	 */
	public function admin_notices() {
		$cache_obj = $this->int_cache_obj();
		$cache_obj->admin_notices();
	}

	/**
	 * This PHP function calls a method to dismiss a cache admin notice.
	 */
	public function dfwp_dismiss_cache_admin_notice() {
		$cache_obj = $this->int_cache_obj();
		$cache_obj->dfwp_dismiss_cache_admin_notice();
	}

	/**
	 * This PHP function clears the cache when settings are saved.
	 *
	 * @param array $new_settings The `new_settings` parameter typically refers to the updated settings that have
	 * been saved by the user. These settings could include any configuration changes or preferences that
	 * the user has made within the application or system.
	 * @param array $old_settings The `old_settings` parameter typically refers to the previous settings before
	 * they were updated. It contains the values of the settings before any changes were made. This
	 * parameter is useful for comparing the old settings with the new settings to determine what has
	 * changed.
	 */
	public function clear_cache_on_setting_save( $new_settings, $old_settings ) {
		$cache_obj = $this->int_cache_obj();
		$cache_obj->clear_cache_on_setting_save( $new_settings, $old_settings );
	}

	/**
	 * The function `setting_page_redirect_on_activation` checks for a transient flag and redirects to a
	 * specific URL if the flag is set.
	 */
	public function setting_page_redirect_on_activation() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_transient( 'defend_wp_firewall_setting_redirect_on_activation' ) ) {
			return;
		}

		delete_transient( 'defend_wp_firewall_setting_redirect_on_activation' );

		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! function_exists( 'iwp_mmb_set_request' ) ) {
			return;
		}
		global $iwp_mmb_core;

		if ( is_object( $iwp_mmb_core ) && ! empty( $iwp_mmb_core->request_params ) ) {
			return;
		}

		wp_safe_redirect( DEFEND_WP_FIREWALL_SETTINGS_PAGE_URL );
	}

	/**
	 * The function `check_redirect_login` checks if a specific condition is met and includes a setup file
	 * if not.
	 *
	 * @return If the conditions in the `check_redirect_login` function are met, nothing will be returned.
	 * The function will simply exit without returning any value.
	 */
	public function check_redirect_login() {
		global $defend_wp_firewall_all_configs;
		$is_pro_activated = defend_wp_firewall_is_dfwp_pro_activated();
		if ( ! empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) && $is_pro_activated ) {
			return;
		}
		if ( empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) || ( empty( $defend_wp_firewall_all_configs['dfwp_join_email'] ) && empty( $defend_wp_firewall_all_configs['defend_wp_join_later'] ) ) ) {
			include_once __DIR__ . '/views/defend-wp-firewall-initial-setup.php';
			exit;
		}
	}

	public function allowed_post_tags() {
		return $this->settings->allowed_post_tags();
	}

	public function inject_defendwp_data( $stats ) {
		global $defend_wp_firewall_all_configs;
		if ( ! empty( $defend_wp_firewall_all_configs['dfwp_pub_key'] ) ) {
			$is_pro_activated  = defend_wp_firewall_is_dfwp_pro_activated();
			$stats['defendwp'] = array(
				'is_active'        => true,
				'is_pro_installed' => $is_pro_activated,
			);
		}
		return $stats;
	}
}
