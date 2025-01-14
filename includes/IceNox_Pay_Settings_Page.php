<?php


// Exit if accessed directly
if ( ! defined( "ABSPATH" ) ) {
	exit;
}

/**
 * IceNox Pay Settings page to configure Payment Methods and the API Key.
 *
 * @author IceNox GmbH
 * @since 1.0.0
 */
class IceNox_Pay_Settings_Page extends WC_Settings_Page {

	public $name;
	public $defaultGateways = [
		"affirm"            => "Affirm",
		"afterpay-clearpay" => "Afterpay / Clearpay",
		"alipay"            => "Alipay",
		"amazon-pay"        => "Amazon Pay",
		"apple-pay"         => "Apple Pay",
		"bancomat-pay"      => "BANCOMAT Pay",
		"bancontact"        => "Bancontact",
		"banktransfer"      => "SEPA Banktransfer",
		"belfius"           => "Belfius",
		"blik"              => "BLIK",
		"bunq"              => "bunq",
		"cards"             => "Credit Card or Debit Card",
		"cashapp"           => "Cash App Pay",
		"direct-debit"      => "Direct Debit",
		"eps"               => "EPS",
		"google-pay"        => "Google Pay",
		"ideal"             => "iDEAL",
		"kbc"               => "KBC Payment Button",
		"klarna"            => "Klarna",
		"link"              => "Link",
		"mobilepay"         => "MobilePay",
		"multibanco"        => "Multibanco",
		"mybank"            => "MyBank",
		"p24"               => "Przelewy24",
		"paypal"            => "PayPal",
		"paysafecard"       => "paysafecard",
		"revolut"           => "Revolut",
		"revolut-pay"       => "Revolut Pay",
		"sofort"            => "Sofort",
		"swish"             => "Swish",
		"tink"              => "Tink",
		"trustly"           => "Trustly",
		"twint"             => "TWINT",
		"wechat"            => "WeChat Pay"
	];

