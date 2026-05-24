<?php
/**
 * Plugin Name:       DropLock — Limit Purchases Per Customer
 * Plugin URI:        https://droplockwp.com
 * Description:       Limit a WooCommerce product to one (or N) per customer across all of their orders. Stops duplicate purchases on limited drops and one-per-customer products.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            DropLock
 * Author URI:        https://droplockwp.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       droplock
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:      9.4
 *
 * @package DropLock_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DROPLOCK_VERSION', '1.0.0' );
define( 'DROPLOCK_IS_LITE', true );
define( 'DROPLOCK_PLUGIN_FILE', __FILE__ );
define( 'DROPLOCK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DROPLOCK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DROPLOCK_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DROPLOCK_DB_VERSION', '1.0.0' );
define( 'DROPLOCK_LITE_LOG_CAP', 50 ); // Lite caps the log at 50 entries.

/**
 * Declare HPOS + Blocks compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				DROPLOCK_PLUGIN_FILE,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				DROPLOCK_PLUGIN_FILE,
				true
			);
		}
	}
);

require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-helper.php';
require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-logger.php';
require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-order-query.php';
require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-validator.php';
require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-admin.php';
require_once DROPLOCK_PLUGIN_DIR . 'includes/class-droplock-plugin.php';

register_activation_hook( __FILE__, array( 'DropLock_Plugin', 'on_activate' ) );
register_deactivation_hook( __FILE__, array( 'DropLock_Plugin', 'on_deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'DropLock requires WooCommerce to be installed and active.', 'droplock' );
					echo '</p></div>';
				}
			);
			return;
		}

		// If the Pro version is active, stand down — Pro supersedes Lite.
		if ( defined( 'DROPLOCK_PRO_VERSION' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-info"><p>';
					echo esc_html__( 'DropLock (free) is inactive because DropLock Pro is running on this site.', 'droplock' );
					echo '</p></div>';
				}
			);
			return;
		}

		DropLock_Plugin::instance();
	}
);
