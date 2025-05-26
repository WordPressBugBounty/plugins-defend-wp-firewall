<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Login_Functions_Hooks {
	private $login_object;

	public function __construct() {
		$this->login_object = new Defend_WP_Firewall_Login_Functions();
	}
	public function define_hooks() {
		add_filter( 'secure_auth_cookie', array( $this->login_object, 'secure_auth_cookie' ), 10, 2 );
		add_action( 'init', array( $this->login_object, 'wp_logout' ), 10, 2 );
		add_action( 'defend_wp_firewall_matched_rule_action', array( $this->login_object, 'defend_wp_firewall_matched_rule_action' ) );
	}
}