	public function __construct() {
		$this->id    = "icenox_pay";
		$this->label = "IceNox Pay";

		add_filter( "woocommerce_settings_tabs_array", [ $this, "add_settings_tab" ], 50 );
		add_action( "woocommerce_settings_tabs_icenox_pay", [ $this, "settings_tab" ] );
		add_action( "woocommerce_update_options_icenox_pay", [ $this, "update_settings" ] );
		add_action( "woocommerce_admin_field_gateways_table", [ $this, "gateways_table_setting" ] );
		if ( isset( $_POST["wc_gateway_name"] ) && trim( $_POST["wc_gateway_name"] ) !== "" ) {
			global $current_user;

			$gatewayId = $this->generateGatewayId( trim( $_POST["wc_gateway_name"] ) );
			$gateways = json_decode( get_option( "icenox_pay_gateways" ), true ) ?? [];

			if ( in_array( $gatewayId, array_keys( $gateways ) ) ) {
				add_action( "admin_notices", function () {
					?>
                    <div class="notice notice-error is-dismissible">
                        <p>
                            <strong><?php _e( "The entered custom method name already exists by another method. Please use a different one!", "woocommerce-icenox-pay-plugin" ); ?></strong>
                        </p>
                    </div>
					<?php
				} );

				return;
			}

			if ( IceNox_Pay_Default_Methods::method_exists( $gatewayId ) ) {
				add_action( "admin_notices", function () {
					?>
                    <div class="notice notice-error is-dismissible">
                        <p>
                            <strong><?php _e( "The entered custom method name is being used by a default method.  Please use a different one!", "woocommerce-icenox-pay-plugin" ); ?></strong>
                        </p>
                    </div>
					<?php
				} );

				return;
			}

			$gateways[ $gatewayId ]["name"]       = trim( $_POST["wc_gateway_name"] );
			$gateways[ $gatewayId ]["created_on"] = time();
			$gateways[ $gatewayId ]["created_by"] = $current_user->user_login;
			update_option( "icenox_pay_gateways", json_encode( $gateways ) );

			add_action( "admin_notices", function () {
				global $gatewayId;
				?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong><?php _e( "Successfully created a new custom method! Please configure it below.", "woocommerce-icenox-pay-plugin" ); ?></strong>
                    </p>
                </div>
				<?php
			} );
		}

		if ( isset( $_GET["action"] ) == "delete" ) {
			if ( isset( $_GET["gateway"] ) ) {
				$gateways  = json_decode( get_option( "icenox_pay_gateways" ), true );
                if(isset($gateways[ $_GET["gateway"] ])) {
                    unset($gateways[ $_GET["gateway"] ]);
                } else {
	                $gatewayId = $this->generateGatewayId( $_GET["gateway"] );
	                unset( $gateways[ $gatewayId ] );
                }
				update_option( "icenox_pay_gateways", json_encode( $gateways ) );
				wp_redirect( admin_url( "admin.php?page=wc-settings&tab=icenox_pay" ) );
				exit;
			}
		}

		if ( isset( $_POST["icenox_pay_api_key"] ) ) {
			if ( trim( $_POST["icenox_pay_api_key"] ) !== "" ) {
				$check_api_key = $this->check_api_key( trim( $_POST["icenox_pay_api_key"] ) );
				if ( $check_api_key["success"] ) {
					if ( get_option( "icenox_pay_merchant_name" ) !== false ) {
						update_option( "icenox_pay_merchant_name", $check_api_key["merchantName"] );
					} else {
						add_option( "icenox_pay_merchant_name", $check_api_key["merchantName"] );
					}
					if ( get_option( "icenox_pay_merchant_id" ) !== false ) {
						update_option( "icenox_pay_merchant_id", $check_api_key["merchantId"] );
					} else {
						add_option( "icenox_pay_merchant_id", $check_api_key["merchantId"] );
					}
					if ( get_option( "icenox_pay_api_key_valid" ) !== false ) {
						update_option( "icenox_pay_api_key_valid", "yes" );
					} else {
						add_option( "icenox_pay_api_key_valid", "yes" );
					}
				} else {
					update_option( "icenox_pay_api_key", "" );
					update_option( "icenox_pay_merchant_name", "" );
					update_option( "icenox_pay_merchant_id", "" );
					update_option( "icenox_pay_api_key_valid", "no" );
				}
			} else {
				update_option( "icenox_pay_api_key", "" );
				update_option( "icenox_pay_merchant_name", "" );
				update_option( "icenox_pay_merchant_id", "" );
			}
		}
	}

	private function generateGatewayId( $name ) {
		/** The gatewayId is generated by taking the submitted name and
		 * 1. replacing any spaces or hyphens with underscores
		 * 2. making all letters lowercase
		 * 3. then removing any characters that are no letters or numbers
		 * 4. and shortening it to 53 characters, if longer
		 */
		return substr( preg_replace( "/[^a-zA-Z0-9_]/", "",
			strtolower( str_replace( " ", "_", str_replace( "-", "_", trim( $name ) ) ) )
		), 0, 53 );
	}

