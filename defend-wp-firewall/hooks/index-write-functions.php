<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Index_Write_Functions_Hooks {
	private $defend_index_write_functions;

	public function __construct() {
		$this->defend_index_write_functions = new Defend_WP_Firewall_Index_Write_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_set_rules', array( $this->defend_index_write_functions, 'set_flag' ), 11 );
		add_action( 'init', array( $this->defend_index_write_functions, 'process_flag' ), 12 );
		add_action( 'defend_wp_firewall_index_write', array( $this->defend_index_write_functions, 'process_index_rules' ), 10 );
	}
}
