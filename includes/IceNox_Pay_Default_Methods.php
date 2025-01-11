<?php

class IceNox_Pay_Default_Methods {

	static function get_method_config( $method_id ): ?array {
		return self::get_all_methods()[ $method_id ] ?? null;
	}

	static function get_method_name( $method_id ): ?string {
		return self::get_all_methods()[ $method_id ]["name"] ?? null;
	}

	static function get_method_processor( $method_id ): ?array {
		return self::get_all_methods()[ $method_id ]["processor"] ?? null;
	}

	static function get_method_id_name_array(): ?array {
		return array_column( self::get_all_methods(), "name", "id" );
	}

	static function method_exists( $method_id ): bool {
		return array_key_exists( $method_id, self::get_all_methods() );
	}

	static function get_all_methods(): array {
		return [
			"affirm"            => [
				"id"        => "affirm",
				"name"      => "Affirm",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"afterpay-clearpay" => [
				"id"        => "afterpay-clearpay",
				"name"      => "Afterpay / Clearpay",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"alipay"            => [
				"id"        => "alipay",
				"name"      => "Alipay",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"amazon-pay"        => [
				"id"        => "amazon-pay",
				"name"      => "Amazon Pay",
				"processor" => [
					"stripe"     => "Stripe",
					"amazon-pay" => "Amazon Pay (requires approval)"
				]
			],
			"apple-pay"         => [
				"id"        => "apple-pay",
				"name"      => "Apple Pay",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"ct"     => "Computop",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"bancomat-pay"      => [
				"id"        => "bancomat-pay",
				"name"      => "BANCOMAT Pay",
				"processor" => [
					"mollie" => "Mollie",
				],
			],
			"bancontact"        => [
				"id"        => "bancontact",
				"name"      => "Bancontact",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"bank-transfer"      => [
				"id"        => "bank-transfer",
				"name"      => __( "SEPA Banktransfer", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
				]
			],
			"belfius"           => [
				"id"        => "belfius",
				"name"      => "Belfius",
				"processor" => [
					"mollie" => "Mollie",
				],
			],
			"blik"              => [
				"id"        => "blik",
				"name"      => "BLIK",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"sumup"  => "SumUp",
				],
			],
			"bunq"              => [
				"id"        => "bunq",
				"name"      => "bunq",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
				],
			],
			"cards"             => [
				"id"        => "cards",
				"name"      => __( "Credit card or Debit card", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"ct"     => "Computop",
					"mp"     => "Micropayment",
					"paypal" => "PayPal",
					"sumup"  => "SumUp",
				],
			],
			"cashapp"           => [
				"id"        => "cashapp",
				"name"      => "Cash App Pay",
				"processor" => [
					"stripe" => "Stripe",
				],
			],
			"direct-debit"      => [
				"id"        => "direct-debit",
				"name"      => __( "Direct Debit", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
				],
			],
			"eps"               => [
				"id"        => "eps",
				"name"      => __( "EPS", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"google-pay"        => [
				"id"        => "google-pay",
				"name"      => "Google Pay",
				"processor" => [
					"stripe" => "Stripe",
					"ct"     => "Computop",
					"sumup"  => "SumUp",
				],
			],
			"ideal"             => [
				"id"        => "ideal",
				"name"      => "iDEAL",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"kbc"               => [
				"id"        => "kbc",
				"name"      => __( "KBC Payment Button", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"mollie" => "Mollie",
				],
			],
			"klarna"            => [
				"id"        => "klarna",
				"name"      => "Klarna",
				"processor" => [
					"stripe" => "Stripe",
				],
			],
			"link"              => [
				"id"        => "link",
				"name"      => "Link",
				"processor" => [
					"stripe" => "Stripe",
				],
			],
			"mobilepay"         => [
				"id"        => "mobilepay",
				"name"      => "MobilePay",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"multibanco"        => [
				"id"        => "multibanco",
				"name"      => "Multibanco",
				"processor" => [
					"stripe" => "Stripe",
					"pay"    => "PAY.NL",
				],
			],
			"mybank"            => [
				"id"        => "mybank",
				"name"      => "MyBank",
				"processor" => [
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"p24"               => [
				"id"        => "p24",
				"name"      => "Przelewy24",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
					"sumup"  => "SumUp",
				],
			],
			"paypal"            => [
				"id"        => "paypal",
				"name"      => "PayPal",
				"processor" => [
					"paypal" => "PayPal",
					"s"      => "Stripe",
				],
			],
			"paysafecard"       => [
				"id"        => "paysafecard",
				"name"      => "PaysafeCard",
				"processor" => [
					"mp"     => "Micropayment",
					"mollie" => "Mollie",
					"m"      => "Mollie PSC",
					"pay"    => "PAY.NL",
				],
			],
			"revolut"           => [
				"id"        => "revolut",
				"name"      => "Revolut",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
					"pay"    => "PAY.NL",
				],
			],
			"revolut-pay"       => [
				"id"        => "revolut-pay",
				"name"      => "Revolut Pay",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"sofort"            => [
				"id"        => "sofort",
				"name"      => __( "Sofort", "woocommerce-icenox-pay-plugin" ),
				"processor" => [
					"stripe" => "Stripe",
					"pay"    => "PAY.NL",
				],
			],
			"swish"             => [
				"id"        => "swish",
				"name"      => "Swish",
				"processor" => [
					"stripe" => "Stripe"
				],
			],
			"tink"              => [
				"id"        => "tink",
				"name"      => "Tink",
				"processor" => [
					"mp" => "Micropayment",
					"e"  => "e-Payouts",
				],
			],
			"trustly"           => [
				"id"        => "trustly",
				"name"      => "Trustly",
				"processor" => [
					"mollie" => "Mollie",
				],
			],
			"twint"             => [
				"id"        => "twint",
				"name"      => "TWINT",
				"processor" => [
					"stripe" => "Stripe",
					"mollie" => "Mollie",
				],
			],
			"wechat"            => [
				"id"        => "wechat",
				"name"      => "WeChat Pay",
				"processor" => [
					"stripe" => "Stripe",
					"pay"    => "PAY.NL",
				],
			]
		];
	}
}