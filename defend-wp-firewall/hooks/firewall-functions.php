<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Functions_Hooks {
	private $defend_wp_firewall_firewall;

	public function __construct() {
		$this->defend_wp_firewall_firewall = new Defend_WP_Firewall_Functions();
	}

	public function define_hooks() {
		add_filter( 'xmlrpc_enabled', array( $this, 'block_xml_rpc' ) );
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
		add_action( 'defend_wp_firewall_request_after_run_all_rules', array( $this->defend_wp_firewall_firewall, 'defend_wp_firewall_request_after_run_all_rules' ), 10 );
		add_filter( 'defend_wp_firewall_matched_rule', array( $this->defend_wp_firewall_firewall, 'defend_wp_firewall_matched_rule' ), 10, 1 );
	}

	public function init() {
		$this->defend_wp_firewall_firewall->process_request( 'init' );
		$this->defend_wp_firewall_firewall->process_incoming_new_rules();
		$this->defend_wp_firewall_firewall->process_verify_add_site();
		$this->defend_wp_firewall_firewall->process_verify_sync_site();
	}

	public function plugins_loaded() {
		$this->defend_wp_firewall_firewall->process_request( 'plugins_loaded' );
	}

	public function block_xml_rpc() {
		return $this->defend_wp_firewall_firewall->block_xml_rpc();
	}
}
