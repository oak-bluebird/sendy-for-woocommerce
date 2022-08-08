<?php
/*
Plugin Name: Sendy for WooCommerce
Plugin URI: https://obluebird.com/
Description: Add Customers to Sendy List from WooCommerce
Version: 1.0
Author: Oak & Bluebird
Author URI: https://obluebird.com/
Text Domain: woo-sendy
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}

if ( !class_exists('Sendy_Woocommerce') ) :

	class Sendy_Woocommerce {

		/**
		 * Construct the plugin.
		 */
		public function __construct() {
			add_action('plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Initialize the plugin.
		 */
		public function init() {
			// Checks if WooCommerce is installed.
			if ( class_exists('WC_Integration') ) {
				// Include our integration class.
				include_once 'includes/class-sendy-wc-integration.php';

				// Register the integration.
				add_filter('woocommerce_integrations', array($this, 'add_integration'));
			} else {
				// throw an admin error if you like
			}
		}

		/**
		 * Add a new integration to WooCommerce.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'Sendy_WC_Integration';
			return $integrations;
		}
	}

	$Sendy_Woocommerce = new Sendy_Woocommerce();

endif;
