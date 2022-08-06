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
			add_action( 'wp_ajax_inner_message_generate_now', [ self::$instance, 'generate_all_inner_message_pdf' ] );
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
		$update_url = wp_nonce_url(
			add_query_arg( [ 'action' => 'inner_message_generate_now' ], admin_url( 'admin-ajax.php' ) ),
			'inner_message_generate_now'
		);
		$count_text = sprintf( _n( '%s inner message', '%s inner messages', $count ), $count );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				A background task is running to generate <?php echo $count_text ?>. Make sure all orders are sync to
				ShipStation.<br><br>
				<a class="button button-primary" href="<?php echo esc_url( $update_url ) ?>">Generate Now</a>
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
		$identifier = $this->prefix . '_' . $this->action;
		global $wpdb;
		$items = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name LIKE %s", $identifier . '%' ),
			ARRAY_A
		);

		$data = [];
		foreach ( $items as $item ) {
			$value = maybe_unserialize( $item['option_value'] );
			if ( ! is_array( $value ) ) {
				continue;
			}
			$data = array_merge( $data, $value );
		}

		return $data;
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
			if ( ! $generator->is_pdf_generated() ) {
				$generator->save_to_file_system();
			}
		} catch ( \Exception $exception ) {
			Logger::log( 'There is a error when generating inner message for order item #' . $item_id );
			Logger::log( $exception );
		}

		// Set false to remove task from queue
		return false;
	}

	/**
	 * Generate all dynamic card PDF
	 *
	 * @return void
	 */
	public function generate_all_inner_message_pdf() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce       = $_REQUEST['_wpnonce'] ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : null;
		$is_verified = wp_verify_nonce( $nonce, 'inner_message_generate_now' );

		$message = '<h1>' . esc_html__( 'Yousaidit Toolkit', 'yousaidit-toolkit' ) . '</h1>';
		if ( ! ( current_user_can( 'manage_options' ) && $is_verified ) ) {
			$message .= '<p>' . __( 'Sorry. This link only for admin to perform upgrade tasks.', 'yousaidit-toolkit' ) . '</p>';
			_default_wp_die_handler( $message, '', [ 'back_link' => true ] );
		}

		$error_messages = [];

		$lists = self::get_background_task_list();
		foreach ( $lists as $list ) {

			if ( false !== $this->task( $list ) ) {
				$error_messages[] = sprintf(
					'Order #%s, Order Item #%s: could not generate PDF.',
					$list['order_id'],
					$list['item_id']
				);
			}
		}

		if ( count( $error_messages ) ) {
			$message .= '<p>' . __( 'One or more errors has been generated when running the task.', 'yousaidit-toolkit' ) . '</p>';
			$message .= '<ul>';
			foreach ( $error_messages as $error_message ) {
				$message .= '<li>' . $error_message . '</li>';
			}
			$message .= '</ul>';
		} else {
			$message .= '<p>' . __( 'Inner message PDF has been generated successfully.', 'yousaidit-toolkit' ) . '</p>';
		}
		_default_wp_die_handler( $message, '', [ 'back_link' => true ] );
		die;
	}
}
