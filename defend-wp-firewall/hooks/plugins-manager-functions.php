<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Plugins_Manager_Functions_Hooks {
	private $plugins_manager;

	public function __construct() {
		$this->plugins_manager = new Defend_WP_Firewall_Plugins_Manager_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_deactivate_plugin', array( $this->plugins_manager, 'defend_wp_firewall_deactivate_plugin' ), 10, 5 );
		add_action( 'defend_wp_firewall_after_firewall_run', array( $this->plugins_manager, 'check_and_deactivate' ), 10 );
	}
}
