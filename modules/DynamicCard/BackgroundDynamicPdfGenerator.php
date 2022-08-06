<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use WC_Order;
use WC_Order_Item_Product;
use WP_Error;
use YouSaidItCards\Modules\DynamicCard\Models\OrderItemDynamicCard;

class BackgroundDynamicPdfGenerator extends BackgroundProcess {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_dynamic_pdf_generator';

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
	 * @param int $order_id
	 * @param int $order_item_id
	 *
	 * @return void
	 */
	private static function update_card_to_generate_list( int $order_id, int $order_item_id ): void {
		$list = (array) get_option( '_dynamic_card_to_generate', [] );
		if ( count( $list ) ) {
			$index = array_search( sprintf( "%s|%s", $order_id, $order_item_id ), $list );
			if ( false !== $index ) {
				unset( $list[ $index ] );
			}
			update_option( '_dynamic_card_to_generate', $list );
		}
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
			if ( in_array( $generate->get_error_code(), [ 'order_not_found', 'order_item_not_found' ], true ) ) {
				Logger::log( $generate->get_error_message() );

				return false;
			}

			return $item;
		}

		return false;
	}

	/**
	 * Generate for order item
	 *
	 * @param int $order_id WooCommerce order id.
	 * @param int $order_item_id WooCommerce order item id.
	 * @param bool $overwrite Should it overwrite if already exists in directory.
	 *
	 * @return string|WP_Error
	 */
	public static function generate_for_order_item( int $order_id, int $order_item_id, bool $overwrite = false ) {
		// If PDF is already generated, return the file path
		$order_dir = Uploader::get_upload_dir( 'dynamic-pdf/' . $order_id );
		$filename  = "$order_dir/dc-$order_item_id.pdf";
		if ( file_exists( $filename ) && ! $overwrite ) {
			return $filename;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			self::update_card_to_generate_list( $order_id, $order_item_id );

			return new WP_Error( 'order_not_found', 'Order not found for #' . $order_id );
		}

		$order_item = $order->get_item( $order_item_id );
		if ( ! $order_item instanceof WC_Order_Item_Product ) {
			self::update_card_to_generate_list( $order_id, $order_item_id );

			return new WP_Error( 'order_item_not_found', 'Order item not found for item #' . $order_item_id );
		}

		$item = new OrderItemDynamicCard( $order, $order_item );
		if ( ! $item->get_ship_station_id() ) {
			return new WP_Error( 'shipstation_id_not_found', 'Shipstation id is not available for order #' . $order_id );
		}
		$item->pdf( [ 'dest' => 'F', 'name' => $filename ] );

		self::update_card_to_generate_list( $order_id, $order_item_id );

		return $filename;
	}

	/**
	 * Check if file is generated already
	 *
	 * @param int $order_id WooCommerce order id.
	 * @param int $order_item_id WooCommerce order item id.
	 *
	 * @return bool
	 */
	public static function is_generated( int $order_id, int $order_item_id ): bool {
		$order_dir = Uploader::get_upload_dir( 'dynamic-pdf/' . $order_id );
		$filename  = "$order_dir/dc-$order_item_id.pdf";

		return file_exists( $filename );
	}
}
