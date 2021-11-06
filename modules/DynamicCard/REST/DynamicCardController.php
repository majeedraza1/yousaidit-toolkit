<?php

namespace YouSaidItCards\Modules\DynamicCard\REST;

use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

class DynamicCardController extends ApiController {
	/**
	 * @var self
	 */
	private static $instance;

	/**
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
		register_rest_route( $this->namespace, '/dynamic-cards/(?P<product_id>\d+)', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_item' ], ],
		] );
	}

	public function get_item( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );

		return $this->respondOK( $request->get_params() );
	}
}
