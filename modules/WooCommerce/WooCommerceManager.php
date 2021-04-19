<?php

namespace YouSaidItCards\Modules\WooCommerce;

use WC_Order_Item_Product;
use YouSaidItCards\Modules\WooCommerce\REST\DataController;
use YouSaidItCards\Modules\WooCommerce\REST\OrderController;
use YouSaidItCards\Modules\WooCommerce\REST\OrderPostcardController;
use YouSaidItCards\Modules\WooCommerce\REST\ProductController;

class WooCommerceManager {
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

			add_filter( 'woocommerce_product_data_store_cpt_get_products_query',
				[ self::$instance, 'handle_custom_query_var' ], 10, 2 );

			// Step 5: Display on Order detail page and (Order received / Thank you page) and Order Emails
			add_filter( 'woocommerce_order_item_get_formatted_meta_data',
				[ self::$instance, 'order_item_get_formatted_meta_data' ], 10, 2 );

			// Step 6: hide Default display of our metabox
			add_filter( 'woocommerce_hidden_order_itemmeta', [ self::$instance, 'hidden_order_itemmeta' ] );

			ProductController::init();
			OrderController::init();
			OrderPostcardController::init();
			DataController::init();
		}

		return self::$instance;
	}

	public function handle_custom_query_var( $query, $query_vars ): array {
		$show_rude_card = $query_vars['show_rude_card'] ?? 'yes';
		if ( 'no' == $show_rude_card ) {
			$query['meta_query'][] = [
				'relation' => 'OR',
				[ 'key' => '_is_rude_card', 'compare' => 'NOT EXISTS', ],
				[ 'key' => '_is_rude_card', 'value' => 'yes', 'compare' => '!=', ]
			];
		}

		return $query;
	}

	/**
	 * Display on Order detail page and (Order received / Thank you page)
	 *
	 * @param array $formatted_meta
	 * @param WC_Order_Item_Product $order_item
	 *
	 * @return mixed
	 */
	public function order_item_get_formatted_meta_data( array $formatted_meta, $order_item ) {
		$data = $order_item->get_meta( '_postcard_pdf_id', true );
		if ( ! empty( $data ) ) {
			$formatted_meta[] = (object) [
				'display_key'   => 'Postcard Ref',
				'display_value' => $data,
			];
		}


		return $formatted_meta;
	}

	/**
	 * Add our meta key to hidden order item meta list to hide default display
	 *
	 * @param array $keys
	 *
	 * @return array
	 */
	public function hidden_order_itemmeta( array $keys ): array {
		$keys[] = '_postcard_pdf_id';
		$keys[] = '_card_designer_commission_id';

		return $keys;
	}
}
