<?php

namespace YouSaidItCards\Modules\Customer\REST;

use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

class MyAccountController extends ApiController {
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
		register_rest_route( $this->namespace, 'me/payment-methods', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
			'args'                => $this->get_collection_params(),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		if ( ! $this->is_logged_in() ) {
			return $this->respondUnauthorized();
		}
		$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );

		return $this->respondOK( [ 'items' => $saved_methods ] );
	}
}
