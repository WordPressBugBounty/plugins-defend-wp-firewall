<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function defend_wp_firewall_manual_debug( $conditions = '', $print_text = '', $for_every = 0 ) {
	if ( ! defined( 'DEFEND_WP_FIREWALL_DEBUG' ) || ! DEFEND_WP_FIREWALL_DEBUG ) {
		return;
	}

	global $defend_wp_firewall_debug_count;
	++$defend_wp_firewall_debug_count;
	$print_text = '-' . $print_text;

	global $defend_wp_firewall_every_count;

	if ( empty( $for_every ) ) {
		return defend_wp_firewall_print_memory_debug( $defend_wp_firewall_debug_count, $conditions, $print_text );
	}

	++$defend_wp_firewall_every_count;
	if ( $defend_wp_firewall_every_count % $for_every == 0 ) {
		return defend_wp_firewall_print_memory_debug( $defend_wp_firewall_debug_count, $conditions, $print_text );
	}
}

function defend_wp_firewall_print_memory_debug( $debug_count, $conditions = '', $print_text = '' ) {
	// return;
	global $defend_wp_firewall_profiling_start;

	$this_memory_peak_in_mb = memory_get_peak_usage();
	$this_memory_peak_in_mb = $this_memory_peak_in_mb / 1048576;

	$this_memory_in_mb = memory_get_usage();
	$this_memory_in_mb = $this_memory_in_mb / 1048576;

	$current_cpu_load = 0;

	if ( function_exists( 'sys_getloadavg' ) ) {
		$cpu_load         = sys_getloadavg();
		$current_cpu_load = $cpu_load[0] ?? 0;
	}

	if ( empty( $defend_wp_firewall_profiling_start ) ) {
		$defend_wp_firewall_profiling_start = time();
	}

	$this_time_taken = time() - $defend_wp_firewall_profiling_start;

	if ( $conditions == 'printOnly' ) {
		if ( $this_memory_peak_in_mb >= 34 ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-memory-usage.txt', $debug_count . $print_text . ' ' . round( $this_memory_in_mb, 2 ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-time-taken.txt', $debug_count . $print_text . ' ' . round( $this_time_taken, 2 ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-cpu-usage.txt', $debug_count . $print_text . ' ' . $current_cpu_load . "\n", FILE_APPEND ); // This will be used only for internal debugging
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-memory-peak.txt', $debug_count . $print_text . ' ' . round( $this_memory_peak_in_mb, 2 ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
		}
		return;
	}
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-memory-usage.txt', $debug_count . $print_text . ' ' . round( $this_memory_in_mb, 2 ) . "\n", FILE_APPEND );// This will be used only for internal debugging
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-time-taken.txt', $debug_count . $print_text . ' ' . round( $this_time_taken, 2 ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-cpu-usage.txt', $debug_count . $print_text . ' ' . $current_cpu_load . "\n", FILE_APPEND ); // This will be used only for internal debugging
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/defend-wp-firewall-memory-peak.txt', $debug_count . $print_text . ' ' . round( $this_memory_peak_in_mb, 2 ) . "\n", FILE_APPEND ); // This will be used only for internal debugging
}

function defend_wp_firewall_is_dir( $good_path ) {
	$good_path = wp_normalize_path( $good_path );

	if ( is_dir( $good_path ) ) {
		return true;
	}

	$ext = pathinfo( $good_path, PATHINFO_EXTENSION );

	if ( ! empty( $ext ) ) {
		return false;
	}

	if ( is_file( $good_path ) ) {
		return false;
	}

	return true;
}

function defend_wp_firewall_is_wp_content_path( $file ) {
	if ( stripos( $file, '/' . DEFEND_WP_FIREWALL_WP_CONTENT_BASENAME ) === 0 || stripos( $file, DEFEND_WP_FIREWALL_WP_CONTENT_DIR ) === 0 ) {
		return true;
	}

	return false;
}

function defend_wp_firewall_add_fullpath( $file ) {
	$file = wp_normalize_path( $file );

	if ( defend_wp_firewall_is_wp_content_path( $file ) ) {
		//Special patch for wp-content dir to support common functions of paths.

		$temp_file = $file;

		if ( stripos( $file, DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR ) === 0 ) {
			$temp_file = substr_replace( $file, '', 0, strlen( DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR ) );
			if ( $temp_file === '' || $temp_file === '/' ) {
				$temp_file = DEFEND_WP_FIREWALL_WP_CONTENT_DIR;
			}
		}

		return defend_wp_firewall_add_custom_path( $temp_file, $custom_path = DEFEND_WP_FIREWALL_WP_CONTENT_DIR . '/' );
	}

	return defend_wp_firewall_add_custom_path( $file, $custom_path = DEFEND_WP_FIREWALL_ABSPATH );
}

