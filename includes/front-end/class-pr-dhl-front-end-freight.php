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

            //print_r(PR_DHL()->get_dhl_factory()->get_dhl_freight_service_points());

            $this->init_hooks();
        }

        public function init_hooks() {
            add_action( 'wp_enqueue_scripts', [$this, 'loadStylesScripts']);
            add_action( 'woocommerce_before_checkout_shipping_form', [$this, 'mapFinderButton']);
            add_action( 'woocommerce_after_checkout_form', [$this, 'addMapPopUp']);
            add_action( 'wp_ajax_dhl_service_point_search', array( $this, 'lookForServicePoints' ) );
            add_action( 'wp_ajax_nopriv_dhl_service_point_search', array( $this, 'lookForServicePoints' ) );
        }

        public function mapFinderButton()
        {
            if (! $this->isGoogleMapEnabled()) {
                return;
            }

            echo sprintf('<button id="dhl-fr-find" class="button">Search in Map</button>');
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
            wp_enqueue_script('pr-dhl-fr-main-script', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/pr-dhl-freight.js');
            wp_localize_script('pr-dhl-fr-main-script', 'pr_dhl_freight', []);

            wp_enqueue_style( 'pr-dhl-fr-main-style', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/pr-dhl-freight.css');

            // Google MAP API Key registration
            if ($this->isGoogleMapEnabled()) {
                wp_enqueue_script('pr-dhl-fr-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $this->shipping_dhl_settings['dhl_google_maps_api_key']);
            }
        }

        public function lookForServicePoints()
        {
            check_ajax_referer( 'dhl_freight_service_points_search', 'security' );

            $country	 = wc_clean( $_POST[ 'dhl_freight_country_code' ] );
            $postcode	 = wc_clean( $_POST[ 'dhl_freight_postal_code' ] );
            $city	 	 = wc_clean( $_POST[ 'dhl_freight_city' ] );
            $address	 = wc_clean( $_POST[ 'parcelfinder_address' ] );

            try {
                $dhl_obj = PR_DHL()->get_dhl_factory();

                $args['address']['countryCode'] = $country;
                $args['address']['postalCode']  = $postcode;
                $args['address']['cityName']    = $city;
                $args['address']['street']      = $address;

                $dhl_obj->get_dhl_freight_service_points($args);

            } catch (Exception $e) {
                wp_send_json( array( 'error' => $e->getMessage() ) );
            }

            wp_die();
        }
    }

endif;
