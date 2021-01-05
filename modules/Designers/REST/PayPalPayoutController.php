<?php

namespace YouSaidItCards\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\Modules\Designers\Models\Payment;
use YouSaidItCards\Modules\Designers\Models\PaymentItem;
use YouSaidItCards\Modules\Designers\PayPal;

defined( 'ABSPATH' ) || exit;

class PayPalPayoutController extends ApiController {
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

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/paypal-payouts', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
			],
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'create_item' ],
			],
		] );
		register_rest_route( $this->namespace, '/paypal-payouts/(?P<id>\d+)', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
			],
		] );
		register_rest_route( $this->namespace, '/paypal-payouts/(?P<id>\d+)/sync', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'sync_item' ],
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondOK();
		}

		$items = ( new Payment() )->find();

		$counts   = DesignerCommission::count_card_for_payout();
		$response = [ 'items' => $items, 'count_cards' => $counts ];

		return $this->respondOK( $response );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondOK();
		}

		$min_amount = (float) $request->get_param( 'min_amount' );

		if ( $min_amount < 1 ) {
			$min_amount = 5.00;
		}

		$payout = ( new PayPal() )->pay_unpaid_commissions( $min_amount );
		if ( $payout instanceof WP_Error ) {
			return $this->respondUnprocessableEntity( $payout->get_error_code(), $payout->get_error_message() );
		}

		return $this->respondCreated();
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondOK();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new Payment() )->find_by_id( $id );

		if ( ! $item instanceof Payment ) {
			return $this->respondNotFound();
		}

		return $this->respondOK( [ 'payment' => $item, 'items' => $item->get_payment_items() ] );

	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function sync_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondOK();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new Payment() )->find_by_id( $id );

		if ( ! $item instanceof Payment ) {
			return $this->respondNotFound();
		}

		$info = ( new PayPal() )->get_batch_status( $item->get_payment_batch_id() );
		if ( is_wp_error( $info ) ) {
			return $this->respondInternalServerError( $info->get_error_code(), $info->get_error_message() );
		}

		$batch_status = $info->getBatchHeader()->getBatchStatus();
		$currency     = $info->getBatchHeader()->getAmount()->getCurrency();
		$amount       = $info->getBatchHeader()->getAmount()->getValue();

		// Update Payment data
		( new Payment() )->update( [
			'payment_id'     => $id,
			'payment_status' => $batch_status,
			'currency'       => $currency,
			'amount'         => floatval( $amount ),
		] );

		// Update Payment item
		$items = $info->getItems();
		foreach ( $items as $item ) {
			( new PaymentItem )->update( PaymentItem::payoutItemToPaymentItem( $item ) );
		}

		return $this->respondOK();
	}
}