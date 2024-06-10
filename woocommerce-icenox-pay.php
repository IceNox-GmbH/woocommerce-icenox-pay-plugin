<?php
/* @wordpress-plugin
 * Plugin Name:             WooCommerce IceNox Pay
 * Plugin URI:              https://pay.icenox.com/
 * Description:             Connect your WooCommerce Store with IceNox Pay. The payment system for your online shop.
 * Version:                 1.9.3
 * Requires at least:       4.0
 * Tested up to:            6.5
 * Requires PHP:            7.3
 * WC requires at least:    3.0
 * WC tested up to:         8.9.2
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
	public static $plugin_version = "1.9.3";

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

		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_custom_payment_gateway' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'init_custom_payment_gateway' ] );
		add_action( 'admin_init', [ $this, 'admin_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_js' ] );
		add_action( 'admin_notices', [ $this, 'api_key_not_set' ] );
		add_action( 'admin_notices', [ $this, 'api_key_invalid' ] );


		//add_action( 'woocommerce_cart_calculate_fees', 'woocommerce_custom_surcharge' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'icenox_pay_settings_link' );

		function icenox_pay_settings_link( array $links ): array {
			$url           = get_admin_url() . "admin.php?page=wc-settings&tab=icenox_pay";
			$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'icenox_pay' ) . '</a>';
			$links[]       = $settings_link;

			return $links;
		}

		add_action( 'before_woocommerce_init', function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		} );

	}

	private function is_woocommerce_active(): bool {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}

	private function load_dependencies() {
		require __DIR__ . '/includes/plugin-update-checker/plugin-update-checker.php';

		if ( ! class_exists( 'IceNox_Pay_Settings_Page' ) ) {
			add_filter( 'woocommerce_get_settings_pages', 'add_icenox_pay_settings_tab' );
			function add_icenox_pay_settings_tab( $pages ) {
				$pages[] = include( dirname( __FILE__ ) . '/includes/icenox-pay-settings-page.php' );

				return $pages;
			}
		}
	}

	private function handle_plugin_updates() {
		$updateChannel = get_option( "icenox_pay_beta" ) === "enabled" ? "beta" : "stable";

		// Update Handler
		$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://pay.icenox.com/api/' . $updateChannel . '/woocommerce-plugin',
			__FILE__,
			'woocommerce-icenox-pay-plugin'
		);
	}

	function api_key_not_set() {
		if ( get_option( "icenox_pay_api_key" ) === false ) {
			echo '<div class="notice notice-info is-dismissible">
          <p>Thank you for installing the IceNox Pay Plugin for WooCommerce. Click <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=icenox_pay">here</a> to set up the plugin.</p>
         </div>';
		}
	}

	function api_key_invalid() {
		if ( get_option( "icenox_pay_api_key" ) !== false && get_option( "icenox_pay_api_key_valid" ) !== "yes" ) {
			echo '<div class="notice notice-error is-dismissible">
          <p>The IceNox Pay API Key is invalid. Please correct the API Key <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=icenox_pay">here</a>.</p>
         </div>';
		}
	}

	public function admin_js( $hook ) {
		if ( 'woocommerce_page_wc-settings' === $hook ) {
			wp_enqueue_script( 'icenox-pay', plugins_url( "includes/assets/js/icenox-pay.js", __FILE__ ), array(
				'jquery',
				'jquery-ui-sortable',
				'jquery-ui-accordion'
			), $this::$plugin_version, true );
			wp_enqueue_script( 'nice-select', plugins_url( "includes/assets/js/nice-select.js", __FILE__ ), array(
				'jquery'
			), $this::$plugin_version, true );
		}
	}

	public function init_custom_payment_gateway() {
		require_once 'class-woocommerce-icenox-pay-gateway.php';
		require_once __DIR__ . '/includes/default-gateway-classes-generator.php';
		require_once __DIR__ . '/includes/gateway-classes-generator.php';
	}

	public function admin_css() {
		wp_enqueue_style( 'custom_payment_admin_css', plugins_url( 'includes/assets/css/admin.css', __FILE__ ) );
		wp_enqueue_style( 'jquery-css', plugins_url( 'includes/assets/css/nice-select.css', __FILE__ ) );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-custom-payment-gateway', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	public function add_custom_payment_gateway( $gateways ) {
		$default_gateways = get_option( 'icenox_pay_default_gateways' );

		if ( $default_gateways ) {
			foreach ( $default_gateways as $gateway ) {

				$gateway_id              = 'icenox_pay_' . preg_replace( '/^a-zA-Z0-9/', "",
						strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway ) ) )
					);
				$gateways[ $gateway_id ] = $gateway_id;
			}
		}

		$custom_gateways = json_decode( get_option( 'icenox_pay_gateways' ) );

		if ( $custom_gateways ) {
			foreach ( $custom_gateways as $gateway ) {

				$gateway_id              = 'icenox_pay_' . preg_replace( '/^a-zA-Z0-9/', "",
						strtolower( str_replace( " ", "_", str_replace( "-", "_", $gateway->name ) ) )
					);
				$gateways[ $gateway_id ] = $gateway_id;
			}
		}

		return $gateways;
	}
}

IceNox_Pay::get_instance();