<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use WC_Order_Item_Product;

class BackgroundInnerMessagePdfGenerator extends BackgroundProcess {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	public static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_im_generator';

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'dispatch_data' ] );
		}

		return self::$instance;
	}

	/**
	 * Generate for order
	 *
	 * @param \WC_Order $order
	 * @param bool $immediately
	 */
	public static function generate_for_order( \WC_Order $order, $immediately = false ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$_inner_message = $item->get_meta( '_inner_message', true );
			if ( is_array( $_inner_message ) ) {
				if ( $immediately ) {
					$generator = new PdfGenerator( $item, $order );
					$generator->save_to_file_system();
				} else {
					static::init()->push_to_queue( [ 'order_id' => $order->get_id(), 'item_id' => $item->get_id() ] );
				}
			}
		}
	}

	/**
	 * Save and run background on shutdown of all code
	 */
	public function dispatch_data() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$order_id = isset( $item['order_id'] ) ? intval( $item['order_id'] ) : 0;
		$item_id  = isset( $item['item_id'] ) ? intval( $item['item_id'] ) : 0;

		if ( $order_id && $item_id ) {
			$wc_order   = wc_get_order( $order_id );
			$order_item = new \WC_Order_Item_Product( $item_id );
			$generator  = new PdfGenerator( $order_item, $wc_order );
			$generator->save_to_file_system();
		}

		// Set false to remove task from queue
		return false;
	}
}
