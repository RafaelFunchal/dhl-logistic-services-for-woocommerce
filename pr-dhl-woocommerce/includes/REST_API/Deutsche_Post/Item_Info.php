<?php

namespace PR\DHL\REST_API\Deutsche_Post;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {
	/**
	 * The array of shipment information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipment;
	/**
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $recipient;
	/**
	 * The array of content item information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $contents;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $weightUom;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $weightUom = 'g' ) {
		$this->weightUom = $weightUom;
		$this->parse_args( $args, $weightUom );
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	protected function parse_args( $args ) {
		$settings = $args[ 'dhl_settings' ];
		$recipient_info = $args[ 'shipping_address' ] + $settings;
		$shipping_info = $args[ 'order_details' ] + $settings;
		$items_info = $args['items'];

		$this->shipment = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->recipient = Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
		$this->contents = array();

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipment_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_label_ref'       => array(
				'rename' => 'label_ref',
				'sanitize' => function( $label_ref ) use ($self) {

					return $self->string_length_sanitization( $label_ref, 28 );
				}
			),
            'dhl_label_ref_2'       => array(
				'rename' => 'label_ref_2',
				'sanitize' => function( $label_ref_2 ) use ($self) {

					return $self->string_length_sanitization( $label_ref_2, 28 );
				}
			),
            'dhl_product'       => array(
				'rename' => 'product',
				'error'  => __( 'DHL "Product" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_service_level' => array(
				'rename'   => 'service_level',
				'default'  => 'STANDARD',
				'validate' => function( $level ) {
					if ( $level !== 'STANDARD' && $level !== 'PRIORITY' && $level !== 'REGISTERED' ) {
						throw new Exception( __( 'Order "Service Level" is invalid', 'pr-shipping-dhl' ) );
					}
				},
			),
			'weight'            => array(
				'error'    => __( 'Order "Weight" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $weight ) {
					if ( ! is_numeric( $weight ) ) {
						throw new Exception( __( 'The order "Weight" must be a number', 'pr-shipping-dhl' ) );
					}
				},
				'sanitize' => function ( $weight ) use ($self) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
					$weight = $self->int_min_max_sanitization( $weight, 1, 2000 );

					return $weight;
				}
			),
			'currency'          => array(
				'error' => __( 'Shop "Currency" is empty!', 'pr-shipping-dhl' ),
			),
			'total_value'       => array(
				'rename' => 'value',
				'error'  => __( 'Shipment "Value" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( __( 'The order "value" must be a number', 'pr-shipping-dhl' ) );
					}
				},
				'sanitize' => function( $value ) use ($self) {

					return $self->float_round_sanitization( $value, 1 );
				}
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_recipient_info_schema() {
		return array(
			'name'      => array(
				'default' => '',
				'error'  => __( 'Recipient is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $name ) use ($self) {

					return $self->string_length_sanitization( $name, 30 );
				}
			),
			'phone'     => array(
				'default' => '',
				'sanitize' => function( $phone ) use ($self) {

					return $self->string_length_sanitization( $phone, 15 );
				}
			),
			'email'     => array(
				'default' => '',
				'sanitize' => function( $email ) use ($self) {

					return $self->string_length_sanitization( $email, 50 );
				}
			),
			'address_1' => array(
				'error' => __( 'Shipping "Address 1" is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $address_1 ) use ($self) {

					return $self->string_length_sanitization( $address_1, 30 );
				}
			),
			'address_2' => array(
				'default' => '',
				'sanitize' => function( $address_2 ) use ($self) {

					return $self->string_length_sanitization( $address_2, 30 );
				}
			),
			'city'      => array(
				'error' => __( 'Shipping "City" is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $city ) use ($self) {

					return $self->string_length_sanitization( $$city, 30 );
				}
			),
			'postcode'  => array(
				'default' => '',
				'sanitize' => function( $postcode ) use ($self) {

					return $self->string_length_sanitization( $postcode, 20 );
				}
			),
			'state'     => array(
				'default' => '',
				'sanitize' => function( $state ) use ($self) {

					return $self->string_length_sanitization( $state, 20 );
				}
			),
			'country'   => array(
				'error' => __( 'Shipping "Country" is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $country ) use ($self) {

					return $self->string_length_sanitization( $country, 2 );
				}
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_content_item_info_schema()
	{
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'     => array(
				'default'  => '',
				'validate' => function( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if (empty($length)) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							__( 'Item HS Code must be between 0 and 20 characters long', 'pr-shipping-dhl' )
						);
					}
				},
			),
			'item_description' => array(
				'rename' => 'description',
				'default' => '',
				'sanitize' => function( $description ) use ($self) {

					return $self->string_length_sanitization( $description, 33 );
				}
			),
			'product_id'  => array(
				'default' => '',
				'sanitize' => function( $product_id ) use ($self) {

					return $self->int_min_max_sanitization( $product_id, 1, 999999999 );
				}
			),
			'sku'         => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename' => 'value',
				'default' => 0,
				'sanitize' => function( $value ) use ($self) {

					return $self->float_round_sanitization( $value, 2 );
				}
			),
			'origin'      => array(
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'         => array(
				'default' => 1,
				'sanitize' => function( $qty ) use ($self) {

					return $self->int_min_max_sanitization( $qty, 1, 99 );
				}
			),
			'item_weight'      => array(
				'rename' => 'weight',
				'default' => 1,
				'sanitize' => function ( $weight ) use ($self) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
					$weight = $self->int_min_max_sanitization( $weight, 1, 2000 );

					return $weight;
				}
			),
		);
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( $weight );

		switch ( $uom ) {
			case 'kg':
				return $weight * 1000;

			case 'lb':
				return $weight / 2.2;

			case 'oz':
				return $weight / 35.274;
		}

		return $weight;
	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = floatval( $float );

		return round( $float, $numcomma);
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if( strlen( $string ) <= $max ){

			return $string;
		}

		return substr( $string, 0, ( $max-1 ));
	}

	protected function int_min_max_sanitization( $int, $min, $max ) {

		if( !is_numeric( $int ) ){

			$int = intval( $int );

		}

		if( $int > intval( $max ) ){

			$int = $max;

		}elseif( $int < intval( $min ) ){

			$int = $min;

		}

		return $int;
	}
}
