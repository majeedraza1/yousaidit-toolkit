<?php

namespace YouSaidItCards\Modules\DynamicCard;

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
			echo '<div id="dynamic-card-container" data-product-id="' . $product->get_id() . '"><div id="dynamic-card"></div></div>';
		}
	}
}
