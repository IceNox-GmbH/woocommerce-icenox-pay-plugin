<?php
$gateways = get_option( 'icenox_pay_default_gateways' );

if ( $gateways ) {
	foreach ( $gateways as $gateway ) {
		$class_name = 'icenox_pay_' . preg_replace( '/[^a-zA-Z0-9_]/', "",
				strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway ) ) )
			);
		if ( file_exists( __DIR__ . '/default-gateways/icenox-pay-' . str_replace( "_", "-", $gateway ) . ".php" ) ) {
			require_once( __DIR__ . '/default-gateways/icenox-pay-' . str_replace( "_", "-", $gateway ) . ".php" );
		} else {
			eval( "
	            class " . $class_name . " extends WC_IceNox_Pay_Payment_Gateway {
	                public function __construct(){
	                    parent::__construct(true);
	                    \$this->id = '" . $class_name . "';
	                    \$this->method_title = \$this->defaultGateways['" . $gateway . "']['name'];
	                    \$this->title = 'IceNox Pay Method';
	                    \$this->has_fields = false;
	
	                    \$this->init_form_fields();
	                    \$this->init_settings();
	
	
						\$this->enabled = \$this->get_option('enabled');
						\$this->title = \$this->get_option('title');
						\$this->gateway_icon = \$this->get_option('gateway_icon');
						\$this->debug_mode = \$this->get_option('debug_mode');
				
				
						\$this->description = \$this->get_option('description');
				
						\$this->custom_api_atts = \$this->get_option('custom_api_atts');
				
						\$this->icenox_pay_api_key = get_option( 'icenox_pay_api_key' );
						\$this->icenox_pay_method_processor = \$this->get_option('icenox_pay_processor') ?: ((isset(\$this->defaultGateways['" . $gateway . "']) && !empty(\$this->defaultGateways['" . $gateway . "']['processor'])) ? \array_key_first(\$this->defaultGateways['" . $gateway . "']['processor']) : null);	
						
						\$this->icenox_pay_payment_method_identifier = (\$this->icenox_pay_method_processor !== '" . $gateway . "') ? 
							\$this->icenox_pay_method_processor . '-" . $gateway . "' : '" . $gateway . "';
				
						\$this->icenox_pay_express_redirect = \$this->get_option('icenox_pay_express_redirect');
						\$this->icenox_pay_notification = 'yes';
	
	                    // Debug mode, only administrators can use the gateway.
	                    if(\$this->debug_mode == 'yes'){
	                        if( !current_user_can('administrator') ){
	                            \$this->enabled = 'no';
	                        }
	                    }
	
	                    add_action('woocommerce_update_options_payment_gateways_'.\$this->id, array(\$this, 'process_admin_options'));
	                    add_action( 'woocommerce_receipt_'.\$this->id, array(\$this, 'receipt_page') );
	
	                }
	                
	                public function init_form_fields() {
						\$this->form_fields = array(
							'enabled'                              => array(
								'title'   => __( 'Enable/Disable', 'woocommerce-icenox-pay-plugin' ),
								'type'    => 'checkbox',
								'label'   => __( 'Enable Payment Method', 'woocommerce-icenox-pay-plugin' ),
								'default' => 'no'
							),
							'title'                                => array(
								'title'       => __( 'Method Title', 'woocommerce-icenox-pay-plugin' ),
								'type'        => 'text',
								'description' => __( 'The title of the payment method which will show to the user on the checkout page.', 'woocommerce-icenox-pay-plugin' ),
								'default'     => \$this->defaultGateways['" . $gateway . "']['name'],
							),
							'gateway_icon'                         => array(
								'title'       => __( 'Method Logo', 'woocommerce-icenox-pay-plugin' ),
								'type'        => 'text',
								'description' => __( 'URL for the payment method that will show to the user on the checkout page.', 'woocommerce-icenox-pay-plugin' ),
								'default'     => '" . home_url() . "/wp-content/plugins/woocommerce-icenox-pay-plugin/includes/assets/images/paymentmethods/" . $gateway . ".svg',
							),
							'description'                          => array(
								'title'       => __( 'Method Description', 'woocommerce-icenox-pay-plugin' ),
								'css'         => 'width:50%;',
								'type'        => 'textarea',
								'default'     => '',
								'description' => __( 'Description for the payment method that will show to the user on the checkout page.', 'woocommerce-icenox-pay-plugin' ),
							),
							'advanced'                             => array(
								'title'       => __( 'Method Settings<hr>', 'woocommerce-icenox-pay-plugin' ),
								'type'        => 'title',
								'description' => '',
							),
							'icenox_pay_processor'                 => \$this->defaultGateways['" . $gateway . "']['processor'] ? array(
								'title'       => __( 'Payment Processor', 'woocommerce-icenox-pay-plugin' ),
								'type'        => 'select',
								'description' => __( 'Please select your Payment Service Provider to process this payment method.', 'woocommerce-icenox-pay-plugin' ),
								'options'     => \$this->defaultGateways['" . $gateway . "']['processor']
							) : array(
								'type'        => 'text',
								'css'         => 'display:none',
								'value'     => '" . $gateway . "',
								'default'     => '" . $gateway . "'
							),
							'icenox_pay_express_redirect'          => array(
								'title'   => __( 'Express Redirect', 'woocommerce-icenox-pay-plugin' ),
								'type'    => 'checkbox',
								'label'   => __( 'Redirect immediately to the payment (only available for selected payment methods)', 'woocommerce-icenox-pay-plugin' ),
								'default' => 'no'
							),
							'debug_mode'                           => array(
								'title'       => __( 'Enable Debug Mode', 'woocommerce-icenox-pay-plugin' ),
								'type'        => 'checkbox',
								'label'       => __( 'Enable ', 'woocommerce-icenox-pay-plugin' ),
								'default'     => 'no',
								'description' => __( 'If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway works as expected.' ),
							),
						);
	                }
	            }
	        " );
		}
	}
}