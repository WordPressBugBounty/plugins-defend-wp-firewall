<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Remove_Action_Filter_Functions_Hooks {
	private $remove_action_filter_manager;

	public function __construct() {
		$this->remove_action_filter_manager = new Defend_WP_Firewall_Remove_Action_Filter_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_remove_action', array( $this->remove_action_filter_manager, 'defend_wp_firewall_remove_action' ), 10, 5 );
		add_action( 'defend_wp_firewall_remove_filter', array( $this->remove_action_filter_manager, 'defend_wp_firewall_remove_filter' ), 10, 5 );

		add_action( 'init', array( $this->remove_action_filter_manager, 'process_remove_action_filter_rules' ) );
	}
}
