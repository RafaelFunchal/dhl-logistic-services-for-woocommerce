<?php

// Exit if accessed directly or class already exists
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Freight\Auth;
use PR\DHL\REST_API\Freight\Client;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Freight_Post', false ) ) {
    return;
}

class PR_DHL_API_Freight_Post extends PR_DHL_API
{
    const API_URL_PRODUCTION = 'https://test-api.freight-logistics.dhl.com/';

    const API_URL_SANDBOX = 'https://test-api.freight-logistics.dhl.com/';

    /**
     * The API driver instance.
     *
     * @since [*next-version*]
     *
     * @var API_Driver_Interface
     */
    public $api_driver;
    /**
     * The API authorization instance.
     *
     * @since [*next-version*]
     *
     * @var Auth
     */
    public $api_auth;
    /**
     * The API client instance.
     *
     * @since [*next-version*]
     *
     * @var Client
     */
    public $api_client;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string $country_code The country code.
     *
     * @throws Exception If an error occurred while creating the API driver, auth or client.
     */
    public function __construct( $country_code ) {
        $this->country_code = $country_code;

        try {
            $this->api_driver = $this->create_api_driver();
            $this->api_auth = $this->create_api_auth();
            $this->api_client = $this->create_api_client();
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    public function is_dhl_freight() {
        return true;
    }

    public function get_settings()
    {
        return get_option('woocommerce_pr_dhl_fr_settings', []);
    }

    public function get_dhl_products_international()
    {
        return [];
    }

    public function get_dhl_products_domestic()
    {
        return [
            103 => __('Service Point')
        ];
    }

    /**
     * Initializes the API client instance.
     *
     * @return Client
     *
     * @throws Exception If failed to create the API client.
     */
    protected function create_api_client() {
        // Create the API client, using this instance's driver and auth objects
        return new Client(
            $this->get_api_url(),
            $this->api_driver,
            $this->api_auth
        );
    }

    /**
     * Initializes the API driver instance.
     *
     * @since [*next-version*]
     *
     * @return API_Driver_Interface
     *
     * @throws Exception If failed to create the API driver.
     */
    protected function create_api_driver() {
        // Use a standard WordPress-driven API driver to send requests using WordPress' functions
        $driver = new WP_API_Driver();

        // This will log requests given to the original driver and log responses returned from it
        $driver = new Logging_Driver( PR_DHL(), $driver );

        // This will prepare requests given to the previous driver for JSON content
        // and parse responses returned from it as JSON.
        $driver = new JSON_API_Driver( $driver );

        //, decorated using the JSON driver decorator class
        return $driver;
    }

    /**
     * Initializes the API auth instance.
     *
     * @throws Exception If failed to create the API auth.
     */
    protected function create_api_auth() {
        return new Auth(
            $this->get_client_key()
        );
    }

    /**
     * Get Freight service points
     */
    public function get_dhl_freight_service_points($args) {
        $defaultAddress = [
            'street' => '',
            'cityName' => '',
            'postalCode' => '',
            'countryCode' => 'SE',
        ];

        $params = [
            'address' => array_merge($defaultAddress, $args)
        ];

        return $this->api_client->get_service_points($params);
    }

    /**
     * Get Freight Additional Services
     *
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function get_dhl_freight_products($args = []) {
        $defaultParams = [
            'toCountryCode' => 'SE'
        ];

        $params = array_merge($defaultParams, $args);

        return $this->api_client->get_products(103, $params);
    }

    /**
     * @param array $args
     * @return stdClass|string
     * @throws Exception
     */
    public function dhl_valid_postal_code($args = []) {
        $default = [
            'countryCode' => 'SE',
            'postalCode' => '',
            'city' => '',
        ];

        $params = array_merge($default, $args);

        return $this->api_client->validate_postal_code($params);
    }

    /**
     * Retrieves the API URL.
     *
     * @since [*next-version*]
     *
     * @return string
     *
     * @throws Exception If failed to determine if using the sandbox API or not.
     */
    public function get_api_url() {
        $is_sandbox = $this->get_setting( 'dhl_sandbox' );
        $is_sandbox = filter_var($is_sandbox, FILTER_VALIDATE_BOOLEAN);
        $api_url = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

        return $api_url;
    }

    /**
     * Retrieves the API credentials.
     *
     * @return string The rest api client key
     *
     * @throws Exception If failed to retrieve the API credentials.
     */
    public function get_client_key() {
        return $this->get_setting( 'dhl_client_key' );
    }

    /**
     * Retrieves a single setting.
     *
     * @param string $key     The key of the setting to retrieve.
     * @param string $default The value to return if the setting is not saved.
     *
     * @return mixed The setting value.
     */
    public function get_setting( $key, $default = '' ) {
        $settings = $this->get_settings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }
}
