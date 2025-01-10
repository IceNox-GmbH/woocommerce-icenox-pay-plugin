<?php
$gateways = json_decode( get_option( "icenox_pay_gateways" ) );
if ( $gateways && is_object( $gateways ) ) {
	foreach ( $gateways as $gateway ) {
		$class_name = "icenox_pay_" . preg_replace( "/[^a-zA-Z0-9_]/", "",
				strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway->name ) ) )
			);
		if(class_exists($class_name)) {
			break;
		}

		eval( "
            class " . $class_name . " extends WC_IceNox_Pay_Payment_Gateway {
                public function __construct(){
                    parent::__construct(true);
                    \$this->id = '" . substr( $class_name, 0, 64 ) . "';
                    \$this->method_title = '" . $gateway->name . "';
                    \$this->title = 'IceNox Pay Method';
                    \$this->has_fields = false;

                    \$this->init_form_fields();
                    \$this->init_settings();


					\$this->enabled = \$this->get_option('enabled');
					\$this->title = \$this->get_option('title');
					\$this->gateway_icon = \$this->get_option('gateway_icon');
					\$this->debug_mode = \$this->get_option('debug_mode');
			
			
					\$this->description = \$this->get_option('description');
					\$this->order_status = \$this->get_option('order_status');
					\$this->customer_note = \$this->get_option('customer_note');
					\$this->customized_form = \$this->get_option('customized_form');
			
					\$this->custom_api_atts = \$this->get_option('custom_api_atts');
			
					\$this->icenox_pay_api_key = \$this->get_option('icenox_pay_api_key');
					\$this->icenox_pay_payment_method_identifier = \$this->get_option('icenox_pay_payment_method_identifier');
			
					\$this->icenox_pay_express_redirect = \$this->get_option('icenox_pay_express_redirect');
					\$this->icenox_pay_notification = \$this->get_option('icenox_pay_notification');

                    // Debug mode, only administrators can use the gateway.
                    if(\$this->debug_mode == 'yes'){
                        if( !current_user_can('administrator') ){
                            \$this->enabled = 'no';
                        }
                    }

                    add_action('woocommerce_update_options_payment_gateways_'.\$this->id, array(\$this, 'process_admin_options'));
                    add_action( 'woocommerce_receipt_'.\$this->id, array(\$this, 'receipt_page') );

                }
            }
        " );
	}
}