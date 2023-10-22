<?php

namespace YouSaidItCards\Modules\TreePlanting;

/**
 * TreePlantingManager class
 */
class TreePlantingManager {
	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'woocommerce_new_order', [ self::$instance, 'woocommerce_new_order' ] );
			add_action( 'admin_init', [ TreePlanting::class, 'create_tables' ] );
			BackgroundPurchaseTree::init();
		}

		return self::$instance;
	}

	/**
	 * @param  int  $order_id
	 *
	 * @return void
	 */
	public function woocommerce_new_order( int $order_id ) {
		$purchase_orders_count = Setting::purchase_tree_after_total_orders();

		$orders_ids   = Setting::get_cumulative_orders_ids();
		$orders_ids[] = $order_id;

		if ( count( $orders_ids ) >= $purchase_orders_count ) {
			$id = TreePlanting::create( [
				'orders_ids' => $orders_ids,
			] );
			if ( $id ) {
				$orders_ids = [];
			}
		}
		Setting::update_cumulative_orders_ids( $orders_ids );
	}
}
