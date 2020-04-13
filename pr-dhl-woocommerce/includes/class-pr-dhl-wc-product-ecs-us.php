<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Shipping Order.
 *
 * @package  PR_DHL_WC_Product
 * @category Product
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Product_eCS_US' ) ) :

class PR_DHL_WC_Product_eCS_US extends PR_DHL_WC_Product {

	public function get_manufacture_tooltip() {
		return __('Country of Manufacture. Mandatory for shipments exporting from China.', 'pr-shipping-dhl');
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function additional_product_settings() {

	}

	public function save_additional_product_settings( $post_id ) {
	    
	}

}

endif;
