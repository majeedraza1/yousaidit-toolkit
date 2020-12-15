<?php

namespace YouSaidItCards\Modules\WooCommerce;

use YouSaidItCards\Modules\WooCommerce\REST\OrderController;
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

			ProductController::init();
			OrderController::init();
		}

		return self::$instance;
	}

	public function handle_custom_query_var( $query, $query_vars ) {
		$show_rude_card = isset( $query_vars['show_rude_card'] ) ? $query_vars['show_rude_card'] : 'yes';
		if ( 'no' == $show_rude_card ) {
			$query['meta_query'][] = [
				'relation' => 'OR',
				[ 'key' => '_is_rude_card', 'compare' => 'NOT EXISTS', ],
				[ 'key' => '_is_rude_card', 'value' => 'yes', 'compare' => '!=', ]
			];
		}

		return $query;
	}
}
