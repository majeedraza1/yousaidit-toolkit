<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\WooCommerce\WcRestClient;
use YouSaidItCards\REST\ApiController;

class OrderController extends ApiController {

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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'me/orders', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
				'args'                => $this->get_collection_params(),
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			]
		] );
		register_rest_route( $this->namespace, 'me/orders/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_item' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
			'args'                => $this->get_collection_params(),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$rest_client = new WcRestClient();
		$args        = [
			'customer' => get_current_user_id(),
			'page'     => (int) $request->get_param( 'page' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
			'search'   => $request->get_param( 'search' ),
		];
		$orders      = $rest_client->list_orders( $args );
		if ( is_wp_error( $orders ) ) {
			return $this->respondWithError( $orders );
		}

		return $this->respondOK( [ 'items' => $orders ] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$rest_client = new WcRestClient();
		$order       = $rest_client->create_order( $request->get_params() );
		if ( is_wp_error( $order ) ) {
			return $this->respondWithError( $order );
		}

		return $this->respondOK( $order );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		$rest_client = new WcRestClient();
		$order       = $rest_client->list_order( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $order ) ) {
			return $this->respondWithError( $order );
		}

		return $this->respondOK( $order );
	}
}
