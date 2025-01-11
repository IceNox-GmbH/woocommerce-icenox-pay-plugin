<?php

class WC_IceNox_Pay_Payment_Gateway extends WC_Payment_Gateway {

	public $defaultGateways = [];

	protected $gateway_icon;
	protected $api_data = [];
	protected $debug_mode;
	protected $api_url_to_ping;

	protected $icenox_pay_api_key;
	protected $icenox_pay_method_processor;
	protected $icenox_pay_payment_method_identifier;

	protected $icenox_pay_express_redirect;
	protected $icenox_pay_notification;


	public function __construct( $child = false, $initSettings = true ) {
		$this->id              = "icenox_pay";
		$this->api_url_to_ping = "https://imp.icenox.com/api/payment/create/";
		$this->method_title    = __( "IceNox Pay Payment", "woocommerce-icenox-pay-plugin" );
		$this->title           = __( "IceNox Pay", "woocommerce-icenox-pay-plugin" );
		$this->has_fields      = false;

		if ( $initSettings === true ) {
			$this->init_form_fields();
			$this->init_settings();
		}

		$this->enabled      = $this->get_option( "enabled" );
		$this->title        = $this->get_option( "title" );
		$this->gateway_icon = $this->get_option( "gateway_icon" );
		$this->debug_mode   = $this->get_option( "debug_mode" );


		$this->description = $this->get_option( "description" );

		$this->icenox_pay_api_key                   = $this->get_option( "icenox_pay_api_key" );
		$this->icenox_pay_payment_method_identifier = $this->get_option( "icenox_pay_payment_method_identifier" );

		$this->icenox_pay_express_redirect = $this->get_option( "icenox_pay_express_redirect" );
		$this->icenox_pay_notification     = $this->get_option( "icenox_pay_notification" );

		// Debug mode, only administrators can use the gateway.
		if ( $this->debug_mode === "yes" ) {
			if ( ! current_user_can( "administrator" ) ) {
				$this->enabled = "no";
			}
		}

		if ( $child === false ) {
			add_action( "woocommerce_update_options_payment_gateways_" . $this->id, [
				$this,
				"process_admin_options"
			] );
		}

		add_action( "woocommerce_api_" . strtolower( get_class( $this ) ), [
			$this,
			"process_returned_response"
		] );

		$this->defaultGateways = IceNox_Pay_Default_Methods::get_all_methods();
	}

