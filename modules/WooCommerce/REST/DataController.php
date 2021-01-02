<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\WooCommerce\Utils;
use YouSaidItCards\Modules\WooCommerce\WcRestClient;
use YouSaidItCards\REST\ApiController;

class DataController extends ApiController {
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
		register_rest_route( $this->namespace, 'data', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$rest_client                 = new WcRestClient();
		$response                    = $rest_client->list_general_data();
		$response['card_sizes']      = Utils::get_formatted_size_attribute();
		$response['currency_symbol'] = get_woocommerce_currency_symbol();

		return $this->respondOK( $response );
	}
}
