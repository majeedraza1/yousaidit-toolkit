<?php

namespace YouSaidItCards\Modules\DynamicCard\REST;

use ArrayObject;
use WC_Product;
use WP_REST_Server;
use YouSaidItCards\Assets;
use YouSaidItCards\Modules\Designers\DynamicCard;
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
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	public function get_item( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		$product    = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return $this->respondNotFound( null, "Product is not found." );
		}

		$card_type = $product->get_meta( '_card_type', true );
		if ( 'dynamic' !== $card_type ) {
			return $this->respondNotFound( null, 'Product is not dynamic type.' );
		}

		$payload = $product->get_meta( '_dynamic_card_payload', true );
		$image = wp_get_attachment_image_src( $product->get_image_id() );
		$data  = [
			'payload'        => DynamicCard::sanitize_card_payload( $payload ),
			'product_thumb'  => is_array( $image ) ? $image[0] : '',
			'placeholder_im' => Assets::get_static_asset_url( 'placeholder--inner-message.jpg' ),
		];

		return $this->respondOK( $data );
	}

	public static function sanitize_card_payload_for_response(): array {
		$sanitized = [];

		return $sanitized;
	}

	public static function sanitize_dynamic_card_layer( array $payload ): array {
		$sanitized = [];

		return $sanitized;
	}
}
