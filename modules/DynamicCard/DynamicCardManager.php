<?php

namespace YouSaidItCards\Modules\DynamicCard;

use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\DynamicCard\Models\CardPayload;
use YouSaidItCards\Modules\DynamicCard\REST\DynamicCardController;
use YouSaidItCards\Modules\DynamicCard\REST\UserMediaController;

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
			UserMediaController::init();
			BackgroundDynamicPdfGenerator::init();

			add_action( 'wp_ajax_generate_dynamic_card_pdf', [ self::$instance, 'generate_dynamic_card_pdf' ] );
			add_action( 'wp_ajax_nopriv_generate_dynamic_card_pdf', [ self::$instance, 'generate_dynamic_card_pdf' ] );

			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ] );
			// Step 4: Add Custom Details as Order Line Items
			add_action( 'woocommerce_checkout_create_order_line_item',
				[ self::$instance, 'create_order_line_item' ], 10, 4 );

			add_action( 'wp_footer', [ self::$instance, 'add_editor' ], 5 );

			add_filter( 'woocommerce_cart_item_thumbnail', [ self::$instance, 'cart_item_thumbnail' ], 10, 2 );
			add_action( 'wp_ajax_dynamic_card_test', [ self::$instance, 'dynamic_card_test' ] );
		}

		return self::$instance;
	}

	public function dynamic_card_test() {
		$product        = wc_get_product( 37553 );
		$modified_value = [
			[ 'value' => 'Hello' ],
			[ 'value' => '' ],
			[ 'value' => '37535' ],
		];
		$payload        = new CardPayload(
			$product->get_meta( '_dynamic_card_payload', true ),
			$modified_value
		);
		var_dump( $payload );
		die;
	}

	public function cart_item_thumbnail( $image_string, $cart_item ) {
		if ( isset( $cart_item['_dynamic_card'] ) && is_array( $cart_item['_dynamic_card'] ) ) {
			/** @var \WC_Product $product */
			$product = $cart_item['data'];
			$payload = $product->get_meta( '_dynamic_card_payload', true );
			$payload = new CardPayload( $payload, $cart_item['_dynamic_card'] );
			/* @TODO change card size for dynamic value */
			$image_string = "<div style='width: 150px;height:150px'><dynamic-card-canvas
			options='" . wp_json_encode( $payload->get_data() ) . "'
			card-width-mm='150'
			card-height-mm='150'
			element-width-mm='40'
			element-height-mm='40'
			></dynamic-card-canvas></div>";
		}

		return $image_string;
	}

	public function generate_dynamic_card_pdf() {
		$order_id      = $_REQUEST['order_id'] ?? 0;
		$order_item_id = $_REQUEST['order_item_id'] ?? 0;
		$filepath      = BackgroundDynamicPdfGenerator::generate_for_order_item( intval( $order_id ), intval( $order_item_id ) );
		if ( is_wp_error( $filepath ) ) {
			wp_send_json_error( $filepath );
		}
		wp_send_json_success( [ 'path' => $filepath ] );
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
			BackgroundDynamicPdfGenerator::init()->push_to_queue( [
				'order_id'      => $order->get_id(),
				'order_item_id' => $item->get_id()
			] );
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
