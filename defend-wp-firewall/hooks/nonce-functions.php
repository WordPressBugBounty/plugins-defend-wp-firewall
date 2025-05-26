<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Nonce_Functions_Hooks {
	private $defend_wp_nonce_functions;

	public function __construct() {
		$this->defend_wp_nonce_functions = new Defend_WP_Firewall_Nonce_Functions();
	}

	public function define_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 11 );
		add_action( 'login_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 11 );
		add_action( 'elementor/common/after_register_scripts', array( $this, 'wp_enqueue_scripts' ), 11 );
	}

	public function wp_enqueue_scripts() {
		$this->defend_wp_nonce_functions->load_assets();
	}
}
