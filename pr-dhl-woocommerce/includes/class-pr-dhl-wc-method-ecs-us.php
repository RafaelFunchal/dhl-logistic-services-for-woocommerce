<?php

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_WC_Method_eCS_US', false ) ) {
	return;
}

class PR_DHL_WC_Method_eCS_US extends WC_Shipping_Method {
    /**
     * Init and hook in the integration.
     */
    public function __construct( $instance_id = 0 ) {
        $this->id = 'pr_dhl_ecs_us';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'DHL eCS US', 'pr-shipping-dhl' );
        $this->method_description = sprintf(
            __( 'To start creating DHL eCommerce shipping labels and return back a DHL Tracking number to your customers, please fill in your user credentials as shown in your contracts provided by DHL. Not yet a customer? Please get a quote %shere%s or find out more on how to set up this plugin and get some more support %shere%s.', 'pr-shipping-dhl' ), '<a href="https://www.logistics.dhl/global-en/home/our-divisions/ecommerce/integration/contact-ecommerce-integration-get-a-quote.html?cid=referrer_3pv-signup_woocommerce_ecommerce-integration&SFL=v_signup-woocommerce" target="_blank">', '</a>', '<a href="https://www.logistics.dhl/global-en/home/our-divisions/ecommerce/integration/integration-channels/third-party-solutions/woocommerce.html?cid=referrer_docu_woocommerce_ecommerce-integration&SFL=v_woocommerce" target="_blank">', '</a>'
        );

