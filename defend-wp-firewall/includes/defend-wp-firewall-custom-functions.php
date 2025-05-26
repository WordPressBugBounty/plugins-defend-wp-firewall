<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function defend_wp_firewall_plugin_backuply_1_3_4( $args ) {
	if ( empty( $_POST['options'] ) ) {
		return;
	}
	$options      = $_POST['options'];
	$keys         = array_keys( $options );
	$field_prefix = $options[ $keys[0] ];

	$possible_fields = array( 'option', 'meta' );

	// We make sure here that we do not process any unwanted data.
	if ( ! in_array( $field_prefix, $possible_fields, true ) ) {
		$dfwp_firewall_rule = $args['dfwp_firewall_rule'];
		$firewall_id        = $dfwp_firewall_rule['id'];
		$matched_rule_data  = 'defend_wp_firewall_plugin_backuply_1_3_4';
		$run_functions      = $args['run_functions'];

		defend_wp_firewall_die(
			array(
				'type'        => 'firewall',
				'firewall_id' => $firewall_id,
				'title'       => 'Firewall function block (ID #' . ( $firewall_id ) . ')',
				'message'     => 'Access denied by firewall.',
				'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
			),
			$run_functions['log'],
			$run_functions['block'],
		);
	}
}

function defend_wp_firewall_plugin_wp_easy_gallery_4_8_5( $args ) {
	if ( empty( $_POST['edit_imageId'] ) ) {
		return;
	}

	$dfwp_firewall_rule = $args['dfwp_firewall_rule'];
	$firewall_id        = $dfwp_firewall_rule['id'];
	$matched_rule_data  = 'defend_wp_firewall_plugin_wp_easy_gallery_4_8_5';
	$run_functions      = $args['run_functions'];

	foreach ( $_POST['edit_imageId'] as $key => $editImageId ) {
		$fixed_value                   = intval( $editImageId );
		$_POST['edit_imageId'][ $key ] = $fixed_value;

		if ( $fixed_value !== $editImageId ) {
			defend_wp_firewall_die(
				array(
					'type'        => 'firewall',
					'firewall_id' => $firewall_id,
					'title'       => 'Firewall function sanitize (ID #' . ( $firewall_id ) . ')',
					'message'     => 'Access denied by firewall.',
					'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
				),
				$run_functions['log'],
				$run_functions['block'],
			);
		}
	}
}

function defend_wp_firewall_plugin_the_events_calendar_6_6_4( $args ) {
	global $defend_wp_firewall_events_calendar_6_6_4;
	$defend_wp_firewall_events_calendar_6_6_4 = $args;
	add_filter( 'posts_orderby', 'defend_wp_firewall_plugin_the_events_calendar_6_6_4_filter', 100, 2 );
}

function defend_wp_firewall_plugin_the_events_calendar_6_6_4_filter( $posts_orderby, $query ) {
	if ( ! is_string( $posts_orderby ) || trim( $posts_orderby ) === '' ) {
		return $posts_orderby;
	}
	global $defend_wp_firewall_events_calendar_6_6_4;
	$redirected_orderbys = '';
	$orderbys            = explode( ',', $posts_orderby );
	foreach ( $orderbys as $orderby_frag ) {
		// Fast-track the `rand` order, no need to redirect anything.
		if ( stripos( $orderby_frag, 'rand' ) === 0 ) {
			$redirected_orderbys .= $orderby_frag;
			continue;
		}
		// Each `ORDER BY` entry could specify an order (DESC|ASC) or not.
		if ( preg_match( '~\s*(?<orderby>[^\s]+]?)\s+(?<order>.+)$~i', $orderby_frag, $m ) ) {
			$orderby = trim( $m['orderby'] );
			$order   = strtoupper( trim( $orderby ) );
			if ( defend_wp_firewall_detect_sql_injection( $order ) && ! in_array( $order, array( 'DESC', 'ASC' ), true ) ) {
				$dfwp_firewall_rule = $defend_wp_firewall_events_calendar_6_6_4['dfwp_firewall_rule'];
				$firewall_id        = $dfwp_firewall_rule['id'];
				$matched_rule_data  = 'defend_wp_firewall_plugin_wp_easy_gallery_4_8_5';
				$run_functions      = $defend_wp_firewall_events_calendar_6_6_4['run_functions'];
				defend_wp_firewall_die(
					array(
						'type'        => 'firewall',
						'firewall_id' => $firewall_id,
						'title'       => 'Firewall function block (ID #' . ( $firewall_id ) . ')',
						'message'     => 'Access denied by firewall.',
						'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
					),
					$run_functions['log'],
					$run_functions['block'],
				);
			}
		}

		return $posts_orderby;
	}

	return $redirected_orderbys;
}

