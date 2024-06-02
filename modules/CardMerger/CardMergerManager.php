<?php

namespace YouSaidItCards\Modules\CardMerger;

use Stackonet\WP\Framework\Supports\Validate;
use WC_Order;
use YouSaidItCards\Modules\CardMerger\PDFMergers\DynamicSizePdfMerger;
use YouSaidItCards\Modules\CardMerger\PDFMergers\MugMerger;
use YouSaidItCards\Modules\DynamicCard\BackgroundDynamicPdfGenerator;
use YouSaidItCards\Modules\InnerMessage\PdfGenerator;
use YouSaidItCards\Modules\OrderDispatcher\QtyCode;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\ShipStationApi;

class CardMergerManager {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'wp_ajax_yousaidit_download_pdf', [ self::$instance, 'combine_and_download_pdf' ] );
			add_action( 'wp_ajax_yousaidit_single_im_card', [ self::$instance, 'single_inner_message_card' ] );
			add_action( 'wp_ajax_yousaidit_single_pdf_card', [ self::$instance, 'single_pdf_card' ] );
			add_action( 'wp_ajax_yousaidit_ship_station_order', [ self::$instance, 'ship_station_order' ] );
			add_action( 'wp_ajax_yousaidit_text_to_image', [ self::$instance, 'text_to_image' ] );
		}

		return self::$instance;
	}

	/**
	 * View ShipStation order
	 */
	public function ship_station_order() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}
		$order_id       = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		$transient_name = 'shipstation_order_' . $order_id;
		$order          = get_transient( $transient_name );
		if ( ! is_array( $order ) ) {
			$order = ShipStationApi::init()->get_order( $order_id );
			set_transient( $transient_name, $order, DAY_IN_SECONDS );
		}
//		header( 'Content-type: text/json' );
		if ( is_array( $order ) ) {
			$order = new Order( $order );
//			echo "<pre><code>";
			var_dump( $order );
//			echo "</code></pre>";
		} else {
			var_dump( $order );
		}
		die();
	}

	/**
	 * Download PDf card
	 */
	public static function combine_and_download_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}

		$type = isset( $_GET['type'] ) && in_array( $_GET['type'], [ 'both', 'pdf', 'im' ] ) ? $_GET['type'] : 'both';

		$card_width    = isset( $_GET['card_width'] ) ? intval( $_GET['card_width'] ) : 0;
		$card_height   = isset( $_GET['card_height'] ) ? intval( $_GET['card_height'] ) : 0;
		$inner_message = isset( $_GET['inner_message'] ) && Validate::checked( $_GET['inner_message'] );
		$card_type     = isset( $_GET['card_type'] ) && in_array( $_GET['card_type'], [ 'static', 'dynamic', 'mug' ] ) ?
			$_GET['card_type'] : 'static';
		$ids           = $_GET['ids'] ?? '';
		$ids           = is_string( $ids ) ? explode( ',', $ids ) : [];
		$ids           = count( $ids ) ? array_map( 'intval', $ids ) : [];

		$orders = Order::get_orders_by_ids( $ids, [ 'force' => true ] );
		$items  = [];
		foreach ( $orders as $order ) {
			$order_items = $order->get_order_items();
			foreach ( $order_items as $order_item ) {
				if (
					$card_width == $order_item->get_pdf_width() &&
					$card_height == $order_item->get_pdf_height() &&
					$card_type == $order_item->get_card_type()
				) {
					$items[] = $order_item;
				}
			}
		}
		if ( 'mug' === $card_type ) {
			MugMerger::combinePDFs( $items );
		} else {
			DynamicSizePdfMerger::combinePDFs( $items, $card_width, $card_height, $inner_message, $type );
		}
		die();
	}

	/**
	 * Single inner message card view or download
	 */
	public function single_inner_message_card() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}
		$ship_station_order_id = isset( $_GET['ship_station_order_id'] ) ? intval( $_GET['ship_station_order_id'] ) : 0;
		$order_id              = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$item_id               = isset( $_GET['item_id'] ) ? intval( $_GET['item_id'] ) : 0;

		if ( empty( $ship_station_order_id ) && ( empty( $order_id ) && empty( $item_id ) ) ) {
			die( "Param 'ship_station_order_id' is required." );
		}

		if ( $item_id ) {
			$order_item = new \WC_Order_Item_Product( $item_id );
		} else {
			$order = Order::get_order( $ship_station_order_id );
			if ( ! $order instanceof Order ) {
				die( 'No order found with this id.' );
			}

			$items      = $order->get_order_items();
			$order_item = $items[0]->get_wc_order_item();
		}

		$mode      = isset( $_REQUEST['mode'] ) && in_array( $_REQUEST['mode'], [ 'pdf', 'html' ] ) ?
			$_REQUEST['mode'] : 'pdf';
		$generator = new PdfGenerator( $order_item );
		$generator->get_pdf( $mode );
//		$generator->_test_fpdf( $mode );
		die();
	}

	/**
	 *
	 */
	public function single_pdf_card() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}
		$wc_order_id      = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$wc_order_item_id = isset( $_GET['item_id'] ) ? intval( $_GET['item_id'] ) : 0;
		$wc_order         = wc_get_order( $wc_order_id );
		if ( ! $wc_order instanceof WC_Order ) {
			die( 'No order found for this is.' );
		}
		$order_item      = new \WC_Order_Item_Product( $wc_order_item_id );
		$product_id      = $order_item->get_product_id();
		$product         = wc_get_product( $product_id );
		$postcard_pdf_id = (int) $order_item->get_meta( '_postcard_pdf_id', true );
		$dynamic_card_id = (int) $order_item->get_meta( '_dynamic_card', true );
		if ( $postcard_pdf_id ) {
			$url = wp_get_attachment_url( $postcard_pdf_id );
		} elseif ( $dynamic_card_id ) {
			$url = BackgroundDynamicPdfGenerator::get_pdf_url( $wc_order_id, $wc_order_item_id );
			if ( ! Validate::url( $url ) ) {
				die;
			}
		} else {
			$pdf_id = (int) $product->get_meta( '_pdf_id', true );
			$url    = wp_get_attachment_url( $pdf_id );
		}

		header( "Location: {$url}" );
		die();
	}

	public function text_to_image() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}
		$text = isset( $_REQUEST['text'] ) ? sanitize_text_field( $_REQUEST['text'] ) : '';

		if ( ! empty( $text ) ) {
			$image = QtyCode::get_dynamic_image( 96, $text );
			header( "Content-Type: image/png" );
			echo $image;
			die();
		}
		echo 'No content available';
		die();
	}
}
