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

    private function throwError($response)
    {
        $message = ! empty($response->body->error)
            ? $response->body->error
            : ( ! empty($response->body->errorMessage)
                ? $response->body->errorMessage
                : ( ! empty($response->body->UserMessage)
                ? $response->body->UserMessage
                : __('No message sent!', 'pr-shipping-dhl') ) );

        throw new \Exception(
            sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
        );
    }

    public function get_products($product_code, $params)
    {
        $response = $this->get('productapi/v1/products/' . $product_code, $params);

        if ($response->status === 200 && isset($response->body->additionalServices)) {
            return $response->body;
        }

        $this->throwError($response);
    }

    public function get_service_points($params)
    {
        $response = $this->post('servicepointlocatorapi_21/v1/servicepoint/findnearestservicepoints', $params);

        if ($response->status === 200 && $response->body->status === 'OK') {
            return $response->body->servicePoints;
        }

        $this->throwError($response);
    }

    public function validate_postal_code($params)
    {
        $response = $this->post('postalcodeapi/v1/postalcodes/validate', $params);

        if ($response->status === 200) {
            return $response->body;
        }

        $this->throwError($response);
    }

    public function pickup_request($params)
    {
        $response = $this->post('pickuprequestapi/v1/pickuprequest/pickuprequest', $params);

        if ($response->status === 200) {
            return $response->body;
        }

        $this->throwError($response);
    }

    public function transportation_request($params)
    {
        $response = $this->post('transportinstructionapi/v1/transportinstruction/sendtransportinstruction', $params);

        if ($response->status === 200 && $response->body->status !== 'Error') {
            return $response->body->transportInstruction;
        }

        throw new \Exception($response->body->validationErrors[0]->message);
    }

    public function print_documents_request($params)
    {
        $response = $this->post('printapi/v1/print/printdocuments', $params);
print_r($response);
        if ($response->status === 200) {
            return $response->body->reports;
        }

        $this->throwError($response);
    }
}
