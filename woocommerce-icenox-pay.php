<?php
/* @wordpress-plugin
 * Plugin Name:             WooCommerce IceNox Pay
 * Plugin URI:              https://pay.icenox.com/
 * Description:             Connect your WooCommerce Store with IceNox Pay. The payment system for your online shop.
 * Version:                 1.11.0
 * Requires at least:       4.0
 * Tested up to:            6.7
 * Requires PHP:            7.3
 * WC requires at least:    6.0
 * WC tested up to:         9.5
 * Author:                  IceNox GmbH
 * Author URI:              https://icenox.com/
 * License:                 GPL v2 or later
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             woocommerce-icenox-pay-plugin
 * Domain Path:             /languages
 * Requires Plugins:        woocommerce
 */

class IceNox_Pay {

	/**
	 * The single instance of the class.
	 */
	protected static $_instance = null;
	public static $plugin_version = "1.11.0";

	/**
	 * @return IceNox_Pay
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * IceNox_Pay constructor.
	 */
	public function __construct() {
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		$this->load_dependencies();
		$this->handle_plugin_updates();

		add_action( "plugins_loaded", [ $this, "load_plugin_textdomain" ] );
		add_action( "plugins_loaded", [ $this, "include_payment_gateway_classes" ] );

		add_action( "admin_init", [ $this, "admin_css" ] );
		add_action( "admin_enqueue_scripts", [ $this, "admin_js" ] );
		add_action( "wp_enqueue_scripts", [ $this, "checkout_css" ] );

		add_filter( "woocommerce_payment_gateways", [ $this, "add_custom_payment_gateway" ] );

		add_action( "admin_notices", [ $this, "display_warning_notices" ] );
		add_action( "admin_notices", [ $this, "payment_method_deprecation_warning" ] );

		add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), "icenox_pay_settings_link" );

		function icenox_pay_settings_link( array $links ): array {
			$url           = get_admin_url() . "admin.php?page=wc-settings&tab=icenox_pay";
			$settings_link = '<a href="' . $url . '">' . __( "Settings", "woocommerce-icenox-pay-plugin" ) . '</a>';
			$links[]       = $settings_link;

			return $links;
		}

		add_action( "before_woocommerce_init", function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( "custom_order_tables", __FILE__, true );
			}
		} );

	}

	private function is_woocommerce_active(): bool {
		$active_plugins = (array) get_option( "active_plugins", [] );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( "active_sitewide_plugins", [] ) );
		}

		return in_array( "woocommerce/woocommerce.php", $active_plugins ) || array_key_exists( "woocommerce/woocommerce.php", $active_plugins );
	}

	private function load_dependencies() {
		require __DIR__ . "/includes/plugin-update-checker/plugin-update-checker.php";

		if ( ! class_exists( "IceNox_Pay_Settings_Page" ) ) {
			add_filter( "woocommerce_get_settings_pages", function ( $pages ) {
				$pages[] = include __DIR__ . "/includes/IceNox_Pay_Settings_Page.php" ;
                return $pages;
			} );
		}
	}

    public function include_payment_gateway_classes() {
	    require_once __DIR__ . "/includes/IceNox_Pay_Default_Methods.php";
	    require_once __DIR__ . "/includes/WC_IceNox_Pay_Payment_Gateway.php";
	    require_once __DIR__ . "/includes/WC_IceNox_Pay_Default_Method.php";
	    require_once __DIR__ . "/includes/WC_IceNox_Pay_Custom_Method.php";
    }

	private function handle_plugin_updates() {
		$updateChannel = get_option( "icenox_pay_beta" ) === "enabled" ? "beta" : "stable";

		// Update Handler
		$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			"https://pay.icenox.com/api/" . $updateChannel . "/woocommerce-plugin",
			__FILE__,
			"woocommerce-icenox-pay-plugin"
		);
	}

	function display_warning_notices() {
		if ( get_option( "icenox_pay_api_key", false ) === false ) {
			echo '<div class="notice notice-info is-dismissible">
	          <p>Thank you for installing the IceNox Pay Plugin for WooCommerce. Click <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=icenox_pay">here</a> to set up the plugin.</p>
	         </div>';
		}

		if ( get_option( "icenox_pay_api_key", false ) !== false && get_option( "icenox_pay_api_key_valid" ) !== "yes" ) {
			echo '<div class="notice notice-error is-dismissible">
	          <p>The IceNox Pay API Key is invalid. Please correct the API Key <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=icenox_pay">here</a>.</p>
	         </div>';
		}
	}

	function payment_method_deprecation_warning() {
		$deprecated_methods = [ "giropay", "klarna_pay_later", "klarna_pay_over_time", "klarna_pay_now", "klarna_us" ];
		foreach ( $deprecated_methods as $method ) {
			$this->show_deprecation_warning( $method );
		}
	}

	function show_deprecation_warning( $payment_method ) {
		$option_name   = "woocommerce_icenox_pay_" . $payment_method . "_settings";
		$method_config = get_option( $option_name, false );
		if ( $method_config ) {
			$enabled_methods = get_option( "icenox_pay_default_gateways", [] );
			if ( empty( $enabled_methods ) || ! isset( $enabled_methods[ $payment_method ] ) ) {
				delete_option( $option_name );

				return;
			}
			$method_processor         = $method_config["icenox_pay_processor"] ?: "stripe";
			$method_config["enabled"] = "no";
			update_option( $option_name, $method_config );
			?>
            <div class="notice notice-error is-dismissible">
                <p><strong>IceNox Pay Warning:</strong> The payment method
                    <strong><?php echo $payment_method; ?></strong> has been disabled,
                    because it is no longer supported by the selected Payment Processor
                    (<?php echo ucfirst( $method_processor ); ?>).</p>
                <p>It will no longer be offered in Checkout.
                    Please remove giropay from the enabled payment methods in the
                    <a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=icenox_pay">IceNox Pay Tab</a>.
                    For questions, please contact Merchant Support.
                </p>
            </div>
			<?php
		}
	}

	public function admin_js( $hook ) {
		if ( "woocommerce_page_wc-settings" === $hook ) {
			wp_enqueue_script( "icenox-pay", plugins_url( "includes/assets/js/icenox-pay.js", __FILE__ ), [ "jquery" ], $this::$plugin_version, true );
		}
	}

	public function admin_css() {
		wp_enqueue_style( "icenox_pay_admin_css", plugins_url( "includes/assets/css/admin.css", __FILE__ ), [], $this::$plugin_version );
	}

	public function checkout_css() {
		if (function_exists("is_checkout") && is_checkout()) {
			wp_enqueue_style( "icenox_pay_checkout_css", plugins_url( "includes/assets/css/checkout.css", __FILE__ ), [], $this::$plugin_version );
		}
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( "woocommerce-icenox-pay-plugin", false, dirname( plugin_basename( __FILE__ ) ) . "/languages/" );
	}

	public function add_custom_payment_gateway( $gateways ) {
		$default_gateways = get_option( "icenox_pay_default_gateways" );

		if ( $default_gateways ) {
			foreach ( $default_gateways as $gateway ) {
				$gateways[] = new WC_IceNox_Pay_Default_Method($gateway);
			}
		}

		$custom_gateways = json_decode( get_option( "icenox_pay_gateways" ) );

		if ( $custom_gateways ) {
			foreach ( $custom_gateways as $gateway ) {

				$gateway_id  = "icenox_pay_custom_" . preg_replace( "/[^a-zA-Z0-9_]/", "", strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway->name ) ) ) );
				$gateways[] = new WC_IceNox_Pay_Custom_Method( $gateway_id, $gateway->name );
			}
		}

		return $gateways;
	}
}

IceNox_Pay::get_instance();