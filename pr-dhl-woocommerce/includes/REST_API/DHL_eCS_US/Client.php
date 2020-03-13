<?php

namespace PR\DHL\REST_API\DHL_eCS_US;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use stdClass;

/**
 * The API client for DHL eCS.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * The api auth.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Auth_Interface
	 */
	protected $auth;

	/**
	 * The pickup address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $pickup_address;

	/**
	 * The shipper address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $shipper_address;

	/**
	 * The default weight unit of measure.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $weight_uom;

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $contact_name The contact name to use for creating orders.
	 */
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->auth 		= $auth;
		$this->weight_uom 	= get_option('woocommerce_weight_unit');
	}

	/**
	 * Get Default value for the label info 
	 * 
	 * @return array
	 */
	protected function get_default_label_info() {

		return array(
			'pickup' 				=> '',
			'distributionCenter' 	=> '',
			'orderedProductId'		=> '',
			'consigneeAddress' 		=> array(),
			'returnAddress' 		=> array(),
			'packageDetail' 		=> array(),
		);
	}

	/**
	 * Get Default value for the international label info 
	 * 
	 * @return array
	 */
	protected function get_default_intl_label_info() {

		$label_info 					= $this->get_default_label_info();

		$label_info['pickupAddress'] 	= array();
		$label_info['shipperAddress'] 	= array();
		
		return $label_info;
	}

	/**
	 * Retrieves the current DHL order, or an existing one if an ID is given.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|null $orderId Optional DHL order ID.
	 *
	 * @return array
	 */
	public function get_shipping_label($orderId = null)
	{
		$current = get_option( 'pr_dhl_ecs_us_label', $this->get_default_label_info() );

		if (empty($orderId)) {
			return $current;
		}

		return get_option( 'pr_dhl_ecs_us_label_' . $orderId, $current );
	}

	/**
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_shipping_label( $order_id ){

		$route 	= $this->shipping_label_route();
		$data 	= $this->get_shipping_label( $order_id );
		
		$response = $this->post($route, $data);
		error_log( print_r( $response, true ) );
		if ( $response->status === 200 ) {
			
			return $response->body;

		}

		throw new Exception(
			sprintf(
				__( 'Failed to create order: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Update accountID
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_account_id( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->get_shipping_label();

		$label['pickup'] = $settings['dhl_pickup_id'];

		update_option( 'pr_dhl_ecs_us_label', $label );
	}

	/**
	 * Update consignee address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_consignee_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		$consignee_address =  array(
			"name" 			=> $settings['dhl_contact_name'],
			"companyName" 	=> $settings['dhl_contact_name'],
			"address1" 		=> $settings['dhl_address_1'],
			"address2" 		=> $settings['dhl_address_2'],
			"city" 			=> $settings['dhl_city'],
			"state" 		=> $settings['dhl_state'],
			"country" 		=> $settings['dhl_country'],
			"postalCode" 	=> $settings['dhl_postcode'],
			"phone"			=> $settings['dhl_phone'],
			"email" 		=> $settings['dhl_email']	
		);

		$label = $this->get_shipping_label();

		$label['consigneeAddress'] = $consignee_address;
		update_option( 'pr_dhl_ecs_us_label', $label );


	}

	/**
	 * Update return address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_return_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		$return_address =  array(
			"name" 			=> $settings['dhl_contact_name'],
			"address1" 		=> $settings['dhl_address_1'],
			"address2" 		=> $settings['dhl_address_2'],
			"address3" 		=> '',
			"city" 			=> $settings['dhl_city'],
			"state" 		=> $settings['dhl_state'],
			"country" 		=> $settings['dhl_country'],
			"postalCode" 	=> $settings['dhl_postcode'],
			"phone"			=> $settings['dhl_phone'],
			"email" 		=> $settings['dhl_email']	
		);

		$label = $this->get_shipping_label();

		$label['returnAddress'] = $return_address;
		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Update pickup address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_pickup_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		if( isset( $settings['dhl_contact_name'] ) && !empty( $settings['dhl_contact_name'] ) ){
			$store_name 	= $settings['dhl_contact_name'];
		}else{
			$store_name 	= get_bloginfo('name');
		}

		// The main address pieces:
		$store_address     = get_option( 'woocommerce_store_address' );
		$store_address_2   = get_option( 'woocommerce_store_address_2' );
		$store_city        = get_option( 'woocommerce_store_city' );
		$store_postcode    = get_option( 'woocommerce_store_postcode' );

		// The country/state
		$store_raw_country = get_option( 'woocommerce_default_country' );

		// Split the country/state
		$split_country = explode( ":", $store_raw_country );

		// Country and state separated:
		$store_country = $split_country[0];
		$store_state   = $split_country[1];

		$address =  array(
			"name" 			=> $store_name,
			"address1" 		=> $store_address,
			"address2" 		=> $store_address_2,
			"address3" 		=> '',
			"city" 			=> $store_city,
			"state" 		=> $store_state,
			"country" 		=> $store_country,
			"postalCode" 	=> $store_postcode,
		);

		$label = $this->get_shipping_label();

		$label['pickupAddress'] = $address;
		update_option( 'pr_dhl_ecs_us_label', $label );


	}

	/**
	 * Update shipper address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_shipper_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		if( isset( $settings['dhl_contact_name'] ) && !empty( $settings['dhl_contact_name'] ) ){
			$store_name 	= $settings['dhl_contact_name'];
		}else{
			$store_name 	= get_bloginfo('name');
		}

		// The main address pieces:
		$store_address     = get_option( 'woocommerce_store_address' );
		$store_address_2   = get_option( 'woocommerce_store_address_2' );
		$store_city        = get_option( 'woocommerce_store_city' );
		$store_postcode    = get_option( 'woocommerce_store_postcode' );

		// The country/state
		$store_raw_country = get_option( 'woocommerce_default_country' );

		// Split the country/state
		$split_country = explode( ":", $store_raw_country );

		// Country and state separated:
		$store_country = $split_country[0];
		$store_state   = $split_country[1];

		$settings = $args[ 'dhl_settings' ];

		$address =  array(
			"name" 			=> $store_name,
			"address1" 		=> $store_address,
			"address2" 		=> $store_address_2,
			"address3" 		=> '',
			"city" 			=> $store_city,
			"state" 		=> $store_state,
			"country" 		=> $store_country,
			"postalCode" 	=> $store_postcode,
		);

		$label = $this->get_shipping_label();

		$label['shipperAddress'] = $address;
		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Update distribution center from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_distribution_center( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->get_shipping_label();

		$label['distributionCenter'] = $settings['dhl_distribution_center'];
		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Update ordered dhl product id from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_dhl_product_id( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->get_shipping_label();

		$label['orderedProductId'] = $settings['dhl_product_id'];
		
		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Update package details from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_package_details( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->get_shipping_label();

		$order_id 		= $args[ 'order_details' ][ 'order_id' ];
		$order 			= wc_get_order( $order_id );

		$weight_uom 	= $this->get_weight_uom();
		$currency 		= $order->get_currency();
		$shipping_total = $order->get_shipping_total();

		$total_weight 	= 0;

		foreach( $order->get_items() as $item_id => $item_line ){

			$product_id 	= $item_line->get_product_id();
			$product 		= wc_get_product( $product_id );

			$quantity 		= $item_line->get_quantity();

			$weight 		= absint( $product->get_weight() ) < 1? 1 : absint( $product->get_weight() );

			$total_weight 	+= ( $weight * $quantity );
		
		}

		$label['packageDetail'] = array(
			'packageId' 			=> '',
			'packageDescription' 	=> '',
			'weight' 				=> array(
				"value" 			=> $total_weight,
				'unitOfMeasure' 	=> $weight_uom
			),
			'service' 				=> 'DELCON',
			'serviceEndorsement' 	=> 1,
			'billingReference1' 	=> '',
			'billingReference2' 	=> '',
			'shippingCost' 			=> array(
				'currency' 			=> $currency,
				'freight' 			=> 0,
				'declaredValue' 	=> $shipping_total,
				'insuredValue' 		=> $shipping_total,
				'dutiesPaid' 		=> false
			)
		);
		
		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Get weight unit of measure.
	 *
	 * @since [*next-version*]
	 *
	 * @return String.
	 *
	 */
	public function get_weight_uom(){

		$weight 	= strtoupper( $this->weight_uom );

		if( $weight == 'LBS' ){
			$weight = 'LB';
		}

		return $weight;
		
	}

	/**
	 * Add all items to the current label.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @return stdClass The item information as returned by the remote API.
	 *
	 */
	public function add_items( array $args ) {

		$label = $this->get_shipping_label();

		$order_id 		= $args[ 'order_details' ][ 'order_id' ];
		$order 			= wc_get_order( $order_id );

		// The country/state
		$store_raw_country = get_option( 'woocommerce_default_country' );

		// Split the country/state
		$split_country = explode( ":", $store_raw_country );

		// Country and state separated:
		$store_country = $split_country[0];
		
		$total_weight 	= 0;
		$total_height 	= 0;
		$total_width 	= 0;
		$total_length 	= 0;

		foreach( $order->get_items() as $item_id => $item_line ){

			$item 			= array();
			$product_id 	= $item_line->get_product_id();
			$product 		= wc_get_product( $product_id );
			
			$quantity 		= $item_line->get_quantity();

			$item['itemDescription'] 	= $product->get_name();
			$item['countryOfOrigin'] 	= $store_country;
			$item['hsCode'] 			= '';
			$item['packagedQuantity'] 	= $quantity;
			$item['skuNumber'] 			= $product->get_sku();
			$item['itemValue'] 			= $item_line->get_total();
			$item['currency'] 			= get_woocommerce_currency();
			
			$label['customsDetails'][] = $item;
		}

		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Add an item to the current.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @return stdClass The item information as returned by the remote API.
	 *
	 */
	public function add_item( Item_Info $item_info ) {

		$label = $this->get_shipping_label();

		$label['customsDetails'][] = $item_info->item;

		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Update the token to the current DHL shipping label.
	 *
	 * @since [*next-version*]
	 *
	 */
	public function update_access_token(){

		$token 	= $this->auth->load_token();

		$label = $this->get_shipping_label();

		$label['labelRequest']['hdr']['accessToken'] = $token->token;

		update_option( 'pr_dhl_ecs_us_label', $label );

	}

	/**
	 * Resets the current shipping label.
	 *
	 * @since [*next-version*]
	 */
	public function reset_current_shipping_label(){

		update_option( 'pr_dhl_ecs_us_label', $this->get_default_label_info() );

	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The route to prepare.
	 *
	 * @return string
	 */
	protected function shipping_label_route() {
		return 'shipping/v4/label';
	}

}
