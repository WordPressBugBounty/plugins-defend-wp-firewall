<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Nonce_Functions {

	public function __construct() {
	}

	public function load_assets() {
		global $defend_wp_firewall_all_configs;
		if ( ! empty( $defend_wp_firewall_all_configs['enable_defendwp_nonce'] ) && $defend_wp_firewall_all_configs['enable_defendwp_nonce'] === 'yes' ) {

			wp_enqueue_script( DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-nonce', DEFEND_WP_FIREWALL_PLUGIN_URL . 'hooks/js/nonce.js', array( 'jquery' ), time(), false );

			$defend_wp_firewall_nonce_obj = apply_filters(
				'defend_wp_firewall_nonce_obj',
				array(
					'defend_wp_firewall_nonce' => wp_create_nonce( 'defend-wp-firewall-nonce' ),
					'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				)
			);

			wp_localize_script(
				DEFEND_WP_FIREWALL_PLUGIN_SLUG . '-nonce',
				'defend_wp_firewall_nonce_obj',
				$defend_wp_firewall_nonce_obj
			);
		}
	}
}
