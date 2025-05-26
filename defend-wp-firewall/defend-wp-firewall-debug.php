<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_shutdown_function( 'defend_wp_firewall_fatal_error_hadler' );
function defend_wp_firewall_fatal_error_hadler( $return = null ) {

	// reference http://php.net/manual/en/errorfunc.constants.php
	$log_error_types = array(
		1     => 'PHP Fatal error',
		2     => 'PHP Warning',
		4     => 'PHP Parse',
		8     => 'PHP Notice error',
		16    => 'PHP Core error',
		32    => 'PHP Core Warning',
		64    => 'PHP Core compile error',
		128   => 'PHP Core compile error',
		256   => 'PHP User error',
		512   => 'PHP User warning',
		1024  => 'PHP User notice',
		2048  => 'PHP Strict',
		4096  => 'PHP Recoverable error',
		8192  => 'PHP Deprecated error',
		16384 => 'PHP User deprecated',
		32767 => 'PHP All',
	);

	$last_error = error_get_last();

	if ( empty( $last_error ) && empty( $return ) ) {
		return;
	}

	if ( DEFEND_WP_FIREWALL_ENV === 'local' ) {
		if ( strstr( $last_error['file'], 'defend-wp-firewall' ) === false ) {
			return;
		}
	}

	if ( strpos( $last_error['message'], 'use the CURLFile class' ) !== false || strpos( $last_error['message'], 'Automatically populating' ) !== false ) {
		return;
	}

	if ( strpos( $last_error['file'], 'iwp-client' ) !== false || ! defined( 'DEFEND_WP_FIREWALL_DEBUG' ) || ! DEFEND_WP_FIREWALL_DEBUG ) {
		return;
	}

	if ( ! empty( $last_error['type'] ) && $last_error['type'] === 8192 ) {
		return;
	}
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-logs.txt', $log_error_types[ $last_error['type'] ] . ': ' . $last_error['message'] . ' in ' . $last_error['file'] . ' on line ' . $last_error['line'] . "\n", FILE_APPEND ); // This will be used only for internal debugging
}

function defend_wp_firewall_log( $value = null, $key = null, $is_print_all_time = true, $for_every = 0 ) {
	if ( ! defined( 'DEFEND_WP_FIREWALL_DEBUG' ) || ! DEFEND_WP_FIREWALL_DEBUG || ! $is_print_all_time ) {
		return;
	}

	if ( ! defined( 'DEFEND_WP_FIREWALL_WP_CONTENT_DIR' ) ) {
		define( 'DEFEND_WP_FIREWALL_WP_CONTENT_DIR', ABSPATH );
	}

	try {
		global $defend_wp_firewall_every_count;
		// $conditions = 'printOnly';

		$local_time = time() + ( 5.5 * 60 * 60 );
		$usr_time   = wp_date( 'Y-m-d H:i:s', $local_time );

		if ( empty( $for_every ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-logs.txt', "\n -----$key------------$usr_time --- " . microtime( true ) . '  ----- ' . var_export( $value, true ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
		}

		++$defend_wp_firewall_every_count;
		if ( $defend_wp_firewall_every_count % $for_every === 0 ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-logs.txt', "\n -----$key------- " . var_export( $value, true ) . "\n", FILE_APPEND );// This will be used only for internal debugging
		}
	} catch ( Exception $e ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-logs.txt', "\n -----$key---------- --- " . microtime( true ) . '  ------ ' . var_export( serialize( $value ), true ) . "\n", FILE_APPEND );// This will be used only for internal debugging
	}
}
