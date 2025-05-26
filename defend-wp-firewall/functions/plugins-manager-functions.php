<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Plugins_Manager_Functions {

	private $deactivate_plugin_rules = array();

	public function defend_wp_firewall_deactivate_plugin( $rule ) {
		if ( ! empty( $this->deactivate_plugin_rules[ $rule['slug'] ] ) ) {
			return;
		}
		if ( ! empty( $rule['options']['deactivate_plugin'] ) ) {
			$this->deactivate_plugin_rules[ $rule['slug'] ]       = $rule['options']['deactivate_plugin'];
			$this->deactivate_plugin_rules[ $rule['slug'] ]['id'] = $rule['id'];
		}
	}

	public function check_and_deactivate() {
		if ( empty( $this->deactivate_plugin_rules ) ) {
			return;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active_plugins = get_option( 'active_plugins' );
		foreach ( $this->deactivate_plugin_rules as $slug => $value ) {
			if ( empty( $value['versions'] ) ) {
				continue;
			}
			if ( in_array( $slug, $active_plugins ) == false ) {
				continue;
			}
			$plugin_details = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug );

			if ( empty( $plugin_details ) ) {
				continue;
			}

			foreach ( $value['versions'] as $version ) {
				if ( version_compare( $plugin_details['Version'], $version, '<=' ) ) {
					$this->deactivate_plugin( $slug, $value['id'] );
					return;
				}
			}
		}
	}

	private function deactivate_plugin( $slug, $firewall_id ) {
		global $defend_wp_firewall_is_ALL_whitelisted_globally;
		$duplicate                                      = $defend_wp_firewall_is_ALL_whitelisted_globally;
		$defend_wp_firewall_is_ALL_whitelisted_globally = 'yes';
		defend_wp_firewall_die(
			array(
				'type'    => 'deactivate_plugin',
				'title'   => 'Firewall Plugin Deactivation (ID #' . ( $firewall_id ) . ')',
				'message' => 'Slug ' . $slug,
				'extra'   => array( 'more_details' => array( 'slug' => $slug ) ),
			),
			true,
			false
		);
		deactivate_plugins( $slug );
		$defend_wp_firewall_is_ALL_whitelisted_globally = $duplicate;
	}
}
