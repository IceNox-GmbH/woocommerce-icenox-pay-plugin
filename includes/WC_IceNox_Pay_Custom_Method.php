<?php
class WC_IceNox_Pay_Custom_Method extends WC_IceNox_Pay_Payment_Gateway {
	public function __construct($id, $name) {
		parent::__construct(true);

		$this->id = $id;
		$this->method_title = $name;
		$this->title           = __( "IceNox Pay Method", "woocommerce-icenox-pay-plugin" );
		$this->has_fields      = false;

		$this->init_form_fields();
		$this->init_settings();

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

		add_action( "woocommerce_update_options_payment_gateways_" . $this->id, [
			$this,
			"process_admin_options"
		] );

	}
}