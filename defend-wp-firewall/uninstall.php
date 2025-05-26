<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

global $wpdb;

$tables_to_drop = array(
	'dfwp_options',
	'dfwp_logs',
	'dfwp_whitelist',
	'dfwp_blacklist',
	'dfwp_activation_key',
	'dfwp_firewall',
	'dfwp_pub_key',
	'dfwp_send_ptc_update',
);

foreach ( $tables_to_drop as $key => $value ) {
	$table_name = $wpdb->base_prefix . $value;
	$result     = $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i;', $table_name ) );
}

$timestamp = wp_next_scheduled( 'defend_wp_firewall_cron_hook' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'defend_wp_firewall_cron_hook' );
}
wp_clear_scheduled_hook( 'defend_wp_firewall_daily_auto_update' );