function defend_wp_firewall_plugin_watchtowerhq_3_9_6( $args ) {
	if ( isset( $_GET['wht_login'] ) && empty( $_GET['access_token'] ) ) {
		$dfwp_firewall_rule = $args['dfwp_firewall_rule'];
		$firewall_id        = $dfwp_firewall_rule['id'];
		$matched_rule_data  = 'defend_wp_firewall_plugin_watchtowerhq_3_9_6';
		$run_functions      = $args['run_functions'];
		defend_wp_firewall_die(
			array(
				'type'        => 'firewall',
				'firewall_id' => $firewall_id,
				'title'       => 'Firewall function sanitize (ID #' . ( $firewall_id ) . ')',
				'message'     => 'Access denied by firewall.',
				'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
			),
			$run_functions['log'],
			$run_functions['block'],
		);
	}
}

function defend_wp_firewall_plugin_really_simple_ssl_9_0_0( $args ) {
	$dfwp_firewall_rule = $args['dfwp_firewall_rule'];
	$firewall_id        = $dfwp_firewall_rule['id'];
	$matched_rule_data  = 'defend_wp_firewall_plugin_really_simple_ssl_9_0_0';
	$run_functions      = $args['run_functions'];

	if ( ! empty( $_POST['user_id'] ) && ! empty( $_POST['login_nonce'] ) ) {
		$user_id     = intval( sanitize_text_field( $_POST['user_id'] ) );
		$nonce       = $_POST['login_nonce'];
		$login_nonce = get_user_meta( $user_id, '_rsssl_two_factor_nonce', true );
		$return      = true;
		if ( ! $login_nonce || empty( $login_nonce['rsssl_key'] ) || empty( $login_nonce['rsssl_expiration'] ) ) {
			$return = false;
		}

		if ( $return ) {
			$unverified_nonce = array(
				'rsssl_user_id'    => $user_id,
				'rsssl_expiration' => $login_nonce['rsssl_expiration'],
				'rsssl_key'        => $nonce,
			);

			$message = wp_json_encode( $unverified_nonce );

			if ( ! $message ) {
				$return = false;
			} else {

				$unverified_hash = wp_hash( $message, 'nonce' );

				$hashes_match = $unverified_hash && hash_equals( $login_nonce['rsssl_key'], $unverified_hash );

				if ( $hashes_match && time() < $login_nonce['rsssl_expiration'] ) {
					return true;
				}
			}
		}

		defend_wp_firewall_die(
			array(
				'type'        => 'firewall',
				'firewall_id' => $firewall_id,
				'title'       => 'Firewall function sanitize (ID #' . ( $firewall_id ) . ')',
				'message'     => 'Access denied by firewall.',
				'extra'       => array( 'more_details' => array( 'FIREWALL_MATCH' => $matched_rule_data ) ),
			),
			$run_functions['log'],
			$run_functions['block'],
		);
	}
}

function defend_wp_firewall_plugin_contest_24_0_7( $args ) {
	$_REQUEST['cgLostPasswordSiteUrl'] = wp_get_referer();
}
