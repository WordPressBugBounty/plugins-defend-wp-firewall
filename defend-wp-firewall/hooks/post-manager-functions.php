<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Post_Manager_Functions_Hooks {
	private $post_manager;

	public function __construct() {
		$this->post_manager = new Defend_WP_Post_Manager_Functions();
	}

	public function define_hooks() {
		add_filter( 'pre_delete_post', array( $this->post_manager, 'pre_delete_post' ), 10, 3 );
		add_action( 'defend_wp_firewall_matched_rule_action', array( $this->post_manager, 'defend_wp_matched_rules' ), 10, 5 );
		add_filter( 'get_post_metadata', array( $this->post_manager, 'get_post_metadata' ), 10, 5 );
		add_action( 'defend_wp_firewall_wp_post_restrictions', array( $this->post_manager, 'wp_post_restrictions' ), 10 );
	}
}
