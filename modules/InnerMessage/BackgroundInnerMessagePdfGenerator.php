<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Supports\Logger;
use WC_Order;
use WC_Order_Item_Product;

/**
 * BackgroundInnerMessagePdfGenerator class
 */
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

			add_action( 'admin_notices', [ self::$instance, 'admin_notices' ] );
			add_action( 'shutdown', [ self::$instance, 'dispatch_data' ] );
		}

		return self::$instance;
	}

	/**
	 * Show admin notice
	 *
	 * @return void
	 */
	public function admin_notices() {
		$list  = $this->get_background_task_list();
		$count = count( $list );
		if ( $count < 1 ) {
			return;
		}
		$count_text = sprintf( _n( '%s inner message', '%s inner messages', $count ), $count );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				A background task is running to generate <?php echo $count_text ?>. Make sure all orders are sync to
				ShipStation.
			</p>
		</div>
		<?php
	}

	/**
	 * Get background task list
	 *
	 * @return array
	 */
	public function get_background_task_list(): array {
		global $wpdb;
		$item = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name LIKE %s", '%' . $this->action . '%' ),
			ARRAY_A
		);

		return is_array( $item ) ? $item : [];
	}

	/**
	 * Generate for order
	 *
	 * @param WC_Order $order
	 * @param bool $immediately
	 */
	public static function generate_for_order( WC_Order $order, bool $immediately = false ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$_inner_message = $item->get_meta( '_inner_message', true );
			if ( is_array( $_inner_message ) ) {
				if ( $immediately ) {
					$generator = new PdfGenerator( $item );
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

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			Logger::log( "Invalid order id # $order_id. Could not generate inner message for the order." );

			return false;
		}

		try {
			$order_item = new WC_Order_Item_Product( $item_id );
			$generator  = new PdfGenerator( $order_item );
			$generator->save_to_file_system();
		} catch ( \Exception $exception ) {
			Logger::log( 'There is a error when generating inner message for order item #' . $item_id );
			Logger::log( $exception );
		}

		// Set false to remove task from queue
		return false;
	}
}