        $this->init();
    }

    /**
     * Initializes the instance.
     *
     * @since [*next-version*]
     */
    protected function init() {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Enqueues scripts for the admin-side.
     *
     * @since [*next-version*]
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only applies to WC Settings panel
        if ( $hook !== 'woocommerce_page_wc-settings' ) {
            return;
        }

        $test_con_data = array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'loader_image'   => admin_url( 'images/loading.gif' ),
            'test_con_nonce' => wp_create_nonce( 'pr-dhl-test-con' ),
        );

        wp_enqueue_script(
            'wc-shipment-dhl-testcon-js',
            PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-test-connection.js',
            array( 'jquery' ),
            PR_DHL_VERSION
        );
        wp_localize_script( 'wc-shipment-dhl-testcon-js', 'dhl_test_con_obj', $test_con_data );
    }

    /**
     * Get message
     *
     * @return string Error
     */
    private function get_message( $message, $type = 'notice notice-error is-dismissible' ) {

        ob_start();
        ?>
        <div class="<?php echo $type ?>">
            <p><?php echo $message ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Initialize integration settings form fields.
     *
     * @throws Exception
     */
    public function init_form_fields() {

        $log_path = PR_DHL()->get_log_url();

        try {

            $dhl_obj = PR_DHL()->get_dhl_factory();
			$select_dhl_product_int = $dhl_obj->get_dhl_products_international();
			$select_dhl_product_dom = $dhl_obj->get_dhl_products_domestic();
        } catch ( Exception $e ) {
            PR_DHL()->log_msg( __( 'DHL eCS US Products not displaying - ', 'pr-shipping-dhl' ) . $e->getMessage() );
        }

        $weight_units = get_option( 'woocommerce_weight_unit' );

        $this->form_fields = array(
			'dhl_pickup_dist'     => array(
				'title'           => __( 'Shipping and Pickup', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
				'class'			  => '',
			),
	
				'dhl_pickup_id' => array(
					'title'             => __( 'DHL PICKUP Account ID', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The pickup account number (10 digits - numerical) will be provided by your local DHL sales organization and tells us where to pick up your shipments.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> '0000500000'
				),
				'dhl_distribution_center' => array(
					'title'             => __( 'DHL Distribution Center', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The distribution center assigned to your products/services.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> '0000500000'
				),
				'dhl_company_name' => array(
					'title'             => __( 'Company Name', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The name of the company.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Company Name'
				),
				'dhl_contact_name' => array(
					'title'             => __( 'Contact Name', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The name of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Contact Name'
				),
				'dhl_address_1' => array(
					'title'             => __( 'Address 1', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The address of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Address 1'
				),
				'dhl_address_2' => array(
					'title'             => __( 'Address 2', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The address of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Address 2'
				),
				'dhl_city' => array(
					'title'             => __( 'City', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The city of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'City'
				),
				'dhl_state' => array(
					'title'             => __( 'State', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The state of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'State'
				),
				'dhl_district' => array(
					'title'             => __( 'District', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The district of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'District'
				),
				'dhl_country' => array(
					'title'             => __( 'Country', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The country of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Country'
				),
				'dhl_postcode' => array(
					'title'             => __( 'Post Code', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The post code of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Post Code'
				),
				'dhl_phone' => array(
					'title'             => __( 'Phone Number', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The phone number of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Phone Number'
				),
				'dhl_email' => array(
					'title'             => __( 'Email', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'The email of the merchant, used as contact information when creating DHL eCommerce orders.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'		=> 'Email'
				),


            'dhl_api'                    => array(
                'title'       => __( 'Account and API Settings', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __(
                    'Please configure your account and API settings with DHL eCommerce International.',
                    'pr-shipping-dhl'
                ),
                'class'       => '',
            ),
				'dhl_api_key'                => array(
					'title'       => __( 'Client Id', 'pr-shipping-dhl' ),
					'type'        => 'text',
					'description' => __(
						'The client ID (a 36 digits alphanumerical string made from 5 blocks) is required for authentication and is provided to you within your contract.',
						'pr-shipping-dhl'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_api_secret'             => array(
					'title'       => __( 'Client Secret', 'pr-shipping-dhl' ),
					'type'        => 'text',
					'description' => __(
						'The client secret (also a 36 digits alphanumerical string made from 5 blocks) is required for authentication (together with the client ID) and creates the tokens needed to ensure secure access. It is part of your contract provided by your Deutsche Post sales partner.',
						'pr-shipping-dhl'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_sandbox'                => array(
					'title'       => __( 'Sandbox Mode', 'pr-shipping-dhl' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable Sandbox Mode', 'pr-shipping-dhl' ),
					'default'     => 'no',
					'description' => __(
						'Please, tick here if you want to test the plug-in installation against the Deutsche Post Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!',
						'pr-shipping-dhl'
					),
					'desc_tip'    => true,
				),
				'dhl_test_connection_button' => array(
					'title'             => PR_DHL_BUTTON_TEST_CONNECTION,
					'type'              => 'button',
					'custom_attributes' => array(
						'onclick' => "dhlTestConnection('#woocommerce_pr_dhl_ecs_us_dhl_test_connection_button');",
					),
					'description'       => __(
						'Press the button for testing the connection against our DHL eCommerce (depending on the selected environment this test is being done against the Sandbox or the Production Environment).',
						'pr-shipping-dhl'
					),
					'desc_tip'          => true,
				),
				'dhl_debug'                  => array(
					'title'       => __( 'Debug Log', 'pr-shipping-dhl' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable logging', 'pr-shipping-dhl' ),
					'default'     => 'yes',
					'description' => sprintf(
						__(
							'A log file containing the communication to the DHL eCommerce server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.',
							'pr-shipping-dhl'
						),
						'<a href="' . $log_path . '" target = "_blank">',
						'</a>'
					),
				),
        );

        $this->form_fields += array(
            'dhl_pickup_dist'  => array(
                'title'       => __( 'Shipping', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
                'class'       => '',
            ),
            'dhl_default_product_int' => array(
                'title'       => __( 'International Default Service', 'pr-shipping-dhl' ),
                'type'        => 'select',
                'description' => __(
                    'Please select your default DHL eCommerce shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'options'     => $select_dhl_product_int,
                'class'       => 'wc-enhanced-select',
			),
			'dhl_default_product_dom' => array(
				'title'             => __( 'Domestic Default Service', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL eCommerce shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_dom,
				'class'				=> 'wc-enhanced-select'
			),
            'dhl_tracking_note'     => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Make Private', 'pr-shipping-dhl' ),
                'default'     => 'no',
                'description' => __(
                    'Please, tick here to not send an email to the customer when the tracking number is added to the order.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
            ),
            'dhl_tracking_note_txt' => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'textarea',
                'description' => __(
                    'Set the custom text when adding the tracking number to the order notes. {tracking-link} is where the tracking number will be set.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => false,
                'default'     => __( 'DHL eCommerce Tracking Number: {tracking-link}', 'pr-shipping-dhl' ),
            ),
        );

	    $this->form_fields += array(
		    'dhl_label_section'   => array(
			    'title'           => __( 'Label options', 'pr-shipping-dhl' ),
			    'type'            => 'title',
			    'description'     => __( 'Options for configuring your label preferences', 'pr-shipping-dhl' ),
		    ),
	        'dhl_label_ref' => array(
		        'title'             => __( 'Customer Reference', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'custom_attributes'	=> array( 'maxlength' => '35' ),
		        'description'       => sprintf( __( 'Use "%s" to send the order id as a reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}', '{user_id}' ),
		        'desc_tip'          => true,
		        'default'           => '{order_id}'
	        ),
	        'dhl_label_ref_2' => array(
		        'title'             => __( 'Customer Reference 2', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'custom_attributes'	=> array( 'maxlength' => '35' ),
		        'description'       => sprintf( __( 'Use "%s" to send the order id as a reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}', '{user_id}' ),
		        'desc_tip'          => true,
		        'default'           => '{email}'
			),
			'dhl_label_format' => array(
				'title'             => __( 'Label Format', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select one of the formats to generate the shipping label in.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => array( 'PDF' => 'PDF', 'PNG' => 'PNG', 'ZPL' => 'ZPL' ),
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_label_size' => array(
				'title'             => __( 'Label Size', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select the shipping label size.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => array( '4x1' => '4x1', '1x1' => '1x1' ),
				'class'				=> 'wc-enhanced-select'
			),
        );

        $this->form_fields += array(

        );
    }

    /**
     * Generate Button HTML.
     *
     * @access public
     *
     * @param mixed $key
     * @param mixed $data
     *
     * @since  1.0.0
     * @return string
     */
    public function generate_button_html( $key, $data ) {
        $field = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => array(),
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
                    </legend>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button"
                            name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>"
                            style="<?php echo esc_attr(
                                $data['css']
                            ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post(
                            $data['title']
                        ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Processes and saves options.
     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
     */
    public function process_admin_options() {

        try {

            $dhl_obj = PR_DHL()->get_dhl_factory();
            $dhl_obj->dhl_reset_connection();
        } catch ( Exception $e ) {

            echo $this->get_message( __( 'Could not reset connection: ', 'pr-shipping-dhl' ) . $e->getMessage() );
            // throw $e;
        }

        return parent::process_admin_options();
    }
}
