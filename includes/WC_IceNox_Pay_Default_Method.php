<?php

class WC_IceNox_Pay_Default_Method extends WC_IceNox_Pay_Payment_Gateway {

	private $method_id;
	private $method_config;

	public function __construct( $method_id ) {
		parent::__construct( true, false );

		$this->method_id     = $method_id;
		$this->method_config = $this->defaultGateways[ $this->method_id ];
		if ( ! $this->method_config ) {
			return;
		}

		$this->id           = "icenox_pay_" . str_replace("-", "_", $this->method_id);
		$this->method_title = isset($this->method_config["name"]) ? __($this->method_config["name"], "woocommerce-icenox-pay-plugin") : "";
		$this->title        = __( "IceNox Pay Method", "woocommerce-icenox-pay-plugin" );
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
		$this->icenox_pay_notification     = "yes";

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
		wp_enqueue_media();
		wp_enqueue_script( "wp-media-picker-js");
		wp_enqueue_style( "wp-media-picker-css");

		$this->form_fields = [
			"enabled"                     => [
				"title"   => __( "Enable/Disable", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"label"   => __( "Enable Payment Method", "woocommerce-icenox-pay-plugin" ),
				"default" => "no"
			],
			"title"                       => [
				"title"       => __( "Method Title", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "The title of the payment method which will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),
				"default"     => $this->method_title,
			],
			"gateway_icon"                => [
				"title"       => __( "Method Logo", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "URL for the payment method that will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),
				"default"     => home_url() . "/wp-content/plugins/woocommerce-icenox-pay-plugin/includes/assets/images/paymentmethods/" . $this->method_id . ".svg",
				"class"       => "icenox-pay-method-icon-url",
			],
			"description"                 => [
				"title"       => __( "Method Description", "woocommerce-icenox-pay-plugin" ),
				"css"         => "width:50%;",
				"type"        => "textarea",
				"default"     => "",
				"description" => __( "Description for the payment method that will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),
			],
			"advanced"                    => [
				"title"       => __( "Method Settings", "woocommerce-icenox-pay-plugin" ) . "<hr>",
				"type"        => "title",
				"description" => "",
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
			"icenox_pay_express_redirect" => [
				"title"   => __( "Express Redirect", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"label"   => __( "Redirect immediately to the payment (only available for selected payment methods)", "woocommerce-icenox-pay-plugin" ),
				"default" => "no"
			],
			"debug_mode"                  => [
				"title"       => __( "Enable Debug Mode", "woocommerce-icenox-pay-plugin" ),
				"type"        => "checkbox",
				"label"       => __( "Enable ", "woocommerce-icenox-pay-plugin" ),
				"default"     => "no",
				"description" => __( "If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway works as expected." ),
			],
		];
	}
}