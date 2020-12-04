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

    public function transportation_request( $item_info )
    {
        $params = array_merge(
            $this->get_parties_request( $item_info ),
            $this->get_pieces_request( $item_info ),
            $this->get_services_request( $item_info )
        );

        error_log(print_r($params, true));
        $response = $this->post('transportinstructionapi/v1/transportinstruction/sendtransportinstruction', $params);
        error_log(print_r($response,true));
        if ($response->status === 200 && $response->body->status !== 'Error') {
            return $response->body->transportInstruction;
        }

        throw new \Exception($response->body->validationErrors[0]->message);
    }

    public function validate_postal_code( $item_info )
    {
        $params = array(
            'countryCode' => $item_info->shipper['country'],
            'city' => $item_info->shipper['city'],
            'postalCode' => $item_info->shipper['postcode']
        );

        $response = $this->post('postalcodeapi/v1/postalcodes/validate', $params);
        if ($response->status === 200) {
            return $response->body;
        }

        $this->throwError($response);
    }

    public function pickup_request( $transport_response )
    {
        $response = $this->post('pickuprequestapi/v1/pickuprequest/pickuprequest', $transport_response);
     
        if ($response->status === 200) {
            return $response->body;
        }

        $this->throwError($response);
    }
    
    public function print_documents_request( $transport_response)
    {
        $params = array(
                    'shipment' => $transport_response,
                    'options' => [
                            'label' => true,
                            ]
                    );

        // error_log(print_r($params,true));
        $response = $this->post('printapi/v1/print/printdocuments', $params);
        // error_log(print_r($response,true));
        if ($response->status === 200) {
            return $response->body->reports;
        }

        $this->throwError($response);
    }

    protected function get_parties_request( $item_info ) {

        $parties = [
            'parties' => [
                [
                    'id' => $item_info->access_point['id'],
                    'type' => 'AccessPoint',
                    'name' => $item_info->access_point['name'],
                    'address' => [
                        'street' => $item_info->access_point['street'],
                        'cityName' => $item_info->access_point['city'],
                        'postalCode' => $item_info->access_point['postcode'],
                        'countryCode' => $item_info->access_point['country']
                    ]
                ],
                [
                    'id' => $item_info->shipper['id'],
                    'type' => 'Consignor',
                    'name' => $item_info->shipper['name'],
                    'address' => [
                        'street' => $item_info->shipper['street'],
                        'cityName' => $item_info->shipper['city'],
                        'postalCode' => $item_info->shipper['postcode'],
                        'countryCode' => $item_info->shipper['country']
                    ],
                ],
                [
                    'type' => 'Consignee',
                    'address' => [
                        'street' => $item_info->recipient['address_1'],
                        'cityName' => $item_info->recipient['city'],
                        'postalCode' => $item_info->recipient['postcode'],
                        'countryCode' => $item_info->recipient['country']
                    ],
                    'phone' => $item_info->recipient['phone'],
                    'email' => $item_info->recipient['email'],
                ]
            ]
        ];

        return $parties;
    }

    protected function get_pieces_request( $item_info ) {

        $pieces = [
            'productCode' => '103',
            'payerCode' => [
                    'code' => '1'
                ],
            'totalWeight' => $item_info->shipment['weight'],
            'pickupDate' => $item_info->shipment['pickup_date'],
            'pieces' => [
                [
                    // "id" => $this->transportation->pieces[0]->id,
                    'id' => [],
                    'numberOfPieces' => 1,
                    'packageType' => 'CLL',
                    'weight' => $item_info->shipment['weight'],
                    'width' => $item_info->shipment['width'],
                    'height' => $item_info->shipment['height'],
                    'length' => $item_info->shipment['length']
                ]
            ]
            
        ];

        return $pieces;
    }

    protected function get_services_request( $item_info )
    {
        $results = [];

        $map = [
            //'notification' => 'pr_dhl_notificationByLetter',
            'insurance' => 'insurance_amount',
            'cashOnDelivery' => 'cashOnDelivery',
            'dangerousGoodsLimitedQuantity' => 'dangerousGoodsLimitedQuantity',
        ];

        foreach ($map as $apiKey => $wpKey) {

            switch ($apiKey) {
                case 'cashOnDelivery':
                case 'insurance':

                    if (isset($item_info->shipment[$wpKey]) && $item_info->shipment[$wpKey] === 'yes') {
                        $results[$apiKey] = [
                            'value' => $item_info->shipment['insurance_amount'],
                            'currency' => $item_info->shipment['currency'],
                        ];
                    }

                    break;

                case 'dangerousGoodsLimitedQuantity':

                    $results[$apiKey] = isset($item_info->shipment[$wpKey]) && $item_info->shipment[$wpKey] === 'yes';

                    break;
            }

        }

        return array( 'additionalServices' => $results );
    }
}
