<?php

class icenox_pay_paypal extends WC_IceNox_Pay_Payment_Gateway {
    /**
     * @var string
     */
    private $paypal_processor;

    public function __construct() {
		parent::__construct( true );
		$this->id = "icenox_pay_paypal";
		$this->method_title = __( 'PayPal', 'woocommerce-icenox-pay-plugin' );
		$this->title = __( 'IceNox Pay Method', 'woocommerce-icenox-pay-plugin' );
		$this->has_fields   = false;


		$this->init_form_fields();
		$this->init_settings();

		$this->enabled      = $this->get_option( 'enabled' );
		$this->title        = $this->get_option( 'title' );
		$this->gateway_icon = $this->get_option( 'gateway_icon' );
		$this->debug_mode   = $this->get_option( 'debug_mode' );

		$this->description = $this->get_option( 'description' );

		$this->custom_api_atts = $this->get_option( 'custom_api_atts' );

		$this->icenox_pay_api_key = get_option( 'icenox_pay_api_key' );
        $this->paypal_processor = $this->get_option( 'icenox_pay_processor' ) ?: "paypal";
		$this->icenox_pay_payment_method_identifier = $this->paypal_processor === "paypal" ?
            "paypal" : $this->paypal_processor . "-paypal";

		$this->icenox_pay_express_redirect = 'yes';
		$this->icenox_pay_notification     = $this->get_option( 'icenox_pay_notification' );

		// Debug mode, only administrators can use the gateway.
		if ( $this->debug_mode == 'yes' ) {
			if ( ! current_user_can( 'administrator' ) ) {
				$this->enabled = 'no';
			}
		}

		add_action( 'woocommerce_receipt_custom_payment', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                              => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-custom-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Payment Method', 'woocommerce-custom-payment-gateway' ),
				'default' => 'no'
			),
			'title'                                => array(
				'title'       => __( 'Method Title', 'woocommerce-custom-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'The title of the payment method which will show to the user on the checkout page.', 'woocommerce-custom-payment-gateway' ),
				'default'     => $this->method_title,
			),
			'gateway_icon'                         => array(
				'title'       => __( 'Method Logo', 'woocommerce-custom-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'URL for the payment method that will show to the user on the checkout page.', 'woocommerce-custom-payment-gateway' ),
				'default'     => home_url() . '/wp-content/plugins/woocommerce-icenox-pay-plugin/includes/assets/images/paymentmethods/paypal.svg',
			),
			'description'                          => array(
				'title'       => __( 'Method Description', 'woocommerce-custom-payment-gateway' ),
				'css'         => 'width:50%;',
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'Description for the payment method that will show to the user on the checkout page.', 'woocommerce-custom-payment-gateway' ),

			),
			'advanced'                             => array(
				'title'       => __( 'Method Settings<hr>', 'woocommerce-custom-payment-gateway' ),
				'type'        => 'title',
				'description' => '',
			),
			'icenox_pay_notification'              => array(
				'title'   => __( 'Auto-Update Payment Status', 'woocommerce-custom-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable IceNox Pay to update the WooCommerce order status after successful payment.', 'woocommerce-custom-payment-gateway' ),
				'default' => 'yes'
			),
            'icenox_pay_processor'                 => array(
                'title'       => __( 'Payment Processor', 'woocommerce-custom-payment-gateway' ),
                'type'        => 'select',
                'description' => __( 'Please select your Payment Service Provider to process PayPal payments.', 'woocommerce-custom-payment-gateway' ),
                'options'     => [
                    'paypal' => 'PayPal',
                    's' => 'Stripe',
                    'paddle' => 'Paddle',
                ],
                'default'   => 'paypal'
            ),
			'debug_mode'                           => array(
				'title'       => __( 'Enable Debug Mode', 'woocommerce-custom-payment-gateway' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable ', 'woocommerce-custom-payment-gateway' ),
				'default'     => 'no',
				'description' => __( 'If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway works as expected.' ),
			),
		);
	}
}