function defend_wp_firewall_add_trailing_slash( $string ) {
	return defend_wp_firewall_remove_trailing_slash( $string ) . '/';
}

function defend_wp_firewall_remove_trailing_slash( $string ) {
	return rtrim( $string, '/' );
}

function defend_wp_firewall_add_custom_path( $file, $custom_path ) {

	$temp_file = defend_wp_firewall_add_trailing_slash( $file );

	if ( stripos( $temp_file, $custom_path ) !== false ) {
		return $file;
	}

	return $custom_path . ltrim( $file, '/' );
}

function defend_wp_firewall_remove_custom_path( $file, $custom_path, $relative_path ) {
		// defend_wp_firewall_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

	if ( stripos( $file, $custom_path ) === false ) {
		if ( substr( $relative_path, -1 ) === '/' ) {
			return $relative_path . ltrim( $file, '/' );
		}

		return $relative_path . '/' . ltrim( $file, '/' );
	}

	return str_replace( $custom_path, $relative_path, $file );
}

function defend_wp_firewall_remove_fullpath( $file ) {
	$file = wp_normalize_path( $file );

	if ( defend_wp_firewall_is_wp_content_path( $file ) ) {

		$temp_file = $file;

		if ( stripos( $file, DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR ) === 0 ) {
			$temp_file = substr_replace( $file, '', 0, strlen( DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR ) );
			if ( $temp_file === '' || $temp_file === '/' ) {
				$temp_file = DEFEND_WP_FIREWALL_WP_CONTENT_DIR;
			}
		}

		if ( defend_wp_firewall_remove_trailing_slash( $file ) === defend_wp_firewall_remove_trailing_slash( DEFEND_WP_FIREWALL_WP_CONTENT_DIR ) ) {
			$temp_file = defend_wp_firewall_remove_trailing_slash( $temp_file );
		}

		return defend_wp_firewall_remove_custom_path( $temp_file, $custom_path = DEFEND_WP_FIREWALL_WP_CONTENT_DIR, $relative_path = DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR );
	}

	return defend_wp_firewall_remove_custom_path( $file, $custom_path = DEFEND_WP_FIREWALL_ABSPATH, $relative_path = DEFEND_WP_FIREWALL_RELATIVE_ABSPATH );
}

function defend_wp_firewall_timeout_cut( $start_time = false, $reduce_sec = 0 ) {
	if ( $start_time === false ) {
		global $defend_wp_firewall_ajax_start_time;
		if ( empty( $defend_wp_firewall_ajax_start_time ) ) {
			$defend_wp_firewall_ajax_start_time = time();
		}

		$start_time = $defend_wp_firewall_ajax_start_time;
	}

	$time_diff = time() - $start_time;
	if ( ! defined( 'DEFEND_WP_FIREWALL_TIMEOUT' ) ) {
		define( 'DEFEND_WP_FIREWALL_TIMEOUT', 21 );
	}

	$max_execution_time = DEFEND_WP_FIREWALL_TIMEOUT - $reduce_sec;
	if ( $time_diff >= $max_execution_time ) {
		defend_wp_firewall_log( $time_diff, '--------cutin ya--------' );
		return true;
	}
	return false;
}

function defend_wp_firewall_get_backtrace_string( $limit = 20 ) {

	if ( ! DEFEND_WP_FIREWALL_DEBUG ) {
		return;
	}

	$bactrace_arr  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $limit );
	$backtrace_str = '';

	if ( ! is_array( $bactrace_arr ) ) {
		return false;
	}

	foreach ( $bactrace_arr as $k => $v ) {
		if ( $k == 0 ) {
			continue;
		}

		$line           = empty( $v['line'] ) ? 0 : $v['line'];
		$backtrace_str .= '<-' . $v['function'] . '(line ' . $line . ')';
	}

	return $backtrace_str;
}

function defend_wp_firewall_get_upload_dir() {
	$upload_dir = array();
	if ( defined( 'DEFEND_WP_FIREWALL_BRIDGE' ) ) {
		$upload_dir['basedir'] = DEFEND_WP_FIREWALL_RELATIVE_WP_CONTENT_DIR . '/uploads';
	} else {
		$upload_dir = wp_upload_dir();
	}

	$upload_dir = str_replace( DEFEND_WP_FIREWALL_ABSPATH, DEFEND_WP_FIREWALL_RELATIVE_ABSPATH, $upload_dir['basedir'] );

	return wp_normalize_path( $upload_dir );
}

