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
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $contact_name The contact name to use for creating orders.
	 */
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->auth 		= $auth;
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

		$label['returnAddress'] = $return_address;
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

		$label['labelRequest']['bd']['shipmentItems' ][] = $item_info->item;

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
