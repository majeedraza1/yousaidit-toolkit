<?php

namespace YouSaidItCards\Modules\TradeSite\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\TradeSite\YousaiditTradeRestClient;
use YouSaidItCards\REST\LegacyApiController;

class CardToProduct extends LegacyApiController {

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
		register_rest_route( $this->namespace, '/trade-site/(?P<id>\d+)/create-product', [
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_item' ] ],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new DesignerCard() )->find_by_id( $id );

		if ( ! $item instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		$client = new YousaiditTradeRestClient;
		$id     = $client->create_product( $item->to_array() );
		if ( is_wp_error( $id ) ) {
			return $this->respond( $id->get_error_data(), 422 );
		}

		return $this->respondCreated( $id );
	}
}
