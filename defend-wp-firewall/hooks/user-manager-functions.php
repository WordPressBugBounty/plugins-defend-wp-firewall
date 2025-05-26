<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_User_Manager_Functions_Hooks {
	private $user_manager;

	public function __construct() {
		$this->user_manager = new Defend_WP_User_Manager_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_matched_rule_action', array( $this->user_manager, 'defend_wp_matched_rules' ), 10, 5 );
		add_action( 'delete_user', array( $this->user_manager, 'delete_user' ), 10, 3 );
	}
}
