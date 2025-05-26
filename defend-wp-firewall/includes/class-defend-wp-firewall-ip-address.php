<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_IP_Address {

	public function get_ip() {
		$ipfy_ip = $this->get_ipify_ip_dfwp();
		if ( ! empty( $ipfy_ip ) ) {
			return $ipfy_ip;
		}
		return $this->get_fallback_ip();
	}

	public function get_fallback_ip() {
		$REMOTE_ADDR = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$REMOTE_ADDR = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {

			$cloudflare_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );

			if ( filter_var( $cloudflare_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				// https://www.cloudflare.com/ips-v4/#
				// https://www.cloudflare.com/ips-v6/#
				$cloud_flare_ip = array(
					'173.245.48.0/20',
					'103.21.244.0/22',
					'103.22.200.0/22',
					'103.31.4.0/22',
					'141.101.64.0/18',
					'108.162.192.0/18',
					'190.93.240.0/20',
					'188.114.96.0/20',
					'197.234.240.0/22',
					'198.41.128.0/17',
					'162.158.0.0/15',
					'104.16.0.0/13',
					'104.24.0.0/14',
					'172.64.0.0/13',
					'131.0.72.0/22',
					'2400:cb00::/32',
					'2606:4700::/32',
					'2803:f800::/32',
					'2405:b500::/32',
					'2405:8100::/32',
					'2a06:98c0::/29',
					'2c0f:f248::/32',
				);

				foreach ( $cloud_flare_ip as $ip_range ) {
					if ( $this->cidr_check( $REMOTE_ADDR, $ip_range ) ) {
						return $cloudflare_ip;
					}
				}
			}
		}

		if ( ! filter_var( $REMOTE_ADDR, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
				$HTTP_X_REAL_IP = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
				if ( filter_var( $HTTP_X_REAL_IP, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE ) ) {
					return $HTTP_X_REAL_IP;
				}
			}

			if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				// Get server IP address
				$server_ip = ( isset( $_SERVER['SERVER_ADDR'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

				$ip = trim( current( explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) );

				if ( filter_var( $ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE ) && $ip != $server_ip ) {
					return $ip;
				}
			}
		}

		return $REMOTE_ADDR;
	}

	private function cidr_check( $ip, $cidr ) {
		list($subnet, $mask) = explode( '/', $cidr );

		if ( filter_var( $ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 ) && filter_var( $subnet, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 ) ) {
			return ( ip2long( $ip ) & ~( ( 1 << ( 32 - $mask ) ) - 1 ) ) == ip2long( $subnet );
		} elseif ( filter_var( $ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6 ) && filter_var( $subnet, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6 ) ) {
			$ip     = inet_pton( $ip );
			$subnet = inet_pton( $subnet );

			$binMask = str_repeat( 'f', round( $mask / 4 ) );
			switch ( $mask % 4 ) {
				case 0:
					break;
				case 1:
					$binMask .= '8';
					break;
				case 2:
					$binMask .= 'c';
					break;
				case 3:
					$binMask .= 'e';
					break;
			}

			$binMask = str_pad( $binMask, 32, '0' );
			$binMask = pack( 'H*', $binMask );

			return ( $ip & $binMask ) == $subnet;
		}

		return false;
	}

	public function get_ipify_ip_dfwp() {
		$cookie_functions_obj = new Defend_WP_Firewall_Cookie_Functions();
		$this_ip              = $cookie_functions_obj->get_ipify_ip_from_cookie();

		return $this_ip;
	}
}
