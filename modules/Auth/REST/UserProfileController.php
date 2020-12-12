<?php

namespace YouSaidItCards\Modules\Auth\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\Auth\Auth;
use YouSaidItCards\REST\ApiController;

class UserProfileController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

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
		register_rest_route( $this->namespace, 'me', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_item' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		return $this->respondOK( [ 'user' => Auth::prepare_user_for_response( $current_user ) ] );
	}
}
