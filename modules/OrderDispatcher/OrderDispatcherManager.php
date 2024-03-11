<?php

namespace YouSaidItCards\Modules\OrderDispatcher;

use YouSaidItCards\ShipStation\Order;

defined( 'ABSPATH' ) || exit;

class OrderDispatcherManager {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the classes can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'admin_menu', [ self::$instance, 'add_menu' ] );
			add_action( 'wp_ajax_generate_barcode', [ self::$instance, 'generate_barcode' ] );
			add_action( 'wp_ajax_print_order_address', [ self::$instance, 'print_order_address' ] );
		}

		return self::$instance;
	}

	public function print_order_address() {
		$id    = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		$order = Order::get_order( $id );
		if ( ! $order instanceof Order ) {
			die( 'No order found!' );
		}

		$address           = $order->get_shipping_address();
		$formatted_address = $order->get_formatted_shipping_address();
		?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport"
				  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
			<meta http-equiv="X-UA-Compatible" content="ie=edge">
			<title>Shipping Address for Order #<?php echo $order->get_id() ?></title>
		</head>
		<body>
		<div id="page" class="page">
			<?php echo $formatted_address ?>
		</div>
		</body>
		</html>
		<?php
		die();
	}

	/**
	 * Generate barcode
	 */
	public function generate_barcode() {
		$code = isset( $_REQUEST['code'] ) ? stripslashes( $_REQUEST['code'] ) : '';
		$code = is_numeric( $code ) ? intval( $code ) : sanitize_text_field( $code );

		$upload_dir = wp_get_upload_dir();
		$fileName   = $upload_dir['basedir'] . '/qr-codes/' . $code . '.png';

		QrCode::generate( $code, $fileName );
		$text = QrCode::read( $fileName );

		var_dump( [ $fileName, $text ] );
		die();
	}

	/**
	 * Add admin menu
	 */
	public function add_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'order-dispatcher';
		$hook       = add_menu_page( 'Order Dispatcher', 'Order Dispatcher', $capability, $slug,
			[ self::$instance, 'menu_page_callback' ], 'dashicons-admin-post', 6 );
		$menus      = [
			[ 'title' => __( 'Order Dispatcher' ), 'slug' => '#/' ],
			[ 'title' => __( 'Print Cards' ), 'slug' => '#/print-cards' ],
			[ 'title' => __( 'Dispatch Orders' ), 'slug' => '#/dispatch-orders' ],
			[ 'title' => __( 'Complete Orders' ), 'slug' => '#/complete-orders' ],
			[ 'title' => __( 'Packing Slip' ), 'slug' => '#/packing-slip' ],
		];
		if ( current_user_can( $capability ) ) {
			foreach ( $menus as $menu ) {
				$submenu[ $slug ][] = [ $menu['title'], $capability, 'admin.php?page=' . $slug . $menu['slug'] ];
			}
		}

		add_action( 'load-' . $hook, [ self::$instance, 'init_hooks' ] );
	}

	/**
	 * Menu page callback
	 */
	public function menu_page_callback() {
		echo '<div id="stackonet_order_dispatcher"></div>';
	}

	/**
	 * Menu page scripts
	 */
	public function init_hooks() {
		wp_enqueue_media();
		wp_enqueue_style( 'yousaidit-toolkit-admin-vue3' );
		wp_enqueue_script( 'yousaidit-toolkit-admin-vue3' );
	}
}
