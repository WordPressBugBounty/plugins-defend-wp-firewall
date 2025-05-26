<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Activation_Controller {

	public function __construct() {
		add_action( 'init', array( $this, 'check_all_requirements' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'dfwp_setting_redirect_on_activation', array( $this, 'dfwp_setting_redirect_on_activation' ) );
	}

	public function check_all_requirements() {
		if ( class_exists( 'Defend_WP' ) === true && defined( 'DEFEND_WP_VERSION' ) && version_compare( DEFEND_WP_VERSION, '2.0.0', '<' ) ) {
			set_transient( 'defend_wp_firewall_defendwp_pro_not_min_version', true, 30 );
			return false;
		}
	}

	public function deactivate_self() {
		$plugin_slug = 'defend-wp/defend-wp.php';// Defend WP premium plugin needs to be deactivated to avoid fatal error.
		deactivate_plugins( $plugin_slug );
	}

	public function defend_wp_firewall_defendwp_pro_not_min_version() {
		echo '<div class="error"><p><strong>The \'DefendWP Pro\' plugin has been deactivated. It requires 2.0.0 verison. Please download and install. Click <a href="' . esc_url( DEFEND_WP_SITE_URL ) . 'my-account" target="_blank">here</a> to download</strong></p></div>';
	}

	public function admin_notices() {
		if ( get_transient( 'defend_wp_firewall_defendwp_pro_not_min_version' ) ) {
			$this->defend_wp_firewall_defendwp_pro_not_min_version();
			delete_transient( 'defend_wp_firewall_defendwp_pro_not_min_version' );
			$this->deactivate_self();
			return;
		}
	}

	public function dfwp_setting_redirect_on_activation( $is_redirect ) {
		if ( get_transient( 'defend_wp_firewall_defendwp_pro_not_min_version' ) ) {
			return false;
		}
		return $is_redirect;
	}
}
