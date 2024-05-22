<?php

namespace YouSaidItCards\Modules\Designers;

use PayPalHttp\HttpException;
use PayPalHttp\HttpResponse;
use PaypalPayoutsSDK\Core\PayPalHttpClient;
use PaypalPayoutsSDK\Core\ProductionEnvironment;
use PaypalPayoutsSDK\Core\SandboxEnvironment;
use PaypalPayoutsSDK\Payouts\PayoutsGetRequest;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\Validate;
use stdClass;
use WP_Error;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\Modules\Designers\Models\Payment;
use YouSaidItCards\Modules\Designers\Models\PaymentItem;

class PaypalPayoutsUtils {

	/**
	 * @var PayPalHttpClient
	 */
	protected static $client;

	/**
	 * @var bool
	 */
	protected static $has_config;

	/**
	 * @return bool
	 */
	public static function is_configured_properly(): bool {
		if ( ! is_bool( static::$has_config ) ) {
			static::get_client();
		}

		return static::$has_config;
	}

	/**
	 * Get paypal http client
	 *
	 * @return PayPalHttpClient
	 */
	public static function get_client(): PayPalHttpClient {
		if ( ! self::$client instanceof PayPalHttpClient ) {
			$options    = (array) get_option( '_stackonet_toolkit', [] );
			$is_sandbox = defined( 'PAYPAL_SANDBOX_MODE' ) ? PAYPAL_SANDBOX_MODE === true :
				( isset( $options['paypal_sandbox_mode'] ) && Validate::checked( $options['paypal_sandbox_mode'] ) );
			if ( $is_sandbox ) {
				if ( defined( 'PAYPAL_SANDBOX_CLIENT_ID' ) && defined( 'PAYPAL_SANDBOX_CLIENT_SECRET' ) ) {
					$client_id     = PAYPAL_SANDBOX_CLIENT_ID;
					$client_secret = PAYPAL_SANDBOX_CLIENT_SECRET;
				} else {
					$client_id     = isset( $options['paypal_sandbox_client_id'] ) ? $options['paypal_sandbox_client_id'] : '';
					$client_secret = isset( $options['paypal_sandbox_client_secret'] ) ? $options['paypal_sandbox_client_secret'] : '';
				}
			} else {
				if ( defined( 'PAYPAL_CLIENT_ID' ) && defined( 'PAYPAL_CLIENT_SECRET' ) ) {
					$client_id     = PAYPAL_CLIENT_ID;
					$client_secret = PAYPAL_CLIENT_SECRET;
				} else {
					$client_id     = isset( $options['paypal_client_id'] ) ? $options['paypal_client_id'] : '';
					$client_secret = isset( $options['paypal_client_secret'] ) ? $options['paypal_client_secret'] : '';
				}
			}

			static::$has_config = ! empty( $client_id ) && ! empty( $client_secret );

			if ( $is_sandbox ) {
				$environment = new SandboxEnvironment( $client_id, $client_secret );
			} else {
				$environment = new ProductionEnvironment( $client_id, $client_secret );
			}
			self::$client = new PayPalHttpClient( $environment );
		}

		return self::$client;
	}

	/**
	 * @param array $data
	 *
	 * @return HttpResponse|WP_Error
	 */
	public static function create_payout( array $data ) {
		if ( ! static::is_configured_properly() ) {
			return new WP_Error( 'paypal_not_configured', 'PayPal API is not configured.' );
		}
		$body          = static::format_payout_data( $data );
		$request       = new PayoutsPostRequest();
		$request->body = $body;

		try {
			return static::get_client()->execute( $request );
		} catch ( HttpException $exception ) {
			Logger::log( $exception );
			$msg = json_decode( $exception->getMessage(), true );

			$error = new WP_Error( 'paypal_http_error', $msg['message'], $msg );
			$error->add_data( $body, 'request_body' );

			return $error;
		}
	}

	/**
	 * Get batch status
	 *
	 * @param string $payout_batch_id
	 *
	 * @return HttpResponse|WP_Error
	 */
	public static function get_payout_batch_status( string $payout_batch_id ) {
		if ( ! static::is_configured_properly() ) {
			return new WP_Error( 'paypal_not_configured', 'PayPal API is not configured.' );
		}

		$request = new PayoutsGetRequest( $payout_batch_id );
		try {
			return static::get_client()->execute( $request );
		} catch ( HttpException $exception ) {
			Logger::log( $exception );
			$msg = json_decode( $exception->getMessage(), true );

			return new WP_Error( 'paypal_http_error', $msg['message'], $msg );
		}
	}

