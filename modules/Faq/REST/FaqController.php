<?php

namespace YouSaidItCards\Modules\Faq\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\Faq\Models\Faq;
use YouSaidItCards\REST\ApiController;

class FaqController extends ApiController {
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
		register_rest_route( $this->namespace, 'customer-faqs', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$items = Faq::find();

		return $this->respondOK( [ 'items' => $items ] );
	}
}
