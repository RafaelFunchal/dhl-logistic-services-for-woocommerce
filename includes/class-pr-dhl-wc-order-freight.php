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

            $this->additional_services = $order->get_meta('dhl_freight_additional_services', true);

            if (! is_array($items)) {
                $items = [];
            }

            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                if (! isset($items[$field_name])) {
                    $items[$field_name] = false;
                }
            }

            return $items;
        }

        public function additional_meta_box_fields($order_id, $is_disabled, $dhl_label_items, $dhl_obj)
        {
            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                woocommerce_wp_checkbox( array(
                    'id'          		=> $field_name,
                    'label'       		=> __( $additional_service->name, 'pr-shipping-dhl' ),
                    'placeholder' 		=> '',
                    'description'		=> '',
                    'value'       		=> isset( $dhl_label_items[$field_name] ) ? $dhl_label_items[$field_name] : $this->shipping_dhl_settings[$field_name],
                    'custom_attributes'	=> array( $is_disabled => $is_disabled )
                ) );
            }
        }

        public function get_additional_meta_ids()
        {
            return collect($this->additional_services)->map(function ($item) {
                return sprintf('pr_dhl_%s', $item->type);
            })->toArray();
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
    }

endif;
