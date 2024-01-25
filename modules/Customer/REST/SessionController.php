<?php

namespace YouSaidItCards\Modules\Customer\REST;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\Sanitize;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Customer\Models\Session;
use YouSaidItCards\REST\ApiController;

class SessionController extends ApiController {

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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'me/session', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			]
		] );
		register_rest_route( $this->namespace, 'me/session/clear', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'clear_session' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			]
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$session = ( new Session )->find_by_user( $user->ID );

		return $this->respondOK( $session );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function clear_session( WP_REST_Request $request ): WP_REST_Response {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		( new Session )->delete_user_session( $user->ID );

		return $this->respondOK( new \ArrayObject() );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}
		$data = Sanitize::deep( $request->get_params() );
		if ( empty( $data ) ) {
			return $this->respondUnprocessableEntity( 'no_data_provided', 'No data provided in request body.' );
		}

		$session = ( new Session )->find_by_user( $user->ID );
		if ( ! $session instanceof Data ) {
			$session = new Data();
		}

		$session->set_data( $data );

		Session::create_or_update( $user->ID, $session->to_array() );

		return $this->respondOK( $session->to_array() );
	}
}
