<?php

namespace YouSaidItCards\Modules\WooCommerce;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\RestClient;
use WC_Order;

class SquarePaymentRestClient extends RestClient {

	public function __construct() {
		$settings       = get_option( 'wc_square_settings', [] );
		$enable_sandbox = isset( $settings['enable_sandbox'] ) && 'yes' == $settings['enable_sandbox'];
		if ( $enable_sandbox ) {
			$token          = isset( $settings['sandbox_token'] ) ? $settings['sandbox_token'] : '';
			$application_id = isset( $settings['sandbox_application_id'] ) ? $settings['sandbox_application_id'] : '';
		} else {
			$token          = isset( $settings['sandbox_token'] ) ? $settings['sandbox_token'] : '';
			$application_id = isset( $settings['sandbox_application_id'] ) ? $settings['sandbox_application_id'] : '';
		}

		$api_base_url = 'https://connect.squareupsandbox.com/v2';

		$this->add_headers( 'Square-Version', '2020-12-16' );
		$this->add_headers( 'Content-Type', 'application/json' );
		$this->add_headers( 'Authorization', sprintf( '%s %s', 'Bearer', $token ) );

		parent::__construct( $api_base_url );
	}

	/**
	 * Create square payments
	 *
	 * @param WC_Order $order
	 * @param array $args
	 *
	 * @return array|\WP_Error
	 */
	public function create_payments( WC_Order $order, array $args ) {
		$args = wp_parse_args( $args, [ 'payment_token' => '' ] );

		$order->get_total();

		$amount_money = [
			'amount'   => floatval( $order->get_total() ) * 100,
			'currency' => get_woocommerce_currency()
		];

		$params = [
			'idempotency_key'     => $args['payment_token'],
			'source_id'           => 'cnon:card-nonce-ok',
			'amount_money'        => $amount_money,
			'order_id'            => $order->get_id(),
			'customer_id'         => $order->get_customer_id(),
			'buyer_email_address' => $order->get_billing_email(),
			'billing_address'     => [
				'first_name'     => $order->get_billing_first_name(),
				'last_name'      => $order->get_billing_last_name(),
				'organization'   => $order->get_billing_company(),
				'address_line_1' => $order->get_billing_address_1(),
				'address_line_2' => $order->get_billing_address_2(),
				'country'        => $order->get_billing_country(),
				'postal_code'    => $order->get_billing_postcode(),
			],
			'shipping_address'    => [
				'first_name'     => $order->get_shipping_first_name(),
				'last_name'      => $order->get_shipping_last_name(),
				'organization'   => $order->get_shipping_company(),
				'address_line_1' => $order->get_shipping_address_1(),
				'address_line_2' => $order->get_shipping_address_2(),
				'country'        => $order->get_shipping_country(),
				'postal_code'    => $order->get_shipping_postcode(),
			],
		];

		$response = $this->post( 'payments', wp_json_encode( $params ) );



		return $response;
	}
}
