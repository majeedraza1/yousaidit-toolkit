<?php

namespace YouSaidItCards\Modules\Designers;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\ShipStationApi;

class BackgroundCommissionSync extends BackgroundProcess {

	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'save_and_dispatch' ] );
		}

		return self::$instance;
	}

	public function save_and_dispatch() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$order_id      = isset( $item['order_id'] ) ? intval( $item['order_id'] ) : 0;
		$order_item_id = isset( $item['order_item_id'] ) ? intval( $item['order_item_id'] ) : 0;
		$commission    = DesignerCommission::find_for_order( $order_id, $order_item_id );
		if ( $commission instanceof DesignerCommission ) {
			$commission->set( 'order_status', $item['order_status'] );
			$commission->update();
		} else {
			( new DesignerCommission )->create( $item );
		}

		return false;
	}

	/**
	 * Sync commission
	 */
	public static function sync_orders() {
		$last_sync    = get_option( 'last_commission_sync_time' );
		$one_hour_ago = time() - HOUR_IN_SECONDS;
		// Only sync once in one hour
		if ( is_numeric( $last_sync ) && $last_sync > $one_hour_ago ) {
			return;
		}

		$items = ShipStationApi::init()->get_orders();
		if ( ! isset( $items['orders'] ) ) {
			return;
		}
		foreach ( $items['orders'] as $order ) {
			$_order = new Order( $order );
			foreach ( $_order->get_order_items() as $order_item ) {
				if ( ! $order_item->has_designer_commission() ) {
					continue;
				}
				$data = [
					'card_id'          => $order_item->get_card_id(),
					'designer_id'      => $order_item->get_designer_id(),
					'order_id'         => $order_item->get_ship_station_order_id(),
					'order_item_id'    => (int) $order_item->get_prop( 'orderItemId', 0 ),
					'order_quantity'   => $order_item->get_quantity(),
					'item_commission'  => $order_item->get_designer_commission(),
					'total_commission' => $order_item->get_designer_commission() * $order_item->get_quantity(),
					'card_size'        => $order_item->get_card_size(),
					'order_status'     => $_order->get_order_status(),
					'payment_status'   => 'unpaid',
					'created_via'      => 'shipstation-api',
				];
				self::init()->push_to_queue( $data );
			}
		}

		update_option( 'last_commission_sync_time', time(), false );
	}
}