	public function check_api_key( $api_key ) {

		$headers         = [
			"Content-Type" => "application/json",
			"x-api-key"    => $api_key
		];
		$response        = wp_remote_post( "https://imp.icenox.com/api/merchant/", [
			"headers" => $headers,
			"method"  => "GET",
		] );
		$decodedResponse = json_decode( $response["body"] );

		if ( $decodedResponse->success ) {
			return [
				"success"      => true,
				"merchantId"   => $decodedResponse->merchant_id,
				"merchantName" => $decodedResponse->merchant_name,
			];
		} else {
			return [
				"success" => false
			];
		}

	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 *
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs["icenox_pay"] = "IceNox Pay";

		return $settings_tabs;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses $this->get_settings()
	 */
	public function settings_tab() {
		$this->name = "";

		woocommerce_admin_fields( $this->get_settings() );
		$this->name = "";
	}

	/**
	 * Get all the settings for this plugin for @return array Array of settings for @see woocommerce_admin_fields() function.
	 * @see woocommerce_admin_fields() function.
	 *
	 */
	public function get_settings() {

		$enabled_method_keys   = get_option( "icenox_pay_default_gateways", [] );
		$enabled_method_values = array_map( function ( $value ) {
            return IceNox_Pay_Default_Methods::get_method_name( $value ) ?? $value . " (DEPRECATED)";
		}, $enabled_method_keys );
		$enabled_methods       = array_combine( $enabled_method_keys, $enabled_method_values );

		$default_method_options = array_merge( $enabled_methods, IceNox_Pay_Default_Methods::get_method_id_name_array() );

		return [
			"title_gateways_options" => [
				"title" => __( "IceNox Pay Settings", "woocommerce-icenox-pay-plugin" ),
				"type"  => "title",
				"id"    => "title_gateways_options"
			],
			"api_key"                => [
				"title"    => __( "API Key", "woocommerce-icenox-pay-plugin" ),
				"desc"     => __( "Please enter your IceNox Pay API Key. The API Key can be found in the Merchant Dashboard.", "woocommerce-icenox-pay-plugin" ),
				"id"       => "icenox_pay_api_key",
				"type"     => "text",
				"css"      => "min-width:300px;",
				"default"  => "",
				"autoload" => true,
			],
			"merchant_id"            => [
				"title"       => __( "Merchant ID", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"class"       => "disabled",
				"css"         => "min-width:300px;pointer-events:none",
				"value"       => get_option( "icenox_pay_merchant_id" ),
				"placeholder" => "Will be filled in automatically",
				"autoload"    => true,
			],
			"merchant_name"          => [
				"title"       => __( "Merchant Name", "woocommerce-icenox-pay-plugin" ),
				"type"        => "text",
				"class"       => "disabled",
				"css"         => "min-width:300px;pointer-events:none",
				"value"       => get_option( "icenox_pay_merchant_name" ),
				"placeholder" => "Will be filled in automatically",
				"autoload"    => true,
			],
			"advanced_mode"          => [
				"title"   => __( "Advanced Mode", "woocommerce-icenox-pay-plugin" ),
				"id"      => "icenox_pay_advanced_mode",
				"type"    => "checkbox",
				"value"   => get_option( "icenox_pay_advanced_mode" ),
				"default" => "no"
			],
			[
				"type" => "sectionend",
				"id"   => "title_gateways_options"
			],
			"title"                  => [
				"title" => __( "Add or Remove Payment Methods", "woocommerce-icenox-pay-plugin" ),
				"type"  => "title",
				"id"    => "add_gateway",
				"css"   => "margin-top: 30px;"
			],
			"default_methods"        => [
				"title"   => __( "Payment Methods", "woocommerce-icenox-pay-plugin" ),
				"desc"    => __( "Select which IceNox Pay Methods you would like to use.", "woocommerce-icenox-pay-plugin" ),
				"id"      => "icenox_pay_default_gateways",
				"class"   => "icenox_pay_multiselect",
				"type"    => "multiselect",
				"options" => $default_method_options
			],
			"name"                   => get_option( "icenox_pay_advanced_mode" ) === "yes" ? [
				"title"     => __( "Custom Method", "woocommerce-icenox-pay-plugin" ),
				"desc"      => __( "Enter the name of the payment method then click on Save Changes.", "woocommerce-icenox-pay-plugin" ),
				"id"        => "wc_gateway_name",
				"type"      => "text",
				"css"       => "min-width:300px;",
				"default"   => "",
				"autoload"  => true,
				"value"     => "",
                "custom_attributes" => [
	                "maxlength" => "32",
                ]
			] : [],
			[
				"type" => "sectionend",
				"id"   => "add_gateway"
			],
			"title_gateways_table"   => [
				"title" => __( "Available Payment Methods", "woocommerce-icenox-pay-plugin" ),
				"type"  => "title",
				"id"    => "add_gateways"
			],
			[
				"type" => "sectionend",
				"id"   => "add_gateways"
			],
			"generated_gateways"     => [
				"type" => "gateways_table"
			],
		];
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses $this->get_settings()
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	public function gateways_table_setting() {
		$enabled_default_gateways = get_option( "icenox_pay_default_gateways" );
		$gateways                 = json_decode( get_option( "icenox_pay_gateways" ) );
		?>
        <tr valign="top">
            <td class="wc_emails_wrapper" colspan="2">
                <table class="wc_emails widefat" cellspacing="0">
                    <thead>
                    <tr>
						<?php
						$columns = apply_filters( "woocommerce_custom_gateways_setting_columns", [
							"status"     => "",
							"icon"       => __( "Icon", "woocommerce-icenox-pay-plugin" ),
							"name"       => __( "Payment Method", "woocommerce-icenox-pay-plugin" ),
							"processor"  => __( "Processor", "woocommerce-icenox-pay-plugin" ),
							"created_by" => __( "Created By", "woocommerce-icenox-pay-plugin" ),
							"actions"    => __( "Actions", "woocommerce-icenox-pay-plugin" ),
						] );
						foreach ( $columns as $key => $column ) {
							echo '<th class="wc-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
						}
						?>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					if ( $enabled_default_gateways ) {
						foreach ( $enabled_default_gateways as $gateway_key => $gateway ) {
							$class_name       = "icenox_pay_" . str_replace( "-", "_", $gateway );
							$gateway_settings = get_option( "woocommerce_" . $class_name . "_settings" );
							$user             = "IceNox Pay";
							$processorMap     = [
								"stripe" => "Stripe",
								"s"      => "Stripe",
								"mollie" => "Mollie",
								"m"      => "Mollie PSC",
								"pay"    => "PAY.NL",
								"mp"     => "Micropayment",
								"ct"     => "Computop",
								"sumup"  => "SumUp",
								"paypal" => "PayPal",
								"e"      => "e-Payouts"
							];
							if ( isset( $gateway_settings["icenox_pay_processor"] ) ) {
								if ( isset( $processorMap[ $gateway_settings["icenox_pay_processor"] ] ) ) {
									$processor = $processorMap[ $gateway_settings["icenox_pay_processor"] ];
								} else {
									if ( $gateway_settings["icenox_pay_processor"] === $gateway ) {
										$processor = IceNox_Pay_Default_Methods::get_method_name( $gateway ) ?? "";
									} else {
										$processor = $gateway_settings["icenox_pay_processor"];
									}
								}
							} else {
								$processor = "";
							}

							$gateway_title = IceNox_Pay_Default_Methods::get_method_name( $gateway ) ?? $gateway;
							echo '<tr>';
							foreach ( $columns as $key => $column ) {

								switch ( $key ) {
									case 'icon' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
                                        if(isset($gateway_settings["gateway_icon"]) && $gateway_settings["gateway_icon"] !== "https://") {
                                            echo '<img class="icenox-pay-settings-method-icon" src="' . $gateway_settings["gateway_icon"] . '"/>';
                                        }
                                        echo '</td>';
										break;
									case 'name' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
                                            <a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $class_name ) ) . '">' . $gateway_title . '</a>
                                        </td>';
										break;
									case 'status' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
										if ( $gateway_settings ) {
											if ( $gateway_settings['enabled'] == 'yes' ) {
												echo '<span class="status-enabled tips" data-tip="' . __( 'Enabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'Yes', 'woocommerce-icenox-pay-plugin' ) . '</span>';
											} else {
												echo '<span class="status-disabled tips" data-tip="' . __( 'Disabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'No', 'woocommerce-icenox-pay-plugin' ) . '</span>';
											}
										} else {
											echo '<span class="status-disabled tips" data-tip="' . __( 'Disabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'No', 'woocommerce-icenox-pay-plugin' ) . '</span>';
										}
										echo '</td>';
										break;
									case 'actions' :
										echo '<td style="width:200px;">
                                            <a class="button tips" data-tip="' . __( 'Configure', 'woocommerce-icenox-pay-plugin' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $class_name ) ) . '">' . __( 'Configure', 'woocommerce-icenox-pay-plugin' ) . '</a>
                                        </td>';
										break;
									case 'processor' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										' . $processor . '
										</td>';
										break;
									case 'created_by' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										' . $user . '
										</td>';
										break;
									default :
										break;
								}
							}

							echo '</tr>';
						}
					}

					if ( $gateways ) {
						foreach ( $gateways as $gateway_key => $gateway ) {
							$class_name       = "icenox_pay_custom_" . preg_replace( "/[^a-zA-Z0-9_]/", "", strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway->name ) ) ) );
							$gateway_settings = get_option( "woocommerce_" . $class_name . "_settings" );
							$user             = get_user_by( "login", $gateway->created_by );
							$gateway_title    = ( isset( $gateway_settings["title"] ) ) ? $gateway_settings["title"] : $gateway->name;
							echo "<tr>";
							foreach ( $columns as $key => $column ) {

								switch ( $key ) {
									case 'icon' :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
										if(isset($gateway_settings["gateway_icon"]) && $gateway_settings["gateway_icon"] !== "https://") {
											echo '<img class="icenox-pay-settings-method-icon" src="' . $gateway_settings["gateway_icon"] . '"/>';
										}
										echo '</td>';
										break;
									case "name" :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
                                            <a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $class_name ) ) . '">' . $gateway_title . '</a>
                                        </td>';
										break;
									case "status" :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
										if ( $gateway_settings ) {
											if ( $gateway_settings['enabled'] == 'yes' ) {
												echo '<span class="status-enabled tips" data-tip="' . __( 'Enabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'Yes', 'woocommerce-icenox-pay-plugin' ) . '</span>';
											} else {
												echo '<span class="status-disabled tips" data-tip="' . __( 'Disabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'No', 'woocommerce-icenox-pay-plugin' ) . '</span>';
											}
										} else {
											echo '<span class="status-disabled tips" data-tip="' . __( 'Disabled', 'woocommerce-icenox-pay-plugin' ) . '">' . __( 'No', 'woocommerce-icenox-pay-plugin' ) . '</span>';
										}
										echo '</td>';
										break;
									case "actions" :
										echo '<td style="width:200px;">
                                            <a class="button tips" data-tip="' . __( 'Configure', 'woocommerce-icenox-pay-plugin' ) . '" href="' . admin_url( "admin.php?page=wc-settings&tab=checkout&section=" . strtolower( $class_name ) ) . '">' . __( "Configure", "woocommerce-icenox-pay-plugin" ) . '</a>
                                            <a style="color:red;" class="button" onclick="if(!window.confirm(\'Are you sure that you want to delete this gateway?\')) return false;" href="' . admin_url( "admin.php?page=wc-settings&tab=icenox_pay&action=delete&gateway=" . $gateway_key . "&noheader=true" ) . '">' . __( "Delete", "woocommerce-icenox-pay-plugin" ) . '</a>
                                        </td>';
										break;
									case "processor" :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">' .
										     __( "Custom Method", "woocommerce-icenox-pay-plugin" ) .
										     '</td>';
										break;
									case "created_by" :
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
                                            <a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $gateway->created_by . '</a>
                                        </td>';
										break;
									default :
										break;
								}
							}

							echo "</tr>";
						}
					}
					?>
                    </tbody>
                </table>
            </td>
        </tr>
		<?php
	}
}

return new IceNox_Pay_Settings_Page();
