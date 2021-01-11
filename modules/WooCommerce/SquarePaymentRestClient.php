<?php

namespace YouSaidItCards\Modules\WooCommerce;

use Stackonet\WP\Framework\Supports\RestClient;
use WC_Order;
use WP_Error;

class SquarePaymentRestClient extends RestClient {

	protected static $access_token = null;
	protected static $application_id = null;
	protected static $setting_read = false;
	protected static $is_sandbox = false;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->add_headers( 'Square-Version', '2020-12-16' );
		$this->add_headers( 'Content-Type', 'application/json' );
		$this->add_headers( 'Authorization', sprintf( '%s %s', 'Bearer', static::get_setting( 'access_token' ) ) );

		parent::__construct( 'https://connect.squareupsandbox.com/v2' );
	}

	/**
	 * @param string|null $key
	 *
	 * @return string
	 */
	public static function get_setting( string $key ): string {
		if ( ! static::$setting_read ) {
			$settings           = get_option( 'wc_square_settings', [] );
			static::$is_sandbox = isset( $settings['enable_sandbox'] ) && 'yes' == $settings['enable_sandbox'];
			if ( static::$is_sandbox ) {
				static::$access_token   = isset( $settings['sandbox_token'] ) ? $settings['sandbox_token'] : '';
				static::$application_id = isset( $settings['sandbox_application_id'] ) ? $settings['sandbox_application_id'] : '';
			} else {
				static::$access_token   = isset( $settings['sandbox_token'] ) ? $settings['sandbox_token'] : '';
				static::$application_id = isset( $settings['sandbox_application_id'] ) ? $settings['sandbox_application_id'] : '';
			}
			static::$setting_read = true;
		}

		$settings = [
			'access_token'   => static::$access_token,
			'application_id' => static::$application_id,
			'is_sandbox'     => static::$is_sandbox,
		];

		return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	}

	/**
	 * Create square payments
	 *
	 * @param WC_Order $order
	 * @param array $args
	 *
	 * @return array|WP_Error
	 */
	public function create_payments( WC_Order $order, array $args ) {
		$args = wp_parse_args( $args, [ 'payment_token' => '', 'source_id' => 'cnon:card-nonce-ok' ] );

		$params = [
			'idempotency_key'     => $args['payment_token'],
			'source_id'           => $args['source_id'],
			'amount_money'        => [
				'amount'   => floatval( $order->get_total() ) * 100,
				'currency' => get_woocommerce_currency()
			],
			'order_id'            => $order->get_id(),
			'reference_id'        => $order->get_id(),
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

		return $this->post( 'payments', wp_json_encode( $params ) );
	}
}
