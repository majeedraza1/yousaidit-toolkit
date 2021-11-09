<?php

namespace YouSaidItCards\Modules\DynamicCard;

use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\DynamicCard\REST\DynamicCardController;

class DynamicCardManager {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			DynamicCardController::init();

			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ] );
			// Step 4: Add Custom Details as Order Line Items
			add_action( 'woocommerce_checkout_create_order_line_item',
				[ self::$instance, 'create_order_line_item' ], 10, 4 );

			add_action( 'wp_footer', [ self::$instance, 'add_editor' ], 5 );
		}

		return self::$instance;
	}

	public function add_editor() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		if ( 'dynamic' == $product->get_meta( '_card_type', true ) ) {
			$card_size = $product->get_meta( '_card_size', true );
			$html      = sprintf( '<div id="dynamic-card-container" data-card-size="%s" data-product-id="%s">',
				$card_size, $product->get_id() );
			$html      .= '<div id="dynamic-card"></div>';
			$html      .= '</div>';
			echo $html;
		}
	}

	/**
	 * Add custom data to cart
	 *
	 * @param array $cart_item_data
	 *
	 * @return array
	 */
	public function add_cart_item_data( array $cart_item_data ): array {
		if ( isset( $_REQUEST['_dynamic_card'] ) ) {
			$cart_item_data['_dynamic_card'] = $_REQUEST['_dynamic_card'];
		}

		return $cart_item_data;
	}

	/**
	 * Add custom data to order line item
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string $cart_item_key
	 * @param array $values
	 * @param WC_Order $order
	 */
	public function create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( array_key_exists( '_dynamic_card', $values ) ) {
			$data = is_array( $values['_dynamic_card'] ) ? $values['_dynamic_card'] : [];
			$item->add_meta_data( '_dynamic_card', self::sanitize_dynamic_card( $data ) );
		}
	}

	/**
	 * Sanitize dynamic card
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function sanitize_dynamic_card( array $data ): array {
		$sanitized_data = [];
		foreach ( $data as $index => $value ) {
			$sanitized_data[ $index ]['value'] = is_numeric( $value['value'] ) ? intval( $value['value'] ) :
				sanitize_text_field( $value['value'] );
		}

		return $sanitized_data;
	}
}
