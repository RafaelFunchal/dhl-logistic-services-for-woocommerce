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
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_label( Item_Info $item_info ){

		$token 		= $this->auth->load_token();
		$route 		= $this->create_label_route( $item_info->shipment['label_format'] );
		$data 		= $this->item_info_to_request_data( $item_info );
		$headers 	= array(
			'Accept' 		=> 'application/json',
			'Content-Type'	=> 'application/json'
		);

		$response 			= $this->post($route, $data, $headers);
		$decoded_response 	= json_decode( $response->body );
		error_log( print_r( $decoded_response, true ) );
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

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {

		$package_id 			= $item_info->shipment['prefix'] . sprintf('%07d', $item_info->shipment['order_id'] );

		$request_data = array(
			'pickup' 				=> $item_info->shipment['pickup_id'],
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
	 * Retrieves the label for a DHL item, by its barcode.
	 *
	 * @param string $item_barcode The barcode of the item whose label to retrieve.
	 *
	 * @return string The raw PDF data for the item's label.
	 *
	 * @throws Exception
	 */
	public function get_label( $pickup_id, $package_id )
	{

		$response = $this->get(
			$this->get_label_route( $pickup_id ),
			array(
				'packageId' => $package_id
			),
			array(
				'Accept' => 'application/pdf'
			)
		);

		if ($response->status === 200) {
			return $response->body;
		}

		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: strval( $response->body );

		throw new Exception(
			sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
		);
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
	protected function get_label_route( $pickup_id ) {
		return sprintf( 'shipping/v4/label/%s', $pickup_id );
	}

}
