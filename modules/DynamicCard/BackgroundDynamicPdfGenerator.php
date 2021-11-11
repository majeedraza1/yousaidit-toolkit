<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\UploadedFile;
use WC_Order_Item_Product;
use WP_Error;
use YouSaidItCards\Modules\DynamicCard\Models\OrderItemDynamicCard;
use YouSaidItCards\ShipStation\Order;

class BackgroundDynamicPdfGenerator extends BackgroundProcess {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'save_and_dispatch' ] );
		}

		return self::$instance;
	}

	/**
	 * Save and dispatch
	 */
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

		$generate = self::generate_for_order_item( $order_id, $order_item_id );
		if ( is_wp_error( $generate ) ) {
			return $item;
		}

		return false;
	}

	public static function generate_for_order_item( int $order_id, int $order_item_id, bool $overwrite = false ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return new WP_Error( 'order_not_found', 'Order not found for #' . $order_id );
		}

		$order_item = $order->get_item( $order_item_id );
		if ( ! $order_item instanceof WC_Order_Item_Product ) {
			return new WP_Error( 'order_not_found', 'Order item not found for item #' . $order_item_id );
		}

		$order_dir = Uploader::get_upload_dir( 'dynamic-pdf/' . $order_id );
		$filename  = "$order_dir/dc-$order_item_id.pdf";

		$item = new OrderItemDynamicCard( $order, $order_item );
		if ( ! $item->get_ship_station_id() ) {
			return new WP_Error( 'shipstation_id_not_found', 'Shipstation id is not available for order #' . $order_id );
		}
		if ( ! file_exists( $filename ) || $overwrite ) {
			$item->pdf( [ 'dest' => 'F', 'name' => $filename ] );
		}

		return $filename;
	}
}
