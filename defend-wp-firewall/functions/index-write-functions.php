<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defend_WP_Firewall_Index_Write_Functions {
	public $defend_wp_firewall_options;
	private $dfwp_firewall_rules_manager;

	public function __construct() {
		$this->defend_wp_firewall_options  = new Defend_WP_Firewall_Options();
		$this->dfwp_firewall_rules_manager = new Defend_WP_Firewall_Rules_Manager();
	}

	public function file_system_init() {

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

	public function set_flag( $rules ) {
		if ( empty( $rules ) ) {
			return;
		}
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['options'] ) && ! empty( $rule['options']['index_php'] ) ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_index_write_flag', '1' );
				return;
			}
		}
	}

	public function process_flag() {
		$flag = $this->defend_wp_firewall_options->get_option( 'dfwp_index_write_flag' );
		if ( ! empty( $flag ) ) {
			$this->defend_wp_firewall_options->delete_option( 'dfwp_index_write_flag' );
			$this->process_index_rules();
		}
		$error = $this->defend_wp_firewall_options->get_option( 'dfwp_index_write_error' );
		if ( ! empty( $error ) ) {
			if ( ! wp_next_scheduled( 'defend_wp_firewall_index_write' ) ) {
				wp_schedule_event( time(), 'daily', 'defend_wp_firewall_index_write' );
			}
		} elseif ( wp_next_scheduled( 'defend_wp_firewall_index_write' ) ) {
			wp_clear_scheduled_hook( 'defend_wp_firewall_index_write' );
		}
	}

	public function is_server_writable() {
		if ( ( ! defined( 'FTP_HOST' ) || ! defined( 'FTP_USER' ) || ! defined( 'FTP_PASS' ) ) && ( get_filesystem_method( array(), ABSPATH ) !== 'direct' ) ) {
			return false;
		} else {
			return true;
		}
	}

	public function process_index_rules() {
		$rules = $this->dfwp_firewall_rules_manager->get_firewall_rules();
		if ( empty( $rules ) ) {
			return;
		}
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['options'] ) && ! empty( $rule['options']['index_php'] ) ) {
				$index_php = $rule['options']['index_php'];
				if ( ! empty( $index_php ) && is_array( $index_php ) ) {
					foreach ( $index_php as $index_php_option ) {
						if ( ! empty( $index_php_option['file_path'] ) && ! empty( $index_php_option['folder_path'] ) ) {
							$this->write_index_recursively( $index_php_option['file_path'], $index_php_option['folder_path'] );
						}
					}
				}
			}
		}
	}

	private function write_index_recursively( $path, $folder_path ) {
		if ( ! $this->file_system_init() ) {
			return false;
		}

		global $wp_filesystem;

		switch ( $folder_path ) {
			case 'root':
				$root_path = ABSPATH;
				break;
			case 'wp-content':
				$root_path = WP_CONTENT_DIR;
				break;
			case 'wp-includes':
				$root_path = trailingslashit( ABSPATH . WPINC );
				break;
			case 'wp-admin':
				$root_path = trailingslashit( ABSPATH . 'wp-admin' );
				break;
			case 'wp-uploads':
				$temp      = wp_upload_dir();
				$root_path = $temp['basedir'];
				break;
			case 'plugins':
				$root_path = WP_PLUGIN_DIR;
				break;
			case 'themes':
				$root_path = get_theme_root();
				break;
			default:
				return false;
		}

		$root_path = trailingslashit( untrailingslashit( $root_path ) );
		$path      = trailingslashit( untrailingslashit( $path ) );
		$full_path = $root_path . $path;

		if ( ! $wp_filesystem->is_dir( $full_path ) ) {
			return false;
		}

		$this->add_index_file_recursive( $full_path );
		return true;
	}

	private function add_index_file_recursive( $dir ) {
		global $wp_filesystem;

		$index_path = trailingslashit( $dir ) . 'index.php';

		// Add index.php if not exists
		if ( ! $wp_filesystem->exists( $index_path ) ) {
			$result = $wp_filesystem->put_contents( $index_path, "<?php\n// Silence is golden. Written by Defend WP Firewall\n", FS_CHMOD_FILE );
			if ( ! $result ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_index_write_error', true );
			}
		}

		// Read subdirectories
		$items = $wp_filesystem->dirlist( $dir );

		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( 'd' === $item['type'] ) {
					$subdir = trailingslashit( $dir ) . $item['name'];
					$this->add_index_file_recursive( $subdir );
				}
			}
		}
	}
}
