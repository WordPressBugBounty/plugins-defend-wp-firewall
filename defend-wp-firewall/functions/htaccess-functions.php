<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rules taken from https://github.com/drego85/htpw/blob/main/htaccess are utilized in this file.
 *
 * @author Andrea Draghetti (https://github.com/drego85)
 */

class Defend_WP_Firewall_Htaccess_Functions {
	public $defend_wp_firewall_options;

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
	}

	public function process_htaccess() {
		$is_litespeed_server = $this->is_litespeed_server();
		$is_apache_server    = $this->is_apache_server();

		if ( $is_apache_server ) {
			$rules = $this->apache_server_config_modification();
		} elseif ( $is_litespeed_server ) {
			$rules = $this->apache_server_config_modification( 'litespeed' );
		}

		if ( ! empty( $rules ) ) {
			$this->write_server_rules_to_htaccess( $rules );
		} else {
			$this->delete_all_dfwp_rules_in_htaccess();
		}
	}

	public function file_system_init() {
		if ( ! $this->server_check() ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! $this->is_server_writable() ) {
			return false;
		}

		$url = wp_nonce_url( 'index.php?page=dfwp_no_page', 'dfwp_fs_cred' );
		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, ABSPATH, null );
		if ( false === $creds ) {
			return false;
		}
		ob_end_clean();

		$fs = WP_Filesystem( $creds, ABSPATH );
		if ( ! $fs ) {
			return false;
		}
		return true;
	}

	public function write_server_rules_to_htaccess( $rules = '' ) {
		if ( ! $this->file_system_init() ) {
			return false;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem->exists( ABSPATH . '.htaccess' ) ) {
			$wp_filesystem->put_contents( ABSPATH . '.htaccess', '', FS_CHMOD_FILE );

			if ( ! $wp_filesystem->exists( ABSPATH . '.htaccess' ) ) {
				return false;
			}
		}

		$current = $wp_filesystem->get_contents( ABSPATH . '.htaccess' );
		$old     = $wp_filesystem->get_contents( ABSPATH . '.htaccess' );

		$current = $this->delete_dfwp_rules_in_htaccess( $current );

		if ( $rules != '' ) {
			$current = "# DefendWP Firewall Start\r\n<IfModule mod_rewrite.c>\r" . $rules . "\r\n</IfModule>\r\n# DefendWP Firewall End" . $current;
		}

		$wp_filesystem->put_contents( ABSPATH . '.htaccess', $current, FS_CHMOD_FILE );

		$status = $this->check_site_status();
		if ( $status === false ) {
			$wp_filesystem->put_contents( ABSPATH . '.htaccess', $old, FS_CHMOD_FILE );
			return false;
		}

		return true;
	}

	public function is_server_writable() {
		if ( ( ! defined( 'FTP_HOST' ) || ! defined( 'FTP_USER' ) || ! defined( 'FTP_PASS' ) ) && ( get_filesystem_method( array(), ABSPATH ) !== 'direct' ) ) {
			return false;
		} else {
			return true;
		}
	}

	public function server_check() {
		if ( ! isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return false;
		}
		$server = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );
		foreach ( array( 'apache', 'litespeed' ) as $server_software ) {
			if ( strstr( $server, $server_software ) ) {
				return true;
			}
		}

		return false;
	}

	private function is_apache_server() {
		if ( ! isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return false;
		}
		$server = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );
		if ( strstr( $server, 'apache' ) ) {
			return true;
		}
		return false;
	}

	private function is_litespeed_server() {
		if ( ! isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return false;
		}
		$server = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );
		if ( strstr( $server, 'apache' ) ) {
			return true;
		}
		return false;
	}

	public function delete_dfwp_rules_in_htaccess( $content ) {
		$start     = '# DefendWP Firewall Start';
		$end       = '# DefendWP Firewall End';
		$start_pos = strpos( $content, $start );
		$end_pos   = strpos( $content, $end );
		if ( $start_pos === false || $end_pos === false ) {
			return $content;
		}
		$firewall_rule = substr( $content, $start_pos, ( $end_pos + strlen( $end ) ) - $start_pos );
		return str_replace( $firewall_rule, '', $content );
	}

	public function check_site_status() {
		$url = get_site_url();

		$http_args = array(
			'method'    => 'GET',
			'verify'    => false,
			'sslverify' => false,
			'timeout'   => 30,
		);

		try {
			$response = wp_remote_request( $url, $http_args );
			if ( is_wp_error( $response ) ) {
				return false;
			} elseif ( wp_remote_retrieve_response_code( $response ) != 200 ) {
				return false;
			} else {
				$response_body = wp_remote_retrieve_body( $response );
				if ( empty( $response_body ) ) {
					return false;
				}
				return true;
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function delete_all_dfwp_rules_in_htaccess() {
		if ( ! $this->file_system_init() ) {
			return false;
		}

		global $wp_filesystem;

		if ( $wp_filesystem->exists( ABSPATH . '.htaccess' ) ) {
			$current         = $wp_filesystem->get_contents( ABSPATH . '.htaccess' );
			$removed_content = $this->delete_dfwp_rules_in_htaccess( $current );
			$wp_filesystem->put_contents( ABSPATH . '.htaccess', $removed_content, FS_CHMOD_FILE );
		}
	}

	public function apache_server_config_modification( $server = 'apache' ) {

		$htaccess_protect_files = $this->defend_wp_firewall_options->get_option( 'htaccess_protect_files' );
		$wp_includes            = WPINC;
		$rules_text             = '';
		if ( $htaccess_protect_files === 'yes' ) {
			$files = array(
				'.htaccess',
				'readme.html',
				'readme.txt',
				'wp-config.php',
				'wp-config-sample.php',
				'error_log',
				'php_errorlog',
				'debug.log',
			);

			$rules_text .= "\n";

			foreach ( $files as $file ) {
				$rules_text .= "\t<files $file>\n";

				if ( 'apache' === $server ) {
					$rules_text .= "\t\t<IfModule mod_authz_core.c>\n";
					$rules_text .= "\t\t\tRequire all denied\n";
					$rules_text .= "\t\t</IfModule>\n";
					$rules_text .= "\t\t<IfModule !mod_authz_core.c>\n";
					$rules_text .= "\t\t\tOrder allow,deny\n";
					$rules_text .= "\t\t\tDeny from all\n";
					$rules_text .= "\t\t</IfModule>\n";
				} else {
					$rules_text .= "\t\t<IfModule mod_litespeed.c>\n";
					$rules_text .= "\t\t\tOrder allow,deny\n";
					$rules_text .= "\t\t\tDeny from all\n";
					$rules_text .= "\t\t</IfModule>\n";
				}

				$rules_text .= "\t</files>\n";
			}
		}

		$rewrites = '';
		if ( $htaccess_protect_files === 'yes' ) {
			$rewrites .= "\n";
			$rewrites .= "\t\tRewriteRule ^wp-admin/install\.php$ - [F]\n";
			$rewrites .= "\t\tRewriteRule ^wp-admin/includes/ - [F]\n";

			if ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) {
				$rewrites .= "\t\tRewriteRule ^$wp_includes/ms-files.php$ - [S=4]\n";
			}

			$rewrites .= "\t\tRewriteRule !^$wp_includes/ - [S=3]\n";
			$rewrites .= "\t\tRewriteRule ^$wp_includes/[^/]+\.php$ - [F]\n";
			$rewrites .= "\t\tRewriteRule ^$wp_includes/js/tinymce/langs/.+\.php - [F]\n";
			$rewrites .= "\t\tRewriteRule ^$wp_includes/theme-compat/ - [F]\n";

			$hide_dirs = implode( '|', array( 'git', 'svn' ) );
			$rewrites .= "\t\tRewriteCond %{REQUEST_FILENAME} -f\n";
			$rewrites .= "\t\tRewriteRule (^|.*/)\.({$hide_dirs})/.* - [F]\n";
		}

		$htaccess_directory_browsing = $this->defend_wp_firewall_options->get_option( 'htaccess_directory_browsing' );

		if ( $htaccess_directory_browsing === 'yes' ) {
			$rules_text .= "\n";
			$rules_text .= "\tOptions -Indexes\n";
		}

		$htaccess_uploads_php = $this->defend_wp_firewall_options->get_option( 'htaccess_uploads_php' );

		if ( $htaccess_uploads_php === 'yes' ) {
			$dir = $this->get_relative_upload_url_path();

			if ( ! empty( $dir ) ) {
				$dir = preg_quote( $dir );

				$rewrites .= "\n";
				$rewrites .= "\t\tRewriteRule ^$dir/.*\.(?:php[1-7]?|pht|phtml?|phps)\\.?$ - [NC,F]\n";
			}
		}

		$htaccess_plugins_php = $this->defend_wp_firewall_options->get_option( 'htaccess_plugins_php' );

		if ( $htaccess_plugins_php === 'yes' ) {

			$dir = $this->get_relative_url_path( WP_PLUGIN_URL );

			if ( ! empty( $dir ) ) {
				$dir = preg_quote( $dir );

				$rewrites .= "\n";
				$rewrites .= "\t\tRewriteRule ^$dir/.*\.(?:php[1-7]?|pht|phtml?|phps)\\.?$ - [NC,F]\n";
			}
		}

		$htaccess_themes_php = $this->defend_wp_firewall_options->get_option( 'htaccess_themes_php' );

		if ( $htaccess_themes_php === 'yes' ) {
			$dir = $this->get_relative_url_path( get_theme_root_uri() );

			if ( ! empty( $dir ) ) {
				$dir = preg_quote( $dir );

				$rewrites .= "\n";
				$rewrites .= "\t\tRewriteRule ^$dir/.*\.(?:php[1-8]?|pht|phtml?|phps)\\.?$ - [NC,F]\n";
			}
		}

		if ( ! empty( $rewrites ) ) {
			$rules_text .= "\n";
			$rules_text .= "\t<IfModule mod_rewrite.c>\n";
			$rules_text .= "\t\tRewriteEngine On\n";
			$rules_text .= $rewrites;
			$rules_text .= "\t</IfModule>\n";
		}

		return $rules_text;
	}

	public function get_relative_url_path( $url ) {
		$url      = wp_parse_url( $url, PHP_URL_PATH );
		$home_url = wp_parse_url( home_url(), PHP_URL_PATH );
		$path     = preg_replace( '/^' . preg_quote( $home_url, '/' ) . '/', '', $url, 1, $count );

		if ( 1 === $count ) {
			return trim( $path, '/' );
		}

		return false;
	}

	public function get_relative_upload_url_path() {
		$upload_dir_details = wp_upload_dir();
		return $this->get_relative_url_path( $upload_dir_details['baseurl'] );
	}
}