function defend_wp_firewall_die_with_json_encode_simple( $msg = array( 'empty data' ), $escape = 0, $next_call_30_secs = false ) {
	switch ( $escape ) {
		case 1:
			$json_encoded_msg = wp_json_encode( $msg, JSON_UNESCAPED_SLASHES );
			// We arlready performed escaped.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			die( '<DEFEND_WP_FIREWALL_START>' . $json_encoded_msg . '<DEFEND_WP_FIREWALL_END>' );
		case 2:
			$json_encoded_msg = wp_json_encode( $msg, JSON_UNESCAPED_UNICODE );
			// We arlready performed escaped.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			die( '<DEFEND_WP_FIREWALL_START>' . $json_encoded_msg . '<DEFEND_WP_FIREWALL_END>' );
	}

	$json_encoded_msg = wp_json_encode( $msg );
	// We arlready performed escaped.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	die( '<DEFEND_WP_FIREWALL_START>' . $json_encoded_msg . '<DEFEND_WP_FIREWALL_END>' );
}

function defend_wp_firewall_remove_protocal_from_url( $url ) {
	$url = preg_replace( '(^https?://?www.)', '', $url );
	return preg_replace( '(^https?://)', '', $url );
}

function defend_wp_firewall_get_post_by_post_name( $post_name ) {
	global $wpdb;

	$value = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->base_prefix . 'posts WHERE `post_name`=%s', $post_name ), ARRAY_A );

	return $value;
}

function defend_wp_firewall_add_protocal_to_url( $url, $protocal, $add_www ) {
	$trimmed_url = defend_wp_firewall_remove_protocal_from_url( $url );
	$protocal    = $protocal . '://';
	return $add_www ? $protocal . 'www.' . $trimmed_url : $protocal . $trimmed_url;
}

function defend_wp_firewall_die( $data, $log = true, $block = true, $send_mail = false, $sanitize = true ) {
	if ( $log ) {
		$defend_wp_firewall_logs = new Defend_WP_Firewall_Logs();
		$defend_wp_firewall_logs->set_log( $data, $sanitize, $send_mail );
	}
	if ( $block ) {

		define( 'DEFEND_WP_FIREWALL_BLOCKED', true );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		include DEFEND_WP_FIREWALL_PLUGIN_DIR . 'includes/view/block-access.php';
		die();
	}
}

function defend_wp_firewall_verify_ajax_requests( $admin_check = true ) {

	//verify its ajax request
	if ( empty( $_POST['action'] ) ) {
		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'error' => true,
				'msg'   => 'Ajax without action',
			)
		);
	}

	//Verifies the Ajax request to prevent processing requests external of the site
	$result = check_ajax_referer( 'dwp_firewall_revmakx', 'security', false );

	if ( empty( $result ) || $result == -1 ) {
		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'error' => true,
				'msg'   => 'Ajax nonce verify failed',
			)
		);
	}

	if ( ! $admin_check ) {
		return true;
	}

	//Check request made by admin
	if ( ! current_user_can( 'manage_options' ) ) {
		defend_wp_firewall_die_with_json_encode_simple(
			array(
				'error' => true,
				'msg'   => 'Ajax nonce verify failed, not an admin',
			)
		);
	}
}

function defend_wp_firewall_disable_hearbeat() {
	if ( defined( 'DEFEND_WP_FIREWALL_DISABLE_HEARTBEAT' ) && DEFEND_WP_FIREWALL_DISABLE_HEARTBEAT ) {
		add_action( 'init', 'defend_wp_fiewall_stop_heartbeat', 1 );
	}
}

function defend_wp_fiewall_stop_heartbeat() {
	wp_deregister_script( 'heartbeat' );
}

function defend_wp_firewall_get_request_uri() {
	if ( empty( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$actual_link = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	} else {
		$actual_link = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	}

	return $actual_link;
}

function defend_wp_firewall_is_admin() {
	return current_user_can( 'manage_options' );
}


function defend_wp_firewall_get_server_headers() {
	$headers = array();

	array_walk(
		$_SERVER,
		function ( $value, $name ) use ( &$headers ) {
			if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
				$key             = ucwords( strtolower( str_replace( '_', '-', substr( $name, 5 ) ) ) );
				$headers[ $key ] = $value;
			}
		}
	);

	return $headers;
}

function defend_wp_firewall_get_remote_address() {
	$ip_obj = new Defend_WP_Firewall_IP_Address();

	return $ip_obj->get_ip();
}

function defend_wp_firewall_get_http_referer() {
	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	}
	return '';
}

