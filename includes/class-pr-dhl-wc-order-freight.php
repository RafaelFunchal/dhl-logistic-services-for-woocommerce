<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PR_DHL_WC_Order_Freight' ) ) :

    class PR_DHL_WC_Order_Freight extends PR_DHL_WC_Order {

        protected $carrier = 'DHL Freight';

        private $additional_services_whitelist = [
            'greenFreight', 'insurance', 'dangerousGoodsLimitedQuantity'
        ];

        private $additional_services = [];

        private $transportation;

        
        public function init_hooks(){

            parent::init_hooks();

            add_filter( 'pr_shipping_dhl_label_args', array( $this, 'checkRules' ), 10, 2 );
        }

        protected function get_default_dhl_product($order_id)
        {
            return [];
        }

        protected function get_tracking_url() {
            return null;
        }

        public function get_bulk_actions() {
            $shop_manager_actions = array(
                'pr_dhl_create_labels'      => __( 'DHL Create Labels', 'pr-shipping-dhl' )
            );

            return $shop_manager_actions;
        }

        public function get_dhl_label_items( $order_id )
        {
            // error_log('get_dhl_label_items');
            $items = parent::get_dhl_label_items($order_id);
            // error_log(print_r($items,true));
            $order = wc_get_order($order_id);

            if (! $this->additional_services) {
                $this->setAdditionalServices();
            }

            if (! is_array($items)) {
                $items = [];
            }

            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                if (! isset($items[$field_name])) {
                    $items[$field_name] = false;
                }

                if ($field_name === 'pr_dhl_insurance') {
                    $insurance_amount_field_name = sprintf('%s_amount', $field_name);

                    if (! isset($items[$insurance_amount_field_name])) {
                        $items[$insurance_amount_field_name] = $order->get_total();
                    }
                }
            }
            // error_log(print_r($items,true));
            return $items;
        }

        public function additional_meta_box_fields($order_id, $is_disabled, $dhl_label_items, $dhl_obj)
        {
            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_width',
                'label'       		=> __( 'Package Width (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_width'] ) ?
                        $dhl_label_items['pr_dhl_package_width'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_width']) ? $this->shipping_dhl_settings['pr_dhl_package_width'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_height',
                'label'       		=> __( 'Package Height (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_height'] ) ?
                        $dhl_label_items['pr_dhl_package_height'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_height']) ? $this->shipping_dhl_settings['pr_dhl_package_height'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_length',
                'label'       		=> __( 'Package Length (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_length'] ) ?
                        $dhl_label_items['pr_dhl_package_length'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_length']) ? $this->shipping_dhl_settings['pr_dhl_package_length'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                woocommerce_wp_checkbox([
                    'id'          		=> $field_name,
                    'label'       		=> __( $additional_service->name, 'pr-shipping-dhl' ),
                    'placeholder' 		=> '',
                    'description'		=> '',
                    'value'       		=> isset( $dhl_label_items[$field_name] ) ? $dhl_label_items[$field_name] : $this->shipping_dhl_settings[$field_name],
                    'custom_attributes'	=> array( $is_disabled => $is_disabled )
                ]);

                if ($field_name === 'pr_dhl_insurance') {

                    $insurance_amount_field_name = sprintf('%s_amount', $field_name);

                    woocommerce_wp_text_input([
                        'id'          		=> $insurance_amount_field_name,
                        'label'       		=> __( 'Insurance Amount:', 'pr-shipping-dhl' ),
                        'placeholder' 		=> '',
                        'description'		=> '',
                        'value'       		=>
                            isset( $dhl_label_items[$insurance_amount_field_name] ) ?
                                $dhl_label_items[$insurance_amount_field_name] :
                                $this->shipping_dhl_settings[$insurance_amount_field_name],
                        'custom_attributes'	=> array( $is_disabled => $is_disabled )
                    ]);
                }
            }

            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_pickup_date',
                'label'       		=> __( 'Pickup Date:', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_pickup_date'] ) ?
                        $dhl_label_items['pr_dhl_pickup_date'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_pickup_date']) ? $this->shipping_dhl_settings['pr_dhl_pickup_date'] : null),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            // Enqueue scripts in the way the parent did
            wp_enqueue_script( 'pr-dhl-fr-main-script-admin', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl-admin.js', array(), PR_DHL_VERSION );
            wp_enqueue_style( 'pr-dhl-fr-main-style-admin', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl-admin.css');
        }

        public function get_additional_meta_ids()
        {
            if (! $this->additional_services) {
                $this->setAdditionalServices();
            }

            $fields = collect($this->additional_services)
                ->map(function ($item) {
                return sprintf('pr_dhl_%s', $item->type);
                })
                ->add('pr_dhl_insurance_amount')
                ->add('pr_dhl_package_width')
                ->add('pr_dhl_package_length')
                ->add('pr_dhl_package_height')
                ->add('pr_dhl_pickup_date')
                ->toArray();

            // error_log(print_r($fields,true));
            return $fields;
        }

        protected function get_label_args_settings($order_id, $dhl_label_items)
        {
            // Get services etc.
            $meta_box_ids = $this->get_additional_meta_ids();
            
            foreach ($meta_box_ids as $value) {
                $api_key = str_replace('pr_dhl_', '', $value);
                if ( isset( $dhl_label_items[ $value ] ) ) {
                    $args['order_details'][ $api_key ] = $dhl_label_items[ $value ];
                }
            }

            // Cast access point info to array to be used in Item_Info accordingly
            $args['access_point'] = (array) get_post_meta($order_id, 'dhl_freight_point', true);

            $args['dhl_settings']['account_name'] = $this->shipping_dhl_settings['dhl_client_name'];
            $args['dhl_settings']['store_address'] = get_option( 'woocommerce_store_address' );
            $args['dhl_settings']['store_city'] = get_option( 'woocommerce_store_city' );
            $args['dhl_settings']['store_postcode'] = get_option( 'woocommerce_store_postcode' );
            $args['dhl_settings']['store_country'] = PR_DHL()->get_base_country();
            $args['dhl_settings']['account_num'] = $this->shipping_dhl_settings['dhl_client_account']; //'350009';
            $args['dhl_settings']['api_key'] = $this->shipping_dhl_settings['dhl_client_key'];
            $args['dhl_settings']['enable_pickup'] = $this->shipping_dhl_settings['dhl_enable_pickup'];
            
            return $args;
        }
/*
        public function save_meta_box_ajax()
        {
            check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
            $order_id = wc_clean( $_POST[ 'order_id' ] );

            // Save inputted data first
            $params            = $this->save_meta_box( $order_id );

            try {

                $label_url = $this
                    ->checkRules($order_id, $params)
                    ->validatePickupPoint()
                    ->transportation($order_id, $params)
                    ->requestPickup($order_id, $params)
                    ->printDocuments($order_id, $params);

                error_log($label_url);
                wp_send_json( array( 
                'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'pr-shipping-dhl'),
                'button_txt' => __( 'Download Label', 'pr-shipping-dhl' ),
                'label_url' => $label_url,
                'tracking_note'   => 'tracking note',
                'tracking_note_type' => 'tracking type',
                ) );

                // wp_send_json([
                    // 'label_url' => $label_url,

                // ]);

            } catch ( Exception $e ) {

                wp_send_json(['error' => $e->getMessage()]);
            }

            wp_die();
        }
*/
        public function checkRules($params, $order_id )
        {
            // error_log('checkRules');
            // error_log(print_r($params,true));
            // Check if access point set
            if (! get_post_meta($order_id, 'dhl_freight_point', true)) {
                throw new Exception(__('Invalid access point!', 'pr-shipping-dhl'));
            }

            // Check if products info set
            $serviceData = get_post_meta($order_id, 'dhl_freight_additional_services', true);

            if (! $serviceData) {
                throw new Exception(__('Invalid service information!', 'pr-shipping-dhl'));
            }

            $currency = get_woocommerce_currency();

            if (
                ! $currency ||
                $currency !== $this->getAllowedCurrency()
            ) {
                throw new \Exception('Invalid shop currency!');
            }

            // Check if weight set and is good
            if (! isset($params['order_details']['weight']) ||
                ! $params['order_details']['weight'] ||
                $params['order_details']['weight'] < $serviceData->piece->actualWeightMin ||
                $params['order_details']['weight'] > $serviceData->piece->actualWeightMax
            ) {
                throw new \Exception('Invalid package weight!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_width']) ||
                ! $params['order_details']['package_width'] ||
                $params['order_details']['package_width'] < $serviceData->piece->widthMin ||
                $params['order_details']['package_width'] > $serviceData->piece->widthMax
            ) {
                throw new \Exception('Invalid package width!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_height']) ||
                ! $params['order_details']['package_height'] ||
                $params['order_details']['package_height'] < $serviceData->piece->heightMin ||
                $params['order_details']['package_height'] > $serviceData->piece->heightMax
            ) {
                throw new \Exception('Invalid package height!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_length']) ||
                ! $params['order_details']['package_length'] ||
                $params['order_details']['package_length'] < $serviceData->piece->lengthMin ||
                $params['order_details']['package_length'] > $serviceData->piece->lengthMax
            ) {
                throw new \Exception('Invalid package length!');
            }

            // Check insurance
            if (
                isset($params['order_details']['insurance']) &&
                $params['order_details']['insurance'] === 'yes' &&
                (
                    ! isset($params['order_details']['insurance_amount']) ||
                    $params['order_details']['insurance_amount'] > $serviceData->highValueLimit
                )
            ) {
                throw new \Exception('Invalid insurance amount!');
            }

            // Check Pickupdate
            if (! isset($params['order_details']['pickup_date']) ||
                ! $params['order_details']['pickup_date']
            ) {
                throw new \Exception('Invalid pickup date!');
            }

            return $params;
        }
