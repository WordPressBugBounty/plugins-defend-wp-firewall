<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Shortcode_Functions_Hooks {
	private $shortcode;

	public function __construct() {
		$this->shortcode = new Defend_WP_Shortcode_Functions();
	}

	public function define_hooks() {
		add_filter( 'defend_wp_firewall_matched_rule', array( $this->shortcode, 'defend_wp_firewall_matched_rule' ), 10, 5 );
		add_filter( 'pre_do_shortcode_tag', array( $this->shortcode, 'pre_do_shortcode_tag' ), 10, 4 );
		add_action( 'defend_wp_firewall_shortcode_rules', array( $this->shortcode, 'defend_wp_firewall_shortcode_rules' ), 10, 5 );
	}
}
