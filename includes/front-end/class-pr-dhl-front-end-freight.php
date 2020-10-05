<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PR_DHL_Front_End_Freight' ) ) :

    class PR_DHL_Front_End_Freight {

        /**
         * Init and hook in the integration.
         */
        public function __construct()
        {
            $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

            $this->init_hooks();
        }

        public function init_hooks() {
            add_action( 'wp_enqueue_scripts', [$this, 'loadStylesScripts']);
            add_action( 'woocommerce_before_checkout_shipping_form', [$this, 'mapFinderButton']);
            add_action( 'woocommerce_after_checkout_form', [$this, 'addMapPopUp']);
            add_action( 'wp_ajax_dhl_service_point_search', [$this, 'lookForServicePoints']);
            add_action( 'wp_ajax_nopriv_dhl_service_point_search', [$this, 'lookForServicePoints']);
        }

        public function mapFinderButton()
        {
            if (! $this->isGoogleMapEnabled()) {
                return;
            }

            wc_get_template('checkout/dhl-freight-fields.php', [], '', PR_DHL_PLUGIN_DIR_PATH . '/templates/');
        }

        public function addMapPopUp()
        {
            if (! $this->isGoogleMapEnabled()) {
                return;
            }

            wc_get_template('checkout/dhl-freight-finder.php', [], '', PR_DHL_PLUGIN_DIR_PATH . '/templates/');
        }

        public function isGoogleMapEnabled()
        {
            return
                $this->shipping_dhl_settings['dhl_display_google_maps'] === 'yes'
                && $this->shipping_dhl_settings['dhl_google_maps_api_key'];
        }

        public function loadStylesScripts()
        {
            wp_enqueue_script('pr-dhl-fr-main-script', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl.js', ['jquery']);
            wp_localize_script('pr-dhl-fr-main-script', 'dhl', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce("dhl_freight"),
                'shopCountry' => wc_get_base_location()
            ]);

            wp_enqueue_style( 'pr-dhl-fr-main-style', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl.css');

            // Google MAP API Key registration
            if ($this->isGoogleMapEnabled()) {
                wp_enqueue_script('pr-dhl-fr-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $this->shipping_dhl_settings['dhl_google_maps_api_key']);
            }
        }

        public function lookForServicePoints()
        {
            check_ajax_referer( 'dhl_freight', 'security' );

            $postcode	 = wc_clean( $_POST[ 'dhl_freight_postal_code' ] );
            $city	 	 = wc_clean( $_POST[ 'dhl_freight_city' ] );
            $address	 = wc_clean( $_POST[ 'dhl_freight_address' ] );

            try {
                $dhl_obj = PR_DHL()->get_dhl_factory();

                $args = [
                    'postalCode' => $postcode,
                    'cityName' => $city,
                    'street' => $address
                ];

                $data = $dhl_obj->get_dhl_freight_service_points($args);

                wp_send_json($data);

            } catch (Exception $e) {
                wp_send_json( array( 'error' => $e->getMessage() ) );
            }

            wp_die();
        }
    }

endif;
