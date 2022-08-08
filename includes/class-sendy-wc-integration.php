<?php

/**
 * Add Customers to Sendy List from WooCommerce
 *
 * @package  Sendy_WC_Integration
 * @category Integration
 * @author   Oak & Buebird
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if ( !class_exists('Sendy_WC_Integration') ) :

	class Sendy_WC_Integration extends WC_Integration
	{

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->id                 = 'sendy_wc_integration_settings';
			$this->method_title       = __('Sendy Settings', 'woo-sendy');
			$this->method_description = __('Adds WooCommerce Customers to Sendy List', 'woo-sendy');

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->sendy_url         = $this->get_option('sendy_url');
			$this->sendy_list        = $this->get_option('sendy_list');
			$this->sendy_api_key     = $this->get_option('sendy_api_key');

			// Actions.
			add_action( 'woocommerce_update_options_integration_' . $this->id, 	array($this, 'process_admin_options') );
			

			add_action( 'woocommerce_review_order_before_submit', 				array($this, 'add_opt_in_checkbox') );
			add_action( 'woocommerce_checkout_create_order', 					array($this, 'create_order'), 10, 2 );
			add_action( 'woocommerce_order_status_completed', 					array($this, 'add_to_sendy_mailer') );
		}


		/**
		 * Initialize integration settings form fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'sendy_url' => array(
					'title'             => __('Sendy URL', 'woo-sendy'),
					'type'              => 'url',
					'description'       => __('URL of your Sendy installtion', 'woo-sendy'),
					'desc_tip'          => true,
					'default'           => ''
				),
				'sendy_api_key' => array(
					'title'             => __('Sendy API Key', 'woo-sendy'),
					'type'              => 'text',
					'default'           => '',
					'desc_tip'          => true,
					'description'       => __('Add your Sendy API Key', 'woo-sendy'),
				),
				'sendy_list' => array(
					'title'             => __('Sendy List ID', 'woo-sendy'),
					'type'              => 'text',
					'default'           => '',
					'desc_tip'          => true,
					'description'       => __('Add ID of your Sendy list', 'woo-sendy'),
				)
			);
		}


		public function add_opt_in_checkbox() {
			woocommerce_form_field( 'sendy_subscribe', array(
				'type'          => 'checkbox',
				'class'         => array(),
				'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
				'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
				'required'      => false,
				'label'         => 'I would like to receive updates about freebies, new icon packs and promotions',
				'default'		=> 1
			));  
		}


		public function add_to_sendy_mailer( $order_id ) {
			$order 			= new WC_Order($order_id);
			$url 			= rtrim($this->sendy_url, "/" );
			$sendy_url 		= $url . '/subscribe';

			if ( !$url || !$this->sendy_list || !$this->sendy_api_key ) {
				return;
			}

			$allowSubscribe = 1 == $order->get_meta('_sendy_subscribe');

			// Don't subscribe customer without permission
			if ( !$allowSubscribe ) {
				return;
			}

			$sendy_data = array(
				'name' 			=> $order->billing_first_name . ' ' . $order->billing_last_name,
				'email' 		=> $order->billing_email,
				'list' 			=> $this->sendy_list,
				'api_key' 		=> $this->sendy_api_key,
				'boolean' 		=> 'true',
			);

			
			$result 		= wp_remote_post($sendy_url, array('body' => $sendy_data));
			$result 		= $result['body'];

			if ( $result == "1" ) {
				$order->add_order_note('Sendy: Customer ' . $order->billing_email . ' added to the list');
			} elseif ($result == "Already subscribed.") {
				$order->add_order_note('Sendy: Customer ' . $order->billing_email . ' is already in the list');
			} else {
				$order->add_order_note('Sendy: Failed to add ' . $order->billing_email . ' in the list. Error: ' . $result);
			}
		}


		public function create_order( \WC_Order $order, $data ) {
			if ( isset( $_POST['sendy_subscribe'] ) && 1 == $_POST['sendy_subscribe'] ) {
				$order->add_meta_data( '_sendy_subscribe', 1, true );
			}
		}


		/**
		 * Validate URL Field.
		 *
		 * Make sure the data is escaped correctly, etc.
		 *
		 * @param  string $key Field key.
		 * @param  string $value Posted Value.
		 * @return string
		 */
		public function validate_url_field( $key, $value ) {
			$value = is_null( $value ) ? '' : $value;
			return trim( esc_url_raw( $value ) );
		}
	}

endif;
