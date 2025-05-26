<?php
/**
 * DefendWP Firewall
 *
 * @link              https://revmakx.com
 * @since             1.0.0
 * @package           Defend_WP_Firewall
 *
 * @wordpress-plugin
 * Plugin Name:       DefendWP Firewall
 * Plugin URI:        https://defendwp.org
 * Description:       Defend your WordPress sites with free instant patches for disclosed vulnerabilities in the WP core, plugins and themes.
 * Version:           1.1.5
 * Author:            Revmakx
 * Author URI:        https://revmakx.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       defend-wp-firewall
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'DEFEND_WP_FIREWALL_MAIN_FILE', __FILE__ );
define( 'DEFEND_WP_FIREWALL_PATH', plugin_dir_path( DEFEND_WP_FIREWALL_MAIN_FILE ) );
define(
	'DEFEND_WP_FIREWALL_BASENAME',
	function_exists( 'plugin_basename' ) ? plugin_basename( __FILE__ ) :
	basename( __DIR__ ) . '/' . basename( __FILE__ )
);

require DEFEND_WP_FIREWALL_PATH . 'defend-wp-firewall-constants.php';
require_once DEFEND_WP_FIREWALL_PATH . 'includes/class-defend-wp-firewall-activation-controller.php';
$constants = new Defend_WP_Firewall_Constants();
$constants->init_live_plugin();

require DEFEND_WP_FIREWALL_PATH . 'defend-wp-firewall-debug.php';

require DEFEND_WP_FIREWALL_PATH . 'includes/class-defend-wp-firewall.php';

$defendwp_firewall = new Defend_WP_Firewall();
$defendwp_firewall->run();

register_activation_hook( __FILE__, array( $defendwp_firewall, 'activation' ) );
register_deactivation_hook( __FILE__, array( $defendwp_firewall, 'deactivate' ) );