function defend_wp_firewall_is_valid_json( $json_string ) {
	if ( ! is_string( $json_string ) ) {
		return $json_string;
	}
	$is_slashed = false;

	if ( stripslashes( $json_string ) !== $json_string ) {
		// Slashes detected, so we need to unslash before decoding
		$json_string = wp_unslash( $json_string );
		$is_slashed  = true;
	}

	$decoded_data = json_decode( ( $json_string ), true );
	$json_error   = json_last_error();
	if ( $decoded_data === null || $json_error !== JSON_ERROR_NONE || ! is_array( $decoded_data ) ) {
		return false;
	} else {
		return array(
			'data'       => $decoded_data,
			'is_slashed' => $is_slashed,
		);
	}
}

function defend_wp_firewall_is_valid_base64( $data ) {
	if ( empty( $data ) ) {
		return false;
	}
	$decode_data = base64_decode( $data, true );
	if ( preg_match( '/^[\x20-\x7E]+$/', $decode_data ) && $decode_data !== false && wp_json_encode( $decode_data ) && base64_encode( $decode_data ) === $data && preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data ) ) {
		return $decode_data;
	}
	return false;
}

function defend_wp_firewall_check_openssl() {
	if ( defined( 'DEFENDWP_FIREWALL_ALTERNATE_CONNECTION' ) && DEFENDWP_FIREWALL_ALTERNATE_CONNECTION ) {
		return false;
	}
	if ( ! function_exists( 'openssl_verify' ) ) {
		return false;
	}
	$sample_data = 'defend_wp_firewall';
	//create new private and public key
	$private_key_res = openssl_pkey_new(
		array(
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		)
	);
	if ( $private_key_res === false ) {
		return false;
	}
	$details = openssl_pkey_get_details( $private_key_res );
	if ( $details === false ) {
		return false;
	}
	$public_key_res = openssl_pkey_get_public( $details['key'] );
	if ( $private_key_res === false ) {
		return false;
	}
	//create signature
	openssl_sign( $sample_data, $signature, $private_key_res, OPENSSL_ALGO_SHA256 );

	//verify signature
	$ok = openssl_verify( $sample_data, $signature, $public_key_res, OPENSSL_ALGO_SHA256 );
	if ( $ok == 1 ) {
		return true;
	}

	return false;
}

function defend_wp_firewall_is_dfwp_pro_activated() {
	return class_exists( 'Defend_WP' );
}


function defend_wp_firewall_is_string( $request_value ) {
	if ( ! is_string( $request_value ) ) {
		return false;
	}

	$json_value = defend_wp_firewall_is_valid_json( $request_value );
	if ( $json_value !== false ) {
		return false;
	}

	$base64_value = defend_wp_firewall_is_valid_base64( $request_value );

	if ( $base64_value !== false && ! empty( $base64_value ) ) {
		return false;
	}

	return true;
}

function defend_wp_firewall_esc_like( $text ) {
	return addcslashes( $text, '_%\\' );
}

function defend_wp_firewall_wpdb_real_escape( $data ) {
	global $wpdb;

	return $wpdb->_real_escape( $data );
}

function defend_wp_firewall_prepare_in_int( $in ) {
	if ( empty( $in ) ) {
		return $in;
	}
	$is_string = false;
	if ( is_string( $in ) ) {
		$in_new    = explode( ',', $in );
		$is_string = true;
	}
	$new_in_arr = array();
	if ( ! empty( $in_new ) ) {
		foreach ( $in_new as $in_key => $value ) {
			if ( ! empty( $value ) && absint( $value ) > 0 ) {
				$new_in_arr[ $in_key ] = absint( $value );
			}
		}
	} else {
		return $in;
	}

	if ( $is_string ) {
		return implode( ',', $new_in_arr );
	}

	return $new_in_arr;
}

function defend_wp_firewall_detect_sql_injection( $input ) {
	$patterns = array(
		'/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|OR|AND|WHERE|FROM|GROUP BY|HAVING|JOIN|ORDER BY|LIMIT|SLEEP|benchmark|delay)\b/i',
		'/--/',
		'/;/',
		'/\'/',
		'/\"/',
		'/\`/',
		'/\\\/',
		'/\*/',
		'/@variable/',
		'/\|\|/',
		'/\b0x[0-9A-F]+\b/i',
		// '/\%/',
		// '/\bTRUE\b/i',
		// '/\bFALSE\b/i'
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $input ) ) {
			return true;
		}
	}
	return false;
}

