<?php

namespace PR\DHL\REST_API\Freight;

use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

class Client extends API_Client
{
    /**
     * The customer client key.
     *
     * @var string
     */
    protected $clientKey;

    /**
     * Client constructor.
     * @param string $base_url Base API route;
     * @param API_Driver_Interface $driver
     * @param API_Auth_Interface|null $auth
     */
    public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
        parent::__construct( $base_url, $driver, $auth );
    }

    public function get_products($product_code, $params)
    {
        $response = $this->get('productapi/v1/products/' . $product_code, $params);

        if ($response->status === 200 && isset($response->body->additionalServices)) {
            return $response->body->additionalServices;
        }

        $message = ! empty($response->body->error)
            ? $response->body->error
            : ( ! empty($response->body->errorMessage)
                ? $response->body->errorMessage
                : __('No message sent!', 'pr-shipping-dhl') );

        throw new \Exception(
            sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
        );
    }

    public function get_service_points($params)
    {
        $response = $this->post('servicepointlocatorapi_21/v1/servicepoint/findnearestservicepoints', $params);

        if ($response->status === 200 && $response->body->status === 'OK') {
            return $response->body->servicePoints;
        }

        $message = ! empty($response->body->error)
            ? $response->body->error
            : ( ! empty($response->body->errorMessage)
                ? $response->body->errorMessage
                : __('No message sent!', 'pr-shipping-dhl') );

        throw new \Exception(
            sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
        );
    }
}