/*
        private function validatePickupPoint()
        {
            $dhl_obj            = PR_DHL()->get_dhl_factory();

            $store_city         = get_option( 'woocommerce_store_city' );
            $store_postcode     = get_option( 'woocommerce_store_postcode' );
            $store_country      = wc_get_base_location()['country'];

            if (
            ! $dhl_obj->dhl_valid_postal_code([
                'countryCode' => $store_country,
                'city' => $store_city,
                'postalCode' => $store_postcode
            ])
            ) {
                throw new Exception(__('Invalid postal code!', 'pr-shipping-dhl'));
            }

            return $this;
        }
*/
        private function requestPickup($order_id, $params)
        {
            $dhl_obj = PR_DHL()->get_dhl_factory();

            // Shop info
            $store_address     = get_option( 'woocommerce_store_address' );
            $store_city        = get_option( 'woocommerce_store_city' );
            $store_postcode    = get_option( 'woocommerce_store_postcode' );
            $store_country     = wc_get_base_location()['country'];

            // Access point info
            $access_point      = get_post_meta($order_id, 'dhl_freight_point', true);

            // Take customer info
            $args = $this->get_label_args( $order_id );

            $results = $dhl_obj->dhl_pickup_request([
                'parties' => [
                    [
                        'id' => $access_point->id,
                        'type' => 'AccessPoint',
                        'name' => $access_point->name,
                        'address' => [
                            'street' => $access_point->street,
                            'cityName' => $access_point->cityName,
                            'postalCode' => $access_point->postalCode,
                            'countryCode' => $access_point->countryCode
                        ]
                    ],
                    [
                        'type' => 'Consignor',
                        'address' => [
                            'street' => $store_address,
                            'cityName' => $store_city,
                            'postalCode' => $store_postcode,
                            'countryCode' => $store_country
                        ],
                    ],
                    [
                        'type' => 'Consignee',
                        'address' => [
                            'street' => $args['shipping_address']['address_1'],
                            'cityName' => $args['shipping_address']['city'],
                            'postalCode' => $args['shipping_address']['postcode'],
                            'countryCode' => $args['shipping_address']['country']
                        ],
                        'phone' => $args['shipping_address']['phone'],
                        'email' => $args['shipping_address']['email'],
                    ]
                ],
                'pieces' => [
                    [
                        "id" => $this->transportation->pieces[0]->id,
                        'numberOfPieces' => 1,
                        'packageType' => 'CLL',
                        'weight' => $params['pr_dhl_weight'],
                        'width' => $params['pr_dhl_package_width'],
                        'height' => $params['pr_dhl_package_height'],
                        'length' => $params['pr_dhl_package_length']
                    ]
                ],
                'id' => $this->transportation->id,
                'additionalServices' => $this->mapDhlAdditionalServices($args, $order_id),
                'productCode' => '103',
                'payerCode' => [
                        'code' => '1'
                    ],
                'totalWeight' => $params['pr_dhl_weight'],
                'pickupDate' => $params['pr_dhl_pickup_date']
            ]);

            // Save booking number
            update_post_meta($order_id, 'dhl_freight_pickup_booking_number', $results->bookingNumber);

            return $this;
        }

        private function transportation($order_id, $params)
        {
            $dhl_obj = PR_DHL()->get_dhl_factory();

            // Shop info
            $store_address     = get_option( 'woocommerce_store_address' );
            $store_city        = get_option( 'woocommerce_store_city' );
            $store_postcode    = get_option( 'woocommerce_store_postcode' );
            $store_country     = wc_get_base_location()['country'];

            // Access point info
            $access_point      = get_post_meta($order_id, 'dhl_freight_point', true);

            // Take customer info
            $args = $this->get_label_args( $order_id );

            $results = $dhl_obj->dhl_transportation_request([
                'productCode' => '103',
                'payerCode' => [
                        'code' => '1'
                    ],
                'parties' => [
                    [
                        'id' => $access_point->id,
                        'type' => 'AccessPoint',
                        'name' => $access_point->name,
                        'address' => [
                            'street' => $access_point->street,
                            'cityName' => $access_point->cityName,
                            'postalCode' => $access_point->postalCode,
                            'countryCode' => $access_point->countryCode
                        ]
                    ],
                    [
                        'type' => 'Consignor',
                        'address' => [
                            'street' => $store_address,
                            'cityName' => $store_city,
                            'postalCode' => $store_postcode,
                            'countryCode' => $store_country
                        ],
                    ],
                    [
                        'type' => 'Consignee',
                        'address' => [
                            'street' => $args['shipping_address']['address_1'],
                            'cityName' => $args['shipping_address']['city'],
                            'postalCode' => $args['shipping_address']['postcode'],
                            'countryCode' => $args['shipping_address']['country']
                        ],
                        'phone' => $args['shipping_address']['phone'],
                        'email' => $args['shipping_address']['email'],
                    ]
                ],
                'pieces' => [
                    [
                        "id" => [],
                        'numberOfPieces' => 1,
                        'packageType' => 'CLL',
                        'weight' => $params['pr_dhl_weight'],
                        'width' => $params['pr_dhl_package_width'],
                        'height' => $params['pr_dhl_package_height'],
                        'length' => $params['pr_dhl_package_length']
                    ]
                ],
                'additionalServices' => $this->mapDhlAdditionalServices($args, $order_id),
                'totalWeight' => $params['pr_dhl_weight'],
                'pickupDate' => $params['pr_dhl_pickup_date']
            ]);

            $this->transportation = $results;

            update_post_meta($order_id, 'dhl_freight_trnasportation_insturctions', $results);

            return $this;
        }

        private function printDocuments($order_id, $params)
        {
            $dhl_obj = PR_DHL()->get_dhl_factory();

            // Shop info
            $store_address     = get_option( 'woocommerce_store_address' );
            $store_city        = get_option( 'woocommerce_store_city' );
            $store_postcode    = get_option( 'woocommerce_store_postcode' );
            $store_country     = wc_get_base_location()['country'];

            // Access point info
            $access_point      = get_post_meta($order_id, 'dhl_freight_point', true);

            // Take customer info
            $args = $this->get_label_args( $order_id );

            $results = $dhl_obj->dhl_print_document_request([
                'shipment' => [
                    'id' => $this->transportation->id,
                    'productCode' => '103',
                    'payerCode' => [
                        'code' => '1'
                    ],
                    'parties' => [
                        [
                            'id' => $access_point->id,
                            'type' => 'AccessPoint',
                            'name' => $access_point->name,
                            'address' => [
                                'street' => $access_point->street,
                                'cityName' => $access_point->cityName,
                                'postalCode' => $access_point->postalCode,
                                'countryCode' => $access_point->countryCode
                            ]
                        ],
                        [
                            'type' => 'Consignor',
                            'address' => [
                                'street' => $store_address,
                                'cityName' => $store_city,
                                'postalCode' => $store_postcode,
                                'countryCode' => $store_country
                            ],
                        ],
                        [
                            'type' => 'Consignee',
                            'address' => [
                                'street' => $args['shipping_address']['address_1'],
                                'cityName' => $args['shipping_address']['city'],
                                'postalCode' => $args['shipping_address']['postcode'],
                                'countryCode' => $args['shipping_address']['country']
                            ],
                            'phone' => $args['shipping_address']['phone'],
                            'email' => $args['shipping_address']['email'],
                        ]
                    ],
                    'pieces' => [
                        [
                            "id" => $this->transportation->pieces[0]->id,
                            'numberOfPieces' => 1,
                            'packageType' => 'CLL',
                            'weight' => $params['pr_dhl_weight'],
                            'width' => $params['pr_dhl_package_width'],
                            'height' => $params['pr_dhl_package_height'],
                            'length' => $params['pr_dhl_package_length']
                        ]
                    ],
                    'additionalServices' => $this->mapDhlAdditionalServices($args, $order_id),
                    'totalWeight' => $params['pr_dhl_weight'],
                    'pickupDate' => $params['pr_dhl_pickup_date'],
                    'routingCode' => $this->transportation->routingCode
                ],
                'options' => [
                    "label" => true,
                ]
            ]);

            // error_log(print_r($results,true));

            update_post_meta($order_id, 'dhl_freight_print_document_data', $results);

            return $results;
        }

        private function mapDhlAdditionalServices($args, $order_id)
        {
            $add_services = $this->get_dhl_label_items($order_id);
            $results = [];

            $map = [
                //'notification' => 'pr_dhl_notificationByLetter',
                'insurance' => 'pr_dhl_insurance',
                'cashOnDelivery' => 'pr_dhl_cashOnDelivery',
                'dangerousGoodsLimitedQuantity' => 'pr_dhl_dangerousGoodsLimitedQuantity',
            ];

            foreach ($map as $apiKey => $wpKey) {

                switch ($apiKey) {
                    case 'cashOnDelivery':
                    case 'insurance':

                        if (isset($add_services[$wpKey]) && $add_services[$wpKey] === 'yes') {
                            $results[$apiKey] = [
                                'value' => isset($add_services['pr_dhl_insurance_amount']) ?
                                    $add_services['pr_dhl_insurance_amount'] :
                                    $args['order_details']['total_value'],
                                'currency' => $args['order_details']['currency'],
                            ];
                        }

                        break;

                    default:

                        $results[$apiKey] = isset($add_services[$wpKey]) && $add_services[$wpKey] === 'yes';

                        break;
                }

            }

            return $results;
        }

        private function getAdditionalServicesWhiteList()
        {
            return apply_filters('pr_dhl_freight_additional_services_allowed', $this->additional_services_whitelist);
        }

        private function setAdditionalServices()
        {
            global $post;

            $post_id = $post ? $post : (isset($_POST['order_id']) && $_POST['order_id'] ? $_POST['order_id'] : null );

            if (! $post_id) {
                return;
            }

            $order = wc_get_order($post_id);

            $this->additional_services = collect($order->get_meta('dhl_freight_additional_services', true)->additionalServices)->filter(function ($item) {
                return in_array($item->type, $this->getAdditionalServicesWhiteList());
            });
        }

        private function getAllowedCurrency()
        {
            return 'SEK';
        }

        public function process_download_awb_label() {
            global $wp_query;

            $dhl_order_id = isset($wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ] )
                ? $wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ]
                : null;

            if (! $dhl_order_id) {
                return;
            }

            $label_info = get_post_meta($dhl_order_id, 'dhl_freight_print_document_data', true);

            header("Content-type:application/pdf");
            header(sprintf("Content-Disposition:attachment; filename=%s", $label_info[0]->name));

            echo base64_decode($label_info[0]->content);
        }
    }

endif;
