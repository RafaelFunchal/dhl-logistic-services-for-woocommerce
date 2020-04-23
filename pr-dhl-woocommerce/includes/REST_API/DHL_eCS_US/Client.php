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
	 * The pickup id.
	 *
	 * @since [*next-version*]
	 *
	 * @var String
	 */
	protected $pickup_id;

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
	public function __construct( $pickup_id, $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->auth 		= $auth;
		$this->pickup_id 	= $pickup_id;
		$this->weight_uom 	= get_option('woocommerce_weight_unit');
	}

	/**
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_label( Item_Info $item_info ){

		$route 		= $this->create_label_route( $item_info->shipment['label_format'] );
		$data 		= $this->item_info_to_request_data( $item_info );

		$response 			= $this->post($route, $data, $this->header_request() );
		$decoded_response 	= json_decode( $response->body );
		
		if ( $response->status === 200 ) {
			
			return $this->get_label_content( $decoded_response );

		}

		throw new Exception(
			sprintf(
				__( 'Failed to create label: %s', 'pr-shipping-dhl' ),
				$this->generate_error_details( $decoded_response )
			)
		);
	}

	/**
	 * Retrieves the label for a DHL, by its barcode.
	 *
	 * @param string $item_barcode The barcode of the item whose label to retrieve.
	 *
	 * @return string The raw PDF data for the item's label.
	 *
	 * @throws Exception
	 */
	public function get_label( $package_id ){

		$route 		= $this->get_label_route();
		$data 		= array( 'packageId' => $package_id );

		$response 			= $this->get($route, $data, $this->header_request( false ) );
		$decoded_response 	= json_decode( $response->body, true );
		
		if ( $response->status === 200 ) {

			return $this->get_label_content( $decoded_response );
		}

		throw new Exception(
			sprintf(
				__( 'Failed to create label: %s', 'pr-shipping-dhl' ),
				$this->generate_error_details( $decoded_response )
			)
		);
	}

	public function get_label_content( $response ){

		if( !isset( $response['labels'] ) ){
			throw new Exception( __( 'Label contents are not exist!', 'pr-shipping-dhl' ) );
		}

		foreach( $response['labels'] as $label ){
			if( !isset( $label['labelData'] ) ){
				throw new Exception( __( 'Label data is not exist!', 'pr-shipping-dhl' ) );
			}

			if( !isset( $label['packageId'] ) ){
				throw new Exception( __( 'Package ID is not exist!', 'pr-shipping-dhl' ) );
			}

			if( !isset( $label['dhlPackageId'] ) ){
				throw new Exception( __( 'DHL Package ID is not exist!', 'pr-shipping-dhl' ) );
			}

			return $label;
		}

	}

	public function generate_error_details( $response ){

		$error_exception 	= '';
		$error_details 		= '';

		foreach( $response as $key => $data ){

			if( $key == 'title' ){
				$error_exception .= $data . '<br />';
			}

			if( $key != 'title' && $key != 'type' ){
				if( is_array( $data ) ){
					
					$detail_string = '';
					
					foreach( $data as $detail_key => $detail ){

						$detail_string .= $detail_key . ': ' . $detail;
						
					}

					$error_details .= '<li>' . $detail_string . '</li>';
				}
				
			}
		}

		$error_exception .= '<ul>' . $error_details . '</ul>';

		return $error_exception;
	}

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {

		$package_id = $item_info->shipment['prefix'] . sprintf( '%07d', $item_info->shipment['order_id'] );

		$request_data = array(
			'pickup' 				=> $this->pickup_id,
			'distributionCenter'	=> $item_info->shipment['distribution_center'],
			'orderedProductId' 		=> $item_info->shipment['product_code'],
			'consigneeAddress' 		=> $item_info->consignee,
			'returnAddress' 		=> $item_info->shipper,
			'packageDetail' 		=> array(
				'packageId' 	=> $package_id,
				'weight' 		=> array(
					'value' 		=> $item_info->shipment['weight'],
					'unitOfMeasure'	=> $item_info->shipment['weightUom'],
				),
				'shippingCost' 			=> array(
					'currency' 		=> $item_info->shipment['currency'],
					'dutiesPaid'	=> $item_info->shipment['duties']
				),
			),
		);

		if( $item_info->isCrossBorder ){

			$contents 			= $item_info->contents;
			$shipment_contents 	= array();

			foreach( $contents as $content ){

				$shipment_content = array(
					'skuNumber' 			=> $content['sku'],
					'itemDescription'		=> $content['description'],
					'itemValue' 			=> $content['value'],
					'packagedQuantity' 		=> $content['qty'],
					'countryOfOrigin' 		=> $content['origin'],
					'currency' 				=> $item_info->shipment['currency'],
				);

				if( !empty( $content['hs_code'] ) ){
					$shipment_content['hsCode'] = $content['hs_code'];
				}

				$shipment_contents[] = $shipment_content;

			}

			$request_data['customsDetails'] = $shipment_contents;

		}

		return Args_Parser::unset_empty_values( $request_data );
	}

	/**
	 * Create manifest
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_manifest( $pickup, $package_id ){

		$route 		= $this->create_manifest_route();
		$data 		= array(
			'pickup' 		=> $pickup,
			'manifests' 	=> array(
				array( 
					'packageIds' => array(  $package_id )
				)
			)
		);

		$response 			= $this->post($route, $data, $this->header_request() );
		$decoded_response 	= json_decode( $response->body );
		
		if ( $response->status === 200 ) {
			
			return $decoded_response;

		}

		throw new Exception(
			sprintf(
				__( 'Failed to create label: %s', 'pr-shipping-dhl' ),
				$decoded_response->title
			)
		);
	}

	public function download_manifest( $request_id ){

	}

	public function header_request( $content_type = true ){

		$headers 	= array(
			'Accept' 		=> 'application/json',
		);

		if( $content_type == true ){
			$headers['Content-Type'] = 'application/json';
		}
		return $headers;
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
	protected function create_label_route( $format ) {
		return sprintf( 'shipping/v4/label?format=%s', $format );
	}

	/**
	 * Prepares an API route with the package id and pickup id.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The route to prepare.
	 *
	 * @return string
	 */
	protected function get_label_route() {
		return sprintf( 'shipping/v4/label/%s', $this->pickup_id );
	}

	/**
	 * Prepares a manifest API route.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	protected function create_manifest_route() {
		return 'shipping/v4/manifest';
	}

	/**
	 * Prepares a manifest API route.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	public function get_manifest_route() {
		return sprintf( $this->create_manifest_route() . '/%s', $this->pickup_id );
	}

}
