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

			add_action( 'admin_notices', [ self::$instance, 'admin_notices' ] );
			add_action( 'wp_ajax_generate_dynamic_card_pdf', [ self::$instance, 'generate_single_dynamic_card_pdf' ] );
			add_action( 'wp_ajax_dynamic_card_generate_now', [ self::$instance, 'generate_all_dynamic_card_pdf' ] );
			add_action( 'shutdown', [ self::$instance, 'save_and_dispatch' ] );
		}

		return self::$instance;
	}

	/**
	 * Show admin notice
	 *
	 * @return void
	 */
	public function admin_notices() {
		$list  = (array) get_option( '_dynamic_card_to_generate', [] );
		$count = count( $list );
		if ( $count < 1 ) {
			return;
		}
		$update_url = wp_nonce_url(
			add_query_arg( [ 'action' => 'dynamic_card_generate_now' ], admin_url( 'admin-ajax.php' ) ),
			'dynamic_card_generator'
		);
		$count_text = sprintf( _n( '%s dynamic card', '%s dynamic cards', $count ), $count );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				A background task is running to generate <?php echo $count_text ?>. Make sure all orders
				are sync to ShipStation. Dynamic card won't be generated without ShipStation id.<br>
				To generate all card now, click "Generate Now" button. Remember, generating all dynamic card is a CPU
				resource consuming task.<br><br>
				<a class="button button-primary" href="<?php echo esc_url( $update_url ) ?>">Generate Now</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Generate all dynamic card PDF
	 *
	 * @return void
	 */
	public function generate_all_dynamic_card_pdf() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce       = $_REQUEST['_wpnonce'] ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : null;
		$is_verified = wp_verify_nonce( $nonce, 'dynamic_card_generator' );

		$message = '<h1>' . esc_html__( 'Yousaidit Toolkit', 'yousaidit-toolkit' ) . '</h1>';
		if ( ! ( current_user_can( 'manage_options' ) && $is_verified ) ) {
			$message .= '<p>' . __( 'Sorry. This link only for admin to perform upgrade tasks.', 'yousaidit-toolkit' ) . '</p>';
			_default_wp_die_handler( $message, '', [ 'back_link' => true ] );
		}

		$error_messages = [];

		$list = (array) get_option( '_dynamic_card_to_generate', [] );
		foreach ( $list as $item ) {
			list( $order_id, $order_item_id ) = explode( '|', $item );
			$file_path = BackgroundDynamicPdfGenerator::generate_for_order_item(
				intval( $order_id ),
				intval( $order_item_id )
			);
			if ( is_wp_error( $file_path ) ) {
				$error_messages[] = $file_path->get_error_message();
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
			$message .= '<p>' . __( 'Dynamic card has been generated successfully.', 'yousaidit-toolkit' ) . '</p>';
		}
		_default_wp_die_handler( $message, '', [ 'back_link' => true ] );
	}

	/**
	 * Generate dynamic card PDF
	 *
	 * @return void
	 */
	public function generate_single_dynamic_card_pdf() {
		$order_id      = $_REQUEST['order_id'] ?? 0;
		$order_item_id = $_REQUEST['order_item_id'] ?? 0;
		$force         = isset( $_REQUEST['force'] );
		$filepath      = BackgroundDynamicPdfGenerator::generate_for_order_item(
			intval( $order_id ),
			intval( $order_item_id ),
			$force
		);
		if ( is_wp_error( $filepath ) ) {
			wp_send_json_error( $filepath );
		}
		wp_send_json_success( [ 'path' => $filepath ] );
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
	private static function generate_for_order_item( int $order_id, int $order_item_id, bool $overwrite = false ) {
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
	 * Show PDF url if exists or message to generate PDF
	 *
	 * @param int $order_id WooCommerce order id.
	 * @param int $order_item_id WooCommerce order item id.
	 *
	 * @return string|void
	 */
	public static function get_pdf_url( int $order_id, int $order_item_id ) {
		$order_dir = Uploader::get_upload_dir( 'dynamic-pdf/' . $order_id );
		$filename  = "$order_dir/dc-$order_item_id.pdf";
		if ( file_exists( $filename ) ) {
			$upload_dir = wp_get_upload_dir();

			return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $filename );
		}

		$url = add_query_arg( [
			'action'        => 'generate_dynamic_card_pdf',
			'order_id'      => $order_id,
			'order_item_id' => $order_item_id,
		], admin_url( 'admin-ajax.php' ) );

		$message = '<h1>' . esc_html__( 'Yousaidit Toolkit', 'yousaidit-toolkit' ) . '</h1>';
		$message .= '<p>' . __( 'Sorry. The card is not ready to view yet. Click the following button to generate now.', 'yousaidit-toolkit' ) . '</p>';
		$message .= '<p><a target="_blank" href="' . esc_url( $url ) . '">Generate Now</a></p>';
		_default_wp_die_handler( $message, '', [ 'back_link' => true ] );
	}
}
