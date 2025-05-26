<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://revmakx.com
 * @since      1.0.0
 *
 * @package    Defend_WP
 * @subpackage Defend_WP/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Defend_WP
 * @subpackage Defend_WP/includes
 * @author     DefendWP <mohamed@revmakx.com>
 */
class Defend_WP_Firewall {

	protected $wpdb;

	protected $plugin_name;

	protected $version;

	protected $base_functions;

	protected $plugin_admin;
	protected $plugin_public;
	protected $plugin_update;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function run() {
		new Defend_WP_Firewall_Activation_Controller();

		$this->load_dependencies();

		$this->base_functions = new Defend_WP_Firewall_Base_Functions();
		$this->base_functions->plugin_installed_init();

		$this->set_locale();

		$this->plugin_admin = new Defend_WP_Firewall_Admin();
		$this->plugin_admin->init();

		$this->plugin_update = new Defend_WP_Firewall_Update();
		$this->plugin_update->init();

		do_action( 'defend_wp_firewall_pre_functions_load' );

		$this->initiate_hooks();
		$this->initiate_testing_hooks();
	}

	private function load_dependencies() {

		require_once plugin_dir_path( __DIR__ ) . 'includes/class-defend-wp-firewall-i18n.php';

		// Include all files here.
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-defend-wp-firewall-options.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/defend-wp-firewall-generic-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/defend-wp-firewall-custom-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-defend-wp-firewall-logs.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-defend-wp-firewall-ip-address.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-defend-wp-firewall-anonymous.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/base-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/cookie-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/blocklist-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/blocklist-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/whitelist-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/whitelist-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/nonce-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/nonce-functions.php';

		require_once plugin_dir_path( __DIR__ ) . 'hooks/firewall-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/firewall-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/htaccess-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/htaccess-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/shortcode-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/shortcode-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/plugins-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/plugins-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/post-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/post-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/user-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/user-manager-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/login-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/login-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/remove-action-filter.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/remove-action-filter.php';
		require_once plugin_dir_path( __DIR__ ) . 'hooks/run-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'functions/run-functions.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/class-defend-wp-firewall-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-defend-wp-firewall-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-defend-wp-firewall-service.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-defend-wp-firewall-update.php';

		if ( defined( 'DEFEND_WP_FIREWALL_TESTING' ) && DEFEND_WP_FIREWALL_TESTING ) {
			include_once plugin_dir_path( __DIR__ ) . 'tests/test-basic-functions.php';
			include_once plugin_dir_path( __DIR__ ) . 'tests/test-functions.php';
		}
		defend_wp_firewall_disable_hearbeat();
	}

	private function set_locale() {

		$plugin_i18n = new Defend_WP_Firewall_I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	public function initiate_hooks() {
		$defend_wp_firewall_firewall_functions = new Defend_WP_Firewall_Functions_Hooks();
		$defend_wp_firewall_firewall_functions->define_hooks();

		$defend_wp_blocklist_functions = new Defend_WP_Firewall_Blocklist_Functions_Hooks();
		$defend_wp_blocklist_functions->define_hooks();

		$defend_wp_login_functions = new Defend_WP_Firewall_Whitelist_Functions_Hooks();
		$defend_wp_login_functions->define_hooks();

		$defend_wp_nonce_functions = new Defend_WP_Firewall_Nonce_Functions_Hooks();
		$defend_wp_nonce_functions->define_hooks();

		$defend_shortcode_functions = new Defend_WP_Firewall_Shortcode_Functions_Hooks();
		$defend_shortcode_functions->define_hooks();

		$defend_plugin_functions = new Defend_WP_Firewall_Plugins_Manager_Functions_Hooks();
		$defend_plugin_functions->define_hooks();

		$defend_functions = new Defend_WP_Post_Manager_Functions_Hooks();
		$defend_functions->define_hooks();

		$defend_functions = new Defend_WP_Firewall_Login_Functions_Hooks();
		$defend_functions->define_hooks();

		$defend_functions = new Defend_WP_User_Manager_Functions_Hooks();
		$defend_functions->define_hooks();

		$defend_functions = new Defend_WP_Firewall_Remove_Action_Filter_Functions_Hooks();
		$defend_functions->define_hooks();

		$defend_functions = new Defend_WP_Firewall_Run_Functions_Hooks();
		$defend_functions->define_hooks();

		new Defend_WP_Firewall_Anonymous();

		if ( is_admin() ) {
			$defend_wp_firewall_htaccess_functions = new Defend_WP_Firewall_Htaccess_Functions_Hooks();
			$defend_wp_firewall_htaccess_functions->define_hooks();
		}
	}

	public function initiate_testing_hooks() {
		if ( ! defined( 'DEFEND_WP_FIREWALL_TESTING' ) || ! DEFEND_WP_FIREWALL_TESTING ) {

			return false;
		}

		$this_obj = new Defend_WP_Firewall_Test_Basic_Functions();
		$this_obj->define_hooks();

		$this_obj = new Defend_WP_Firewall_Test_Functions();
		$this_obj->define_hooks();
	}

	public function deactivate() {
		$defend_wp_firewall_options = new Defend_WP_Firewall_Options();
		$defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', false );
	}
	public function activation() {
		set_transient( 'defend_wp_firewall_setting_redirect_on_activation', true, 30 );
	}
}
