<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Htaccess_Functions_Hooks {
	private $defend_htaccess_functions;

	public function __construct() {
		$this->defend_htaccess_functions = new Defend_WP_Firewall_Htaccess_Functions();
	}

	public function define_hooks() {
		add_action( 'defend_wp_firewall_setttings_updated', array( $this, 'defend_wp_firewall_setttings_updated' ), 11 );
	}

	public function defend_wp_firewall_setttings_updated() {
		$this->defend_htaccess_functions->process_htaccess();
	}
}