function defend_wp_firewall_detect_and_sanitize_sql_injection( $input ) {

	if ( defend_wp_firewall_detect_sql_injection( $input ) ) {
		return defend_wp_firewall_do_sql_sanitize( $input );
	}
	return $input;
}

function defend_wp_firewall_do_sql_sanitize( $old_string ) {
	global $wpdb;
	$new_string = $wpdb->prepare( '%s', $old_string );

	return $new_string;
}

function defend_wp_firewall_delete_cookie( $cookie_key ) {
	if ( ! empty( $_COOKIE ) && isset( $_COOKIE[ $cookie_key ] ) ) {
		unset( $_COOKIE[ $cookie_key ] );
		setcookie( $cookie_key, '', time() - 3600, '/' );
	}
}

function defend_wp_firewall_wp_safe_redirect_check( $location, $status = 302 ) {

	// Need to look at the URL the way it will end up in wp_redirect().
	$location = wp_sanitize_redirect( $location );

	/**
	 * Filters the redirect fallback URL for when the provided redirect is not safe (local).
	 *
	 * @since 4.3.0
	 *
	 * @param string $fallback_url The fallback URL to use by default.
	 * @param int    $status       The HTTP response status code to use.
	 */
	$fallback_url = apply_filters( 'wp_safe_redirect_fallback', admin_url(), $status );

	$location = wp_validate_redirect( $location, $fallback_url );
	return $location;
}

function defend_wp_users_can_register( $data ) {
	return get_option( 'users_can_register' );
}



function defend_wp_firewall_remove_by_plugin_class( $tag, $class_name, $functionName, $isAction = false, $priority = 10 ) {
	if ( ! class_exists( $class_name ) ) {
		return null;
	}

	global $wp_filter;

	if ( empty( $wp_filter[ $tag ][ $priority ] ) ) {
		return null;
	}

	foreach ( $wp_filter[ $tag ][ $priority ] as $callable ) {
		if ( empty( $callable['function'] ) || ! is_array( $callable['function'] ) || count( $callable['function'] ) < 2 ) {
			continue;
		}

		if ( ! is_a( $callable['function'][0], $class_name ) ) {
			continue;
		}

		if ( $callable['function'][1] !== $functionName ) {
			continue;
		}

		if ( $isAction ) {
			remove_action( $tag, $callable['function'], $priority );
		} else {
			remove_filter( $tag, $callable['function'], $priority );
		}

		return $callable['function'];
	}

	return null;
}

function defend_wp_sanitize_file_name( $filename ) {
	$filename      = remove_accents( $filename );
	$special_chars = array( '?', '[', ']', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', '’', '«', '»', '”', '“', chr( 0 ) );
	// Check for support for utf8 in the installed PCRE library once and store the result in a static.
	static $utf8_pcre = null;
	if ( ! isset( $utf8_pcre ) ) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$utf8_pcre = @preg_match( '/^./u', 'a' );
	}
	if ( ! seems_utf8( $filename ) ) {
		$_ext     = pathinfo( $filename, PATHINFO_EXTENSION );
		$_name    = pathinfo( $filename, PATHINFO_FILENAME );
		$filename = sanitize_title_with_dashes( $_name ) . '.' . $_ext;
	}
	if ( $utf8_pcre ) {
		$filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
	}
	$filename = str_replace( $special_chars, '', $filename );
	$filename = str_replace( array( '%20', '+' ), '-', $filename );
	$filename = preg_replace( '/\.{2,}/', '.', $filename );
	$filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
	$filename = preg_replace( '/\/\.+/i', '', $filename );
	$filename = preg_replace( '/(\/\/)+/i', '', $filename );
	$filename = trim( $filename, '.-_' );
	return $filename;
}


function defend_wp_firewall_delete_not_allowed_shortcodes( $content, $allowed_shortcodes = array() ) {
	if ( empty( $allowed_shortcodes ) ) {
		return $content;
	}
	$matches = array();
	preg_match_all(
		'/' . get_shortcode_regex() . '/',
		$content,
		$matches,
		PREG_SET_ORDER
	);

	$all_shortcodes = array();
	foreach ( $matches as $shortcode ) {
		$all_shortcodes[] = $shortcode[2];
	}

	$not_allowed_shortcodes = array_diff( $all_shortcodes, $allowed_shortcodes );

	$pattern = get_shortcode_regex( $not_allowed_shortcodes );

	$content = preg_replace_callback( '/' . $pattern . '/s', 'strip_shortcode_tag', $content );

	return $content;
}

function defend_wp_firewall_collect_urls() {
	return array(
		'url'      => site_url(),
		'home_url' => home_url(),
	);
}
