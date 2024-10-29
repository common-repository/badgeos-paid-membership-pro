<?php
/**
 * Plugin Name: BadgeOS Paid Membership Pro
 * Description: Award/Revoke BadgeOS achievements/ranks and points to Paid Membership Pro users according to their membership level.
 * Version: 1.0.0
 * Requires at least: 5.1
 * Author: BadgeOS
 * Author URI: https://badgeos.org
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bos-pmpro
 */

defined('ABSPATH') || exit;

//Define CONSTANTS
define( 'BOS_PMPRO_VERSION', '1.0.0' );
define( 'BOS_PMPRO_FILE', __FILE__ );
define( 'BOS_PMPRO_DIR', plugin_dir_path ( __FILE__ ) );
define( 'BOS_PMPRO_DIR_FILE', BOS_PMPRO_DIR . basename ( __FILE__ ) );
define( 'BOS_PMPRO_INCLUDES_DIR', trailingslashit ( BOS_PMPRO_DIR . 'includes' ) );
define( 'BOS_PMPRO_TEMPLATES_DIR', trailingslashit ( BOS_PMPRO_DIR . 'templates' ) );
define( 'BOS_PMPRO_BASE_DIR', plugin_basename(__FILE__));
define( 'BOS_PMPRO_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
define( 'BOS_PMPRO_ASSETS_URL', trailingslashit ( BOS_PMPRO_URL . 'assets' ) );

//Include required files
include_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/class-bos-pmpro-singleton.php';
include_once plugin_dir_path( __FILE__ ) . 'settings/options.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/badgeos/bos-pmpro-integration.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/badgeos/rules-engine.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/badgeos/steps-ui.php';

register_activation_hook( __FILE__, array( 'BOS_PMPRO_Integration', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BOS_PMPRO_Integration', 'deactivate' ) );
