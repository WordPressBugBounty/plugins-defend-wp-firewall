<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Defend_WP_Firewall_Add_Action_Filter_Functions_Hooks {
	private $add_action_filter_manager;

	public function __construct() {
		$this->add_action_filter_manager = new Defend_WP_Firewall_Add_Action_Filter_Functions();
	}
	public function define_hooks() {
		add_action( 'defend_wp_firewall_add_filter', array( $this->add_action_filter_manager, 'add_filter' ), 10, 5 );
		add_action( 'defend_wp_firewall_add_action', array( $this->add_action_filter_manager, 'add_action' ), 10, 5 );
	}
}
