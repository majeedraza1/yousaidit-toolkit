<?php

namespace Yousaidit\Modules\Designers;

use Exception;
use PayPal\Api\Currency;
use PayPal\Api\Payout;
use PayPal\Api\PayoutBatch;
use PayPal\Api\PayoutItem;
use PayPal\Api\PayoutSenderBatchHeader;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use WP_Error;
use Yousaidit\Modules\Designers\Models\DesignerCommission;
use Yousaidit\Modules\Designers\Models\Payment;
use Yousaidit\Modules\Designers\Models\PaymentItem;

class PayPal {
	/**
	 * @var ApiContext
	 */
	protected $api_context;

	/**
	 * PayPal constructor.
	 */
	public function __construct() {
		if ( defined( 'PAYPAL_CLIENT_ID' ) && defined( 'PAYPAL_CLIENT_SECRET' ) ) {
			$client_id     = PAYPAL_CLIENT_ID;
			$client_secret = PAYPAL_CLIENT_SECRET;
		} else {
			$options       = get_option( 'yousaiditcard_designers_settings', [] );
			$client_id     = isset( $options['paypal_client_id'] ) ? $options['paypal_client_id'] : '';
			$client_secret = isset( $options['paypal_client_secret'] ) ? $options['paypal_client_secret'] : '';
		}

		$this->api_context = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
	}

	/**
	 * Pay unpaid commissions
	 *
	 * @param int $min_amount
	 *
	 * @return string|WP_Error
	 */
	public function pay_unpaid_commissions( $min_amount = 0 ) {
		// Get unpaid commissions
		$commission = ( new DesignerCommission )->get_unpaid_commission();

		// Save payment info in database for using in payout
		$data = Payment::create_payout( $commission, [], $min_amount );

		// Attempt to pay
		$payout = ( new static )->payout( $data );

		if ( is_wp_error( $payout ) ) {
			// If any error, delete payment information from database
			( new Payment )->delete( $data['payment_id'] );

			return $payout;
		}

		$batchHeader = $payout->getBatchHeader();

		$created_at = new \DateTime( $batchHeader->getTimeCreated() );
		$created_at = $created_at->format( "Y-m-d H:i:s" );

		// Update payment data with batch id
		( new Payment )->update( [
			'payment_id'       => $data['payment_id'],
			'payment_batch_id' => $batchHeader->getPayoutBatchId(),
			'payment_status'   => $batchHeader->getBatchStatus(),
			'created_at'       => $created_at,
		] );

		// Mark commissions as paid
		( new DesignerCommission )->mark_commissions_paid( $batchHeader->getPayoutBatchId(), $data['commission_ids'] );

		// Sync info
		static::sync_batch_items( $batchHeader->getPayoutBatchId() );

		return $data['payment_id'];
	}

	/**
	 * Payout
	 *
	 * @param array $data
	 *
	 * @return PayoutBatch|WP_Error
	 */
	public function payout( array $data ) {
		$payment_id    = isset( $data['payment_id'] ) ? $data['payment_id'] : uniqid();
		$items         = isset( $data['items'] ) ? $data['items'] : [];
		$email_subject = isset( $data['email_subject'] ) ? $data['email_subject'] : "You have a Payout!";

		$senderBatchHeader = new PayoutSenderBatchHeader();
		$senderBatchHeader->setSenderBatchId( $payment_id );
		$senderBatchHeader->setEmailSubject( $email_subject );

		$payouts = new Payout();
		$payouts->setSenderBatchHeader( $senderBatchHeader );
		foreach ( $items as $item ) {
			$payouts->addItem( $this->add_payout_items( $item ) );
		}

		try {
			$output = $payouts->create( null, $this->api_context );
		} catch ( Exception $exception ) {
			return new WP_Error( 'payout_error', $exception->getMessage() );
		}

		return $output;
	}

	/**
	 * Add payment items
	 *
	 * @param array $item
	 *
	 * @return PayoutItem
	 */
	protected function add_payout_items( array $item ) {
		$currency = new Currency;
		$currency->setCurrency( $item['currency'] );
		$currency->setValue( $item['total_commissions'] );

		$payout_item = new PayoutItem();
		$payout_item->setRecipientType( 'Email' );
		$payout_item->setNote( $item['note'] );
		$payout_item->setReceiver( $item['paypal_email'] );
		$payout_item->setSenderItemId( $item['item_id'] );
		$payout_item->setAmount( $currency );

		return $payout_item;
	}

	/**
	 * Get batch status
	 *
	 * @param string $batch_id
	 *
	 * @return PayoutBatch|WP_Error
	 */
	public function get_batch_status( $batch_id ) {
		try {
			$output = Payout::get( $batch_id, $this->api_context );
		} catch ( Exception $exception ) {
			return new WP_Error( 'payout_error', $exception->getMessage() );
		}

		return $output;
	}

	/**
	 * @param $batch_id
	 *
	 * @return bool|PayoutBatch|WP_Error
	 */
	public static function sync_batch_items( $batch_id ) {
		$batch = ( new static )->get_batch_status( $batch_id );
		if ( is_wp_error( $batch ) ) {
			return $batch;
		}

		$items = $batch->getItems();
		foreach ( $items as $item ) {
			( new PaymentItem )->update( PaymentItem::payoutItemToPaymentItem( $item ) );
		}

		return true;
	}
}