	/**
	 * Format payout data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function format_payout_data( array $data ): array {
		$payment_id    = isset( $data['payment_id'] ) ? $data['payment_id'] : uniqid();
		$email_subject = isset( $data['email_subject'] ) ? $data['email_subject'] : "You have a Payout!";
		$email_message = isset( $data['email_message'] ) ? $data['email_message'] :
			"You have received a payout! Thanks for using our service!";

		$body = [
			"sender_batch_header" => [
				"sender_batch_id" => $payment_id,
				"email_subject"   => $email_subject,
				"email_message"   => $email_message
			],
			"items"               => [],
		];

		$items = isset( $data['items'] ) ? $data['items'] : [];
		foreach ( $items as $item ) {
			$_item = static::format_payout_item( $item );
			if ( count( $_item ) ) {
				$body["items"][] = $_item;
			}
		}

		return $body;
	}

	/**
	 * Format payout item
	 *
	 * @param array|PaymentItem $item
	 *
	 * @return array
	 */
	public static function format_payout_item( array $item ): array {
		if ( ! Validate::email( $item['paypal_email'] ) ) {
			return [];
		}
		if ( ! Validate::number( $item['total_commissions'] ) ) {
			return [];
		}

		return [
			"recipient_type" => "EMAIL",
			"receiver"       => $item['paypal_email'],
			"amount"         => [
				"value"    => (string) $item['total_commissions'],
				"currency" => $item['currency'],
			],
			"note"           => $item['note'],
			"sender_item_id" => $item['item_id'],
		];
	}

	/**
	 * Pay unpaid commissions
	 *
	 * @param int $min_amount
	 * @param string|array $order_status
	 *
	 * @return string|WP_Error
	 */
	public static function pay_unpaid_commissions( $min_amount = 0, $order_status = 'completed' ) {
		// Get unpaid commissions
		$commission = ( new DesignerCommission )->get_unpaid_commission( [ 'order_status' => $order_status ] );

		// Save payment info in database for using in payout
		$data = Payment::create_payout( $commission, [], $min_amount );

		if ( ! $data['payment_id'] ) {
			return new WP_Error( 'no_item_to_pay', 'No designer to be payable yet.' );
		}

		// Attempt to pay
		$payout = static::create_payout( $data );

		if ( is_wp_error( $payout ) ) {
			// If any error, delete payment information from database
			( new Payment )->delete( $data['payment_id'] );

			return $payout;
		}

		$batch_header    = $payout->result->batch_header;
		$payout_batch_id = $batch_header->payout_batch_id;

		// Mark commissions as paid
		( new DesignerCommission )->mark_commissions_paid( $payout_batch_id, $data['commission_ids'] );

		// Sync info
		static::sync_batch_items( $payout_batch_id );

		return $data['payment_id'];
	}

	/**
	 * Sync batch items
	 *
	 * @param string $payout_batch_id
	 *
	 * @return bool|HttpResponse|WP_Error
	 */
	public static function sync_batch_items( string $payout_batch_id ) {
		$response = static::get_payout_batch_status( $payout_batch_id );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$batch_header = $response->result->batch_header;

		// Update payment data with batch id
		( new Payment )->update( [
			'payment_id'       => (int) $batch_header->sender_batch_header->sender_batch_id,
			'payment_batch_id' => $payout_batch_id,
			'payment_status'   => $batch_header->batch_status,
			'currency'         => $batch_header->amount->currency,
			'amount'           => (float) $batch_header->amount->value,
		] );

		$items = $response->result->items;

		foreach ( $items as $item ) {
			( new PaymentItem )->update( static::format_payout_item_to_payment_item( $item ) );
		}

		return true;
	}

	/**
	 * Format payout item to payment item
	 *
	 * @param stdClass $data
	 *
	 * @return array
	 */
	public static function format_payout_item_to_payment_item( stdClass $data ): array {
		$item = $data->payout_item;

		return [
			'item_id'            => (int) $item->sender_item_id,
			'payout_item_id'     => $data->payout_item_id,
			'transaction_status' => $data->transaction_status,
			'currency'           => $item->amount->currency,
			'total_commissions'  => (float) $item->amount->value,
			'error_message'      => $data->errors->message ?? '',
		];
	}
}
