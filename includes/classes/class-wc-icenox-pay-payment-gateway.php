<?php

class WC_IceNox_Pay_Payment_Gateway extends WC_Payment_Gateway {

	public $defaultGateways = [];

	protected $gateway_icon;
	protected $api_data = [];
	protected $debug_mode;
	protected $icenox_pay_api_url;

	protected $icenox_pay_api_key;
	protected $icenox_pay_method_processor;
	protected $icenox_pay_payment_method_identifier;

	protected $icenox_pay_express_redirect;


	public function __construct() {
		$this->icenox_pay_api_url = "https://imp.icenox.com/api/payment/create/";

		add_action( "woocommerce_api_" . strtolower( get_class( $this ) ), [
			$this,
			"process_returned_response"
		] );

		$this->defaultGateways = IceNox_Pay_Default_Methods::get_all_methods();
	}

	protected function load_method_icon_picker() {
		wp_enqueue_media();
		wp_enqueue_script( "wp-media-picker-js" );
		wp_enqueue_style( "wp-media-picker-css" );
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
				"default" => "",
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
			"icenox_pay_api_key"                   => [
				"title"       => __( "API Key", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"default"     => get_option( "icenox_pay_api_key" ),
				"placeholder" => "00000000-0000-0000-0000-000000000000"
			],
			"icenox_pay_payment_method_identifier" => [
				"title"       => __( "Payment Method Identifier", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"default"     => "",
				"placeholder" => "",
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

	public function admin_options() {
		?>
		<h2>
			<?php echo esc_html( $this->get_method_title() ); ?> [IceNox Pay]
			<?php wc_back_link( __( "Return to payments", "woocommerce" ), admin_url( "admin.php?page=wc-settings&tab=checkout" ) ); ?>
		</h2>
		<div id="poststuff" class="icenox-pay-method-settings">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<table class="form-table">
						<?php $this->generate_settings_html(); ?>
					</table>
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable">
						<div class="postbox " id="icenox-support">
							<h3><?php echo __( "Need Help?", "woocommerce-icenox-pay-plugin" ); ?></h3>
							<div class="inside">
								<div class="support-widget">
									<img src="https://pay.icenox.com/static/images/logo-dark.svg" alt="IceNox Pay">
									<span><?php _e( "Contact our Merchant Support, if you need help configuring the plugin.", "woocommerce-icenox-pay-plugin" ); ?></span>
									<ul>
										<li>» <a href="mailto:info@icenox.com" target="_blank"><?php echo __( "Email Us", "woocommerce-icenox-pay-plugin" ); ?></a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<?php
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

		$headers  = [
			"Content-Type"  => "application/json",
			"Authorization" => "Bearer " . $this->icenox_pay_api_key,
			"User-Agent"    => "IceNoxPay/" . IceNox_Pay::$plugin_version . " WooCommerce (WordPress)"
		];
		$response = wp_remote_post( $this->icenox_pay_api_url, [
			"headers" => $headers,
			"body"    => json_encode( $request_body ),
			"method"  => "POST",
		] );
		if ( is_wp_error( $response ) ) {
			return [
				"success" => false
			];
		}
		$decoded_response = json_decode( $response["body"] );

		if ( $decoded_response && $decoded_response->success ) {
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
			"notification_mode"   => "woocommerce",
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