	public function init_form_fields() {
		$this->form_fields = [
			"enabled"                              => [
				"title"   => __( "Enable/Disable", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"label"   => __( "Enable Payment Method", "woocommerce-icenox-pay-plugin" ),
				"default" => "no"
			],
			"title"                                => [
				"title"       => __( "Method Title", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "The title of the payment method which will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),
				"default"     => $this->method_title,
			],
			"gateway_icon"                         => [
				"title"       => __( "Method Logo", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "URL for the payment method that will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),
				"default"     => __( "https://", "woocommerce-icenox-pay-plugin" ),
			],
			"description"                          => [
				"title"       => __( "Method Description", "woocommerce-icenox-pay-plugin" ),
				"css"         => "width:50%;",
				"type"        => "textarea",
				"default"     => "",
				"description" => __( "Description for the payment method that will show to the user on the checkout page.", "woocommerce-icenox-pay-plugin" ),

			],
			"advanced"                             => [
				"title"       => __( "API Request options", "woocommerce-icenox-pay-plugin" ) . "<hr>",
				"type"        => "title",
				"description" => "",
			],
			"icenox_pay_notification"              => [
				"title"   => __( "Auto-Update Payment Status", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"label"   => __( "Enable IceNox Pay to update the WooCommerce order status after successful payment.", "woocommerce-icenox-pay-plugin" ),
				"default" => "yes"
			],
			"icenox_pay_api_key"                   => [
				"title"       => __( "API Key", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "API Key for IceNox Pay.", "woocommerce-icenox-pay-plugin" ),
				"default"     => get_option( "icenox_pay_api_key" ),
				"placeholder" => "00000000-0000-0000-0000-000000000000"
			],
			"icenox_pay_payment_method_identifier" => [
				"title"       => __( "Payment Method Identifier", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"description" => __( "Payment Method Identifier for IceNox Pay.", "woocommerce-icenox-pay-plugin" ),
				"default"     => "",
				"placeholder" => "",
			],
			"icenox_pay_express_redirect"          => [
				"title"   => __( "Express Redirect", "woocommerce-icenox-pay-plugin" ),
				"type"    => "checkbox",
				"label"   => __( "Redirect immediately to the payment (only available for selected payment methods)", "woocommerce-icenox-pay-plugin" ),
				"default" => "no"
			],
			"debug_mode"                           => [
				"title"       => __( "Enable Debug Mode", "woocommerce-icenox-pay-plugin" ),
				"type"        => "checkbox",
				"label"       => __( "Enable ", "woocommerce-icenox-pay-plugin" ),
				"default"     => "no",
				"description" => __( "If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway work as you expected.", "woocommerce-icenox-pay-plugin" ),
			],
		];
	}


	/**
	 * Admin Panel Options
	 * - Options for bits like "title" and availability on a country-by-country basis
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function admin_options() {
		include_once __DIR__ . "/views/admin_options_html.php";
	}

	public function process_payment( $order_id ) {
		// Create IceNox Pay Payment
		$payment_request = $this->create_icenox_pay_payment( $this->api_data, $order_id );
		if ( $payment_request["success"] ) {

			$order = wc_get_order( $order_id );
			$order->update_meta_data( "icenox_pay_payment_id", $payment_request["paymentId"] );
			$order->save();

			if ( isset( $payment_request["redirect"] ) ) {
				return [
					"result"   => "success",
					"redirect" => $payment_request["redirect"]
				];
			}
		} else {
			wc_add_notice( __( "There was a problem redirecting you to the Payment Gateway. Please try again or contact our support.", "woocommerce-icenox-pay-plugin" ), "error" );

			return false;
		}

		//Redirect to "Thank You" Page
		return [
			"result"   => "success",
			"redirect" => $this->get_return_url( $order )
		];
	}

	public function create_icenox_pay_payment( $api_data, $order_id ) {
		$request_body = $this->get_request_body( $api_data, $order_id );

		$headers          = [
			"Content-Type"  => "application/json",
			"Authorization" => "Bearer " . $this->icenox_pay_api_key,
			"User-Agent"    => "IceNoxPay/" . IceNox_Pay::$plugin_version . " WooCommerce (WordPress)"
		];
		$response         = wp_remote_post( $this->api_url_to_ping, array(
			"headers" => $headers,
			"body"    => json_encode( $request_body ),
			"method"  => "POST",
		) );
		$decoded_response = json_decode( $response["body"] );

		if ( $decoded_response->success ) {
			return [
				"success"   => true,
				"paymentId" => $decoded_response->paymentid,
				"redirect"  => $decoded_response->url,
			];
		} else {
			return [
				"success" => false
			];
		}

	}

	public function get_request_body( $api_data, $order_id ) {
		$request_body = [];

		if ( ! empty( $api_data ) ) {
			$request_body = array_merge( $api_data, $request_body );
		}

		$order = new WC_Order( $order_id );

		$icenox_pay_data = [
			"paymentmethod"       => $this->icenox_pay_payment_method_identifier,
			"orderid"             => $order->get_id(),
			"add_orderid_prefix"  => false,
			"amount"              => $order->get_total() + $order->get_total_discount() - $order->get_shipping_total(),
			"shipping"            => $order->get_shipping_total(),
			"fee"                 => 0.00,
			"discount"            => $order->get_total_discount(),
			"included_vat"        => $order->get_total_tax(),
			"total"               => $order->get_total(),
			"customer_email"      => $order->get_billing_email(),
			"customer_first_name" => $order->get_billing_first_name(),
			"customer_last_name"  => $order->get_billing_last_name(),
			"customer_phone"      => $order->get_billing_phone(),
			"customer_address"    => json_encode( [
				"line1"        => $order->get_billing_address_1(),
				"line2"        => $order->get_billing_address_2(),
				"city"         => $order->get_billing_city(),
				"zip"          => $order->get_billing_postcode(),
				"state"        => $order->get_billing_state(),
				"country"      => $order->get_billing_country(),
				"country_code" => $order->get_billing_country(),
				"name"         => [
					"first" => $order->get_billing_first_name(),
					"last"  => $order->get_billing_last_name(),
				]
			] ),
			"customerid"          => $order->get_customer_id(),
			"notification_mode"   => $this->icenox_pay_notification === "yes" ? "woocommerce" : "off",
			"currency"            => $order->get_currency(),
			"shippingmethod"      => $order->get_shipping_method(),
			"redirect_url"        => $this->get_return_url( $order ),
			"express_redirect"    => $this->icenox_pay_express_redirect === "yes"
		];

		return array_merge( $icenox_pay_data, $request_body );
	}

	public function payment_fields() {
		if ( trim( $this->description ) !== "" ) {
			echo $this->description;
		} else {
			echo "";
		}
	}

	public function get_icon() {

		if ( trim( $this->gateway_icon ) === "https://" ) {
			return "";
		}

		if ( trim( $this->gateway_icon ) != "" ) {
			return '<img class="icenox-pay-method-icon customized_payment_icon" src="' . esc_attr( $this->gateway_icon ) . '" />';
		}

		return "";
	}

	/**
	 * For developers to process returned URLs from 3rd-party gateways
	 * @since 1.3.8
	 */
	public function process_returned_response() {
		do_action( "custom_payment_process_returned_result" );
		exit;
	}

}
