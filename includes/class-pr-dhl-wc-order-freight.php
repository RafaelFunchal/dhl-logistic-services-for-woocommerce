<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PR_DHL_WC_Order_Freight' ) ) :

    class PR_DHL_WC_Order_Freight extends PR_DHL_WC_Order {

        protected $carrier = 'DHL Freight';

        private $additional_services = [];

        public function init_hooks(){

            parent::init_hooks();

            add_action( 'pr_shipping_dhl_label_created', array( $this, 'change_order_status' ), 10, 1 );
            add_action( 'woocommerce_email_order_details', array( $this, 'add_tracking_info'), 10, 4 );
            add_action( 'woocommerce_order_status_changed', array( $this, 'create_label_on_status_changed' ), 10, 4 );
        }

        protected function get_default_dhl_product($order_id)
        {
            return [];
        }

        protected function get_tracking_url() {
            return null;
        }

        public function get_bulk_actions() {
            return [];
        }

        public function get_dhl_label_items( $order_id )
        {
            $items = parent::get_dhl_label_items($order_id);

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

            return $items;
        }

        public function additional_meta_box_fields($order_id, $is_disabled, $dhl_label_items, $dhl_obj)
        {
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
                ->toArray();

            return $fields;
        }

        protected function get_label_args_settings($order_id, $dhl_label_items)
        {
            return [];
//            $order = wc_get_order($order_id);
//
//            $this->additional_services = $order->get_meta('dhl_freight_additional_services', true);
//
//            $meta_box_ids = $this->get_additional_meta_ids();
//
//            foreach ($meta_box_ids as $value) {
//                $api_key = str_replace('pr_dhl_', '', $value);
//                if ( isset( $dhl_label_items[ $value ] ) ) {
//                    $args['order_details'][ $api_key ] = $dhl_label_items[ $value ];
//                }
//            }
//
//            return $args;
        }

        public function save_meta_box_ajax()
        {
            check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
            $order_id = wc_clean( $_POST[ 'order_id' ] );

            // Save inputted data first
            $this->save_meta_box( $order_id );

            try {
                // Gather args for DHL API call
                $args = $this->get_label_args( $order_id );

                $dhl_obj = PR_DHL()->get_dhl_factory();

                if (
                    ! $dhl_obj->dhl_valid_postal_code([
                        'city' => $args['shipping_address']['city'],
                        'postalCode' => $args['shipping_address']['postcode']
                    ])
                ) {
                    throw new Exception(__('Invalid postal code!', 'pr-shipping-dhl'));
                }


                $results = $dhl_obj->dhl_pickup_request([
                    'parties' => [
                        [
                            'id' => $order_id,
                            'type' => 'AccessPoint',
                            'name' => $args['shipping_address']['name'],
                            'contactName' => $args['shipping_address']['name'],
                            'references' => [$order_id],
                            'address' => [
                                'street' => $args['shipping_address']['address_1'],
                                'streetNumber' => '',
                                'cityName' => $args['shipping_address']['city'],
                                'postalCode' => $args['shipping_address']['postcode'],
                                'countryCode' => $args['shipping_address']['country']
                            ],
                            'phone' => $args['shipping_address']['phone'],
                            'email' => $args['shipping_address']['email'],
                            'fax' => ''
                        ]
                    ],
                    'pieces' => [
                        [
                            'numberOfPieces' => 1,
                            'weight' => $args['order_details']['weight']
                        ]
                    ],
                    //'id' => $order_id,
                    'additionalServices' => $this->mapDhlAdditionalServices($args, $order_id),
                    'totalWeight' => $args['order_details']['weight']
                ]);

                print_r($results);

                die();

            } catch ( Exception $e ) {

                wp_send_json( array( 'error' => $e->getMessage() ) );
            }

            wp_die();
        }

        private function mapDhlAdditionalServices($args, $order_id)
        {
            $add_services = $this->get_dhl_label_items($order_id);
            $results = [];

            $map = [
                'notification' => 'pr_dhl_notificationByLetter',
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

        private function setAdditionalServices()
        {
            global $post;

            $post_id = $post ? $post : (isset($_POST['order_id']) && $_POST['order_id'] ? $_POST['order_id'] : null );

            if (! $post_id) {
                return;
            }

            $order = wc_get_order($post_id);

            $this->additional_services = $order->get_meta('dhl_freight_additional_services', true);
        }
    }

endif;
