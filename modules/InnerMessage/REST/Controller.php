<?php

namespace YouSaidItCards\Modules\InnerMessage\REST;

use WP_REST_Server;
use YouSaidItCards\REST\ApiController;
use YouSaidItCards\Utils;

/**
 * Controller
 */
class Controller extends ApiController {
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
		register_rest_route( $this->namespace, 'admin/inner-message', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_item' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$order_id = (int) $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return $this->respondNotFound();
		}
		$item_id       = (int) $request->get_param( 'item_id' );
		$meta_key      = $request->get_param( 'meta_key' );
		$current_value = wc_get_order_item_meta( $item_id, $meta_key );
		if ( ! is_array( $current_value ) ) {
			return $this->respondNotFound();
		}

		$original_value = wc_get_order_item_meta( $item_id, $meta_key . '_original' );
		if ( empty( $original_value ) && ! empty( $current_value ) ) {
			wc_add_order_item_meta( $item_id, $meta_key . '_original', $current_value );
		}

		$meta_value = $request->get_param( 'meta_value' );
		$meta_value = is_array( $meta_value ) ? $meta_value : [];

		$content                  = $meta_value['content'] ?? '';
		$content                  = stripslashes( wp_filter_post_kses( $content ) );
		$current_value['content'] = Utils::sanitize_inner_message_text( $content );
		wc_update_order_item_meta( $item_id, $meta_key, $current_value );

		return $this->respondOK();
	}
}
