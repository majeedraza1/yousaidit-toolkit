<?php

namespace YouSaidItCards\REST;

use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\ShipStation\Order;

class OrderController extends LegacyApiController {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/orders', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/carriers', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_carriers_items' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/dispatch', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/orders/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/orders/card-sizes', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_order_items' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Get order items
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_order_items( $request ) {
		$force = Validate::checked( $request->get_param( 'force' ) );
		$items = Order::get_order_items_by_card_sizes( $force );

		return $this->respondOK( [ 'items' => $items ] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$page  = $request->get_param( 'page' );
		$page  = ! is_numeric( $page ) ? 1 : intval( $page );
		$limit = $request->get_param( 'limit' );
		$limit = ! is_numeric( $limit ) ? 100 : intval( $limit );
		$force = (bool) $request->get_param( 'force' );

		$card_size     = $request->get_param( 'card_size' );
		$inner_message = $request->get_param( 'inner_message' );

		$orderStatus = $request->get_param( 'orderStatus' );
		$orderStatus = "shipped" == $orderStatus ? "shipped" : "awaiting_shipment";

		$_orders = Order::get_orders( [
			'pageSize'      => $limit,
			'page'          => $page,
			'force'         => $force,
			'card_size'     => $card_size,
			'inner_message' => $inner_message,
			'orderStatus'   => $orderStatus,
		] );

		$pagination = $this->getPaginationMetadata( [
			'currentPage' => $page,
			'limit'       => $limit,
			'totalCount'  => $_orders['total_items'],
		] );

		return $this->respondOK( [
			'items'       => $_orders['items'],
			'total_items' => $_orders['total_items'],
			'pagination'  => $pagination
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_carriers_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$carriers = Order::get_carriers();

		return $this->respondOK( [ 'items' => $carriers ] );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$order_id = (int) $request->get_param( 'id' );

		$order                     = Order::get_order( $order_id );
		$response                  = $order->to_array();
		$response['original_data'] = $order->get_original_data();

		return $this->respondOK( $response );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$open_statuses = [ 'awaiting_payment', 'awaiting_shipment', 'on_hold' ];

		$order_id = (int) $request->get_param( 'orderId' );
		$order    = Order::get_order( $order_id );

		if ( ! in_array( $order->get_order_status(), $open_statuses ) ) {
			return $this->respondUnprocessableEntity( 'cannot_update', 'Order cannot modified.' );
		}

		$updatedData = Order::mark_as_shipped( $request->get_params() );
		if ( is_wp_error( $updatedData ) ) {
			return $this->respondInternalServerError( $updatedData->get_error_code(), $updatedData->get_error_message() );
		}

		return $this->respondOK( $updatedData );
	}
}
