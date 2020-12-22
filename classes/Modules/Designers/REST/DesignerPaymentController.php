<?php

namespace Yousaidit\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yousaidit\Modules\Designers\Models\PaymentItem;

defined( 'ABSPATH' ) || exit;

class DesignerPaymentController extends ApiController {

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
		register_rest_route( $this->namespace, '/designer-payments', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
			],
		] );
		register_rest_route( $this->namespace, '/designer-payments/(?P<id>\d+)', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
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
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$items = ( new PaymentItem )->find_by_designer_id( $current_user->ID );

		return $this->respondOK( [ 'items' => $items ] );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$id = $request->get_param( 'id' );

		return $this->respondOK();
	}
}
