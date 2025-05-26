<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Run_Functions_Hooks {
	private $run_functions_manager;

	public function __construct() {
		$this->run_functions_manager = new Defend_WP_Firewall_Run_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_run', array( $this->run_functions_manager, 'defend_wp_firewall_run' ), 10, 5 );

		add_action( 'init', array( $this->run_functions_manager, 'process_always_run_functions' ) );

		add_action( 'defend_wp_firewall_matched_rule_action', array( $this->run_functions_manager, 'register_run_functions' ), 10, 2 );
		add_action( 'defend_wp_firewall_after_firewall_run', array( $this->run_functions_manager, 'run_functions' ), 10 );
	}
}
