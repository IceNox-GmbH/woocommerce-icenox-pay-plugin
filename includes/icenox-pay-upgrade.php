<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class IceNox_Pay_Upgrades {

	public static $instance;
	/**
	 * IceNox Pay Upgrade Changes constructor.
	 */
	public function __construct() {
		add_action('init', array($this, 'perform_upgrades'));
		self::$instance = $this;
	}

	public function perform_upgrades(){
		if ( get_option( 'icenox_pay_upgrade' ) != 'done' ) {
			$this->perform_icenox_pay_upgrade();
		}
	}

    /**
     * Perform Upgrade Changes
     */
	public function perform_icenox_pay_upgrade() {

		$gateways = get_option('wpruby_generated_custom_gatwayes');
		if($gateways) {
			add_option( 'icenox_pay_gateways', $gateways );
		}
		add_option('icenox_pay_upgrade', 'done');
	}

	/**
	 * @return IceNox_Pay_Upgrades
	 */
	public static function get_instance(){
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

IceNox_Pay_Upgrades::get_instance();