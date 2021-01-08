<?php

namespace YouSaidItCards;

// If this file is called directly, abort.
use YouSaidItCards\Modules\WooCommerce\SquarePaymentRestClient;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_ajax_yousaidit_test', [ self::$instance, 'stackonet_test' ] );
		}

		return self::$instance;
	}

	/**
	 * A AJAX method just to test some data
	 */
	public function stackonet_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ) );
		}

		$square   = new SquarePaymentRestClient();
		$args     = [
			'idempotency_key' => '310445ef-bf1a-40ad-ad55-3dd1fdfe6ddb',
			'source_id'       => 'cnon:card-nonce-ok',
			'amount_money'    => [
				'amount'   => floatval( '10.55' ),
				'currency' => 'USD',//get_woocommerce_currency()
			],
		];
		$response = $square->post( 'payments', $args );
		var_dump( [ $square, $response, $args ] );
		die();
	}
}
