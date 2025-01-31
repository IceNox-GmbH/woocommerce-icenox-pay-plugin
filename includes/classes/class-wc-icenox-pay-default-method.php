<?php

class WC_IceNox_Pay_Default_Method extends WC_IceNox_Pay_Payment_Gateway {

	private $method_id;
	private $method_config;

	public function __construct( $method_id ) {
		parent::__construct();

		$this->method_id     = $method_id;
		$this->method_config = $this->defaultGateways[ $this->method_id ];
		if ( ! $this->method_config ) {
			return;
		}

		$this->id           = "icenox_pay_" . str_replace( "-", "_", $this->method_id );
		$this->method_title = isset( $this->method_config["name"] ) ? __( $this->method_config["name"], "woocommerce-icenox-pay-plugin" ) : "";
		$this->has_fields   = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled      = $this->get_option( "enabled" );
		$this->title        = $this->get_option( "title" );
		$this->gateway_icon = $this->get_option( "gateway_icon" );
		$this->debug_mode   = $this->get_option( "debug_mode" );


		$this->description = $this->get_option( "description" );

		$this->icenox_pay_api_key                   = get_option( "icenox_pay_api_key" );
		$this->icenox_pay_method_processor          = $this->get_option( "icenox_pay_processor", ! empty( $this->method_config["processor"] ) ? array_key_first( $this->method_config["processor"] ) : null );
		$this->icenox_pay_payment_method_identifier = ( empty( $this->icenox_pay_method_processor ) || $this->icenox_pay_method_processor === $this->method_id ) ? $this->method_id : $this->icenox_pay_method_processor . "-" . $this->method_id;

		$this->icenox_pay_express_redirect = $this->get_option( "icenox_pay_express_redirect" );

		if ( $this->debug_mode === "yes" ) {
			if ( ! current_user_can( "administrator" ) ) {
				$this->enabled = "no";
			}
		}

		add_action( "woocommerce_update_options_payment_gateways_" . $this->id, [
			$this,
			"process_admin_options"
		] );

	}

	public function init_form_fields() {
		$this->load_method_icon_picker();
		$this->form_fields = [
			"enabled"                              => [
				"title"   => __( "Status", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"default" => "no",
				"class"   => "icenox-pay-method-enabled-toggle toggle-input",
				"label"   => '<span class="toggle-slider"></span>' . __( "Enable Method", "woocommerce-icenox-pay-plugin" ),
			],
			"general"                              => [
				"title"       => __( "Checkout Settings", "woocommerce-icenox-pay-plugin" ) . "<hr>",
				"type"        => "title",
				"description" => __( "The following settings allow you to customize how the payment method is displayed to the customer.", "woocommerce-icenox-pay-plugin" )
			],
			"title"                                => [
				"title"   => __( "Name", "woocommerce-icenox-pay-plugin" ),
				"type"    => "text",
				"default" => $this->method_title,
			],
			"gateway_icon"                         => [
				"title"   => __( "Icon", "woocommerce-icenox-pay-plugin" ),
				"type"    => "url",
				"default"     => WP_PLUGIN_URL . "/woocommerce-icenox-pay-plugin/includes/assets/images/paymentmethods/" . $this->method_id . ".svg",
				"class"   => "icenox-pay-method-icon-url",
			],
			"description"                          => [
				"title"   => __( "Description", "woocommerce-icenox-pay-plugin" ),
				"css"     => "max-width:400px;",
				"type"    => "textarea",
				"default" => "",
			],
			"advanced"                             => [
				"title"       => __( "Method Configuration", "woocommerce-icenox-pay-plugin" ) . "<hr>",
				"type"        => "title",
				"description" => ""
			],
			"icenox_pay_processor"        => $this->method_config["processor"] ? [
				"title"       => __( "Payment Processor", "woocommerce-icenox-pay-plugin" ),
				"type"        => "select",
				"description" => __( "Please select your Payment Service Provider to process this payment method.", "woocommerce-icenox-pay-plugin" ),
				"options"     => $this->method_config["processor"],
				"default"     => array_key_first( $this->method_config["processor"] ),
			] : [
				"type"    => "hidden",
				"value"   => $this->method_id,
				"default" => $this->method_id,
			],
			"icenox_pay_express_redirect"          => [
				"title"       => __( "Express Redirect", "woocommerce-icenox-pay-plugin" ),
				"type"        => "checkbox",
				"class"       => "toggle-input",
				"label"       => '<span class="toggle-slider"></span>' . __( "Skip the IceNox Pay payment page and redirect immediately to the selected method.", "woocommerce-icenox-pay-plugin" ),
				"description" => __( "Only available for redirect-based methods not requiring additional information from the customer.", "woocommerce-icenox-pay-plugin" ),
				"default"     => "no"
			],
			"debug_mode"                           => [
				"title"       => __( "Debug Mode", "woocommerce-icenox-pay-plugin" ),
				"type"        => "checkbox",
				"class"       => "toggle-input",
				"label"       => '<span class="toggle-slider"></span>' . __( "Enable Debug Mode", "woocommerce-icenox-pay-plugin" ),
				"default"     => "no",
				"description" => __( "If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway work as you expected.", "woocommerce-icenox-pay-plugin" ),
			],
		];
	}
}