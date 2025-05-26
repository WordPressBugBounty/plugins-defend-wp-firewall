<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Defend_WP_Firewall_Purge_Cache {

	private $defend_wp_firewall_options;

	public function __construct() {
		$this->defend_wp_firewall_options = new Defend_WP_Firewall_Options();
	}

	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$pub_key = $this->defend_wp_firewall_options->get_option( 'dfwp_pub_key' );
		if ( empty( $pub_key ) ) {
			return;
		}
		$enable_defendwp_nonce = $this->defend_wp_firewall_options->get_option( 'enable_defendwp_nonce' );
		if ( ! empty( $enable_defendwp_nonce ) && $enable_defendwp_nonce == 'yes' ) {
			$is_cleared = $this->defend_wp_firewall_options->get_option( 'dfwp_clear_cache_plugins_cache_on_activation' );
			if ( ! empty( $is_cleared ) ) {
				return false;
			}
			$this->purge_all_cache();

			$is_cleared = $this->defend_wp_firewall_options->get_option( 'dfwp_clear_cache_plugins_cache_on_activation' );

			if ( ! empty( $is_cleared ) ) {
				return false;
			}

			printf(
				'<div class="%1$s">%2$s</div>',
				'notice notice-error is-dismissible defendwp-notice',
				"<p> <strong>DefendWP: Clear all your cache</strong> </p>
			<p>To ensure AJAX requests are handled properly, please clear all the caches. If you use a cache plugin or your hosting provider has a caching service, clear that as well.
			If you face any issues, please <a class='font-medium text-yellow-700 underline hover:text-yellow-600' href='mailto:help@defendwp.org?subject=Facing%20issue%20with%20page%20loading%20or%20data%20displaying&amp;body=I%20was%20facing%20issues%20with%20the%20website%20and%20disabled%20the%20'Add%20DefendWP%20nonce%20for%20all%20requests'%20as%20instructed.%20What%20next%3F'>contact us</a> </p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>"
			);
		}
	}

	public function dfwp_dismiss_cache_admin_notice() {
		defend_wp_firewall_verify_ajax_requests(); // This function handles nonce verification
		$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
	}

	public function clear_cache_on_setting_save( $new_settings, $old_settings ) {
		$old_value = ! empty( $old_settings['enable_defendwp_nonce'] ) ? $old_settings['enable_defendwp_nonce'] : '';
		$new_value = ! empty( $new_settings['enable_defendwp_nonce'] ) ? $new_settings['enable_defendwp_nonce'] : '';
		if ( $old_value !== $new_value ) {
			$this->purge_all_cache();
		}
	}

	/*
	* Public function, Will Clear all cache plugins
	*/
	public function purge_all_cache() {
		$this->delete_allwpfc_cache();
		$this->delete_allwp_super_cache();
		$this->delete_allw3_total_cache();
		$this->delete_allwp_rocket_cache();
		$this->delete_all_comet_cache();
		$this->delete_allautoptimize_cache();
		$this->delete_all_lite_speed_cache();
	}
	/*
	 * Public function, Will return the WpFastestCache is loaded or not
	 */

	public function check_wpfc_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ) {
			include_once WP_PLUGIN_DIR . '/wp-fastest-cache/wpFastestCache.php';
			if ( class_exists( 'WpFastestCache' ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Public function, Will return the WP Super cache Plugin is loaded or not
	 */

	public function check_wp_super_cache_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
			include_once WP_PLUGIN_DIR . '/wp-super-cache/wp-cache.php';
			if ( function_exists( 'wp_cache_clean_cache' ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Public function, Will return the W3 Total cache Plugin is loaded or not
	 */

	public function check_w3_total_cache_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
			include_once WP_PLUGIN_DIR . '/w3-total-cache/w3-total-cache.php';
			if ( function_exists( 'w3tc_flush_all' ) ) {
				return true;
			}
		}
		return false;
	}

	public function check_lite_speed_cache_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ) {
			@include_once WP_PLUGIN_DIR . '/litespeed-cache/litespeed-cache.php';
			return true;
		}
		return false;
	}

	/*
	 * Public function, Will return the Comet cache plugin is loaded or not
	 */

	public function check_comet_cache_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'comet-cache/comet-cache.php' ) ) {
				return true;
		}
		return false;
	}

	/*
	 * Public function, Will return the Comet cache plugin is loaded or not
	 */

	public function check_wp_rocket_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			@include_once WP_PLUGIN_DIR . '/wp-rocket/wp-rocket.php';
			if ( function_exists( 'rocket_clean_domain' ) && function_exists( 'rocket_clean_minify' ) && function_exists( 'rocket_clean_cache_busting' ) && function_exists( 'create_rocket_uniqid' ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Public function, Will return the Comet cache plugin is loaded or not
	 */

	public function check_autoptimize_plugin() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'autoptimize/autoptimize.php' ) ) {
			@include_once WP_PLUGIN_DIR . 'autoptimize/autoptimize.php';
			if ( class_exists( 'autoptimizeCache' ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will delete all cache files for WP Fastest Plugin
	 */

	public function delete_allwpfc_cache() {
		if ( $this->check_wpfc_plugin() ) {
			$wpfc = new Defend_WP_Firewall_WPFC_Cache();
			$wpfc->deleteMinifiedCache();
			if ( ! empty( $GLOBALS['defend_wp_firewall_wpfc_delete_cache'] ) && $GLOBALS['defend_wp_firewall_wpfc_delete_cache'] == true ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
				return array( 'success' => 'All cache files have been deleted' );
			} else {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', false );
				return array(
					'error'      => 'Unable to perform WP Fastest cache',
					'error_code' => 'wpfc_plugin_delete_cache',
				);
			}
		} else {
			return array(
				'error'      => 'WP fastest cache not activated',
				'error_code' => 'wpfc_plugin_is_not_activated',
			);
		}
	}

	/*
	 * This function will delete all cache files for WP Super Cache Plugin
	 */

	public function delete_allwp_super_cache() {
		if ( $this->check_wp_super_cache_plugin() ) {
			global $file_prefix;// This variable is defined in super cache plugin
			$wp_super_cache = wp_cache_clean_cache( $file_prefix, true );
			if ( $wp_super_cache == false ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', false );
				return array(
					'error'      => 'Unable to perform WP Super cache',
					'error_code' => 'wp_super_cache_plugin_delete_cache',
				);
			}
			$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			return array( 'success' => 'All cache files have been deleted' );
		} else {
			return array(
				'error'      => 'WP Super cache not activated',
				'error_code' => 'wp_super_cache_plugin_is_not_activated',
			);
		}
	}

	/*
	 * This function will delete all cache files for W3 Total Cache Plugin
	 */

	public function delete_allw3_total_cache() {
		if ( $this->check_w3_total_cache_plugin() ) {
			w3tc_flush_all();
			$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			return array( 'success' => 'All cache files have been deleted' );
		} else {
			return array(
				'error'      => 'W3 Total cache not activated',
				'error_code' => 'wp_super_cache_plugin_is_not_activated',
			);
		}
	}

	/*
	 * This function will delete all cache files for Comet Cache Plugin
	 */

	public function delete_all_comet_cache() {
		if ( $this->check_comet_cache_plugin() ) {
			require_once DEFEND_WP_FIREWALL_PATH . 'admin/comet-cache-class.php';
			$response = defend_wp_firewall_clear_comet_cache();
			if ( isset( $response['error'] ) ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', false );
			} else {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			}
			return $response;
		} else {
			return array(
				'error'      => 'Comet cache not activated',
				'error_code' => 'comet_cache_plugin_is_not_activated',
			);
		}
	}

	/*
	 * This function will delete all cache files for WP Rocket Plugin
	 */

	public function delete_allwp_rocket_cache() {
		if ( $this->check_wp_rocket_plugin() ) {
			$lang = '';
			// Remove all cache files.
			rocket_clean_domain( $lang );

			// Remove all minify cache files.
			rocket_clean_minify();

			// Remove cache busting files.
			rocket_clean_cache_busting();

			// Generate a new random key for minify cache file.
			$options                   = get_option( WP_ROCKET_SLUG );
			$options['minify_css_key'] = create_rocket_uniqid();
			$options['minify_js_key']  = create_rocket_uniqid();
			remove_all_filters( 'update_option_' . WP_ROCKET_SLUG );
			update_option( WP_ROCKET_SLUG, $options );
			$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			return array( 'success' => 'All cache files have been deleted' );
		} else {
			return array(
				'error'      => 'WP Rocket not activated',
				'error_code' => 'comet_cache_plugin_is_not_activated',
			);
		}
	}

	public function delete_allautoptimize_cache() {
		if ( $this->check_autoptimize_plugin() ) {
			$wp_auto_optimize = autoptimizeCache::clearall();
			if ( $wp_auto_optimize == false ) {
				$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', false );
				return array(
					'error'      => 'Unable to perform Autoptimize cache',
					'error_code' => 'auto_optimize_cache_plugin_delete_cache',
				);
			}
			$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			return array( 'success' => 'All cache files have been deleted' );
		} else {
			return array(
				'error'      => 'Autoptimize not activated',
				'error_code' => 'auto_optimize_plugin_is_not_activated',
			);
		}
	}

	public function delete_all_lite_speed_cache() {
		if ( $this->check_lite_speed_cache_plugin() ) {
			do_action( 'litespeed_purge_all' );// This action is defined in lite speed cache plugin
			$this->defend_wp_firewall_options->set_option( 'dfwp_clear_cache_plugins_cache_on_activation', true );
			return array( 'success' => 'Purged all caches successfully' );
		} else {
			return array(
				'error'      => 'Litespeed cache not activated',
				'error_code' => 'litespeed_cache_plugin_is_not_activated',
			);
		}
	}
}

if ( class_exists( 'WpFastestCache' ) ) {
	class Defend_WP_Firewall_WPFC_Cache extends WpFastestCache {

		public function __construct() {
			add_action( 'wpfc_delete_cache', array( $this, 'wpfc_delete_cache' ) );
		}

		public function deleteALLCache() {
			$this->deleteCacheToolbar();
		}

		public function deleteMinifiedCache() {
			$this->deleteCache();
		}

		public function wpfc_delete_cache() {
			$GLOBALS['defend_wp_firewall_wpfc_delete_cache'] = true;
		}
	}
}
