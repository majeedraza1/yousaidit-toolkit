<?php

namespace YouSaidItCards\Modules\CardMerger;

use Stackonet\WP\Framework\Supports\Validate;
use YouSaidItCards\Modules\CardMerger\PDFMergers\DynamicSizePdfMerger;
use YouSaidItCards\Modules\CardMerger\PDFMergers\TestPdfMerger;
use YouSaidItCards\Modules\InnerMessage\PdfGenerator;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\SyncShipStationOrder;

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
		$order_id = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		$pages    = SyncShipStationOrder::init_sync_for_shipped_orders();
		var_dump( $pages );
		die();
	}

	/**
	 * Download PDf card
	 */
	public static function combine_and_download_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}

		$card_width    = isset( $_GET['card_width'] ) ? intval( $_GET['card_width'] ) : 0;
		$card_height   = isset( $_GET['card_height'] ) ? intval( $_GET['card_height'] ) : 0;
		$inner_message = isset( $_GET['inner_message'] ) ? Validate::checked( $_GET['inner_message'] ) : false;
		$ids           = isset( $_GET['ids'] ) ? $_GET['ids'] : '';
		$ids           = is_string( $ids ) ? explode( ',', $ids ) : [];
		$ids           = count( $ids ) ? array_map( 'intval', $ids ) : [];

		$orders = Order::get_orders_by_ids( $ids );
		$items  = [];
		foreach ( $orders as $order ) {
			$order_items = $order->get_order_items();
			foreach ( $order_items as $order_item ) {
				if ( $card_width == $order_item->get_pdf_width() && $card_height == $order_item->get_pdf_height() ) {
					$items[] = $order_item;
				}
			}
		}
		DynamicSizePdfMerger::combinePDFs( $items, $card_width, $card_height, $inner_message );
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

		$wc_order = null;
		if ( $order_id ) {
			$wc_order = wc_get_order( $order_id );
		}

		$mode      = isset( $_REQUEST['mode'] ) && in_array( $_REQUEST['mode'], [ 'pdf', 'html' ] ) ?
			$_REQUEST['mode'] : 'pdf';
		$generator = new PdfGenerator( $order_item, $wc_order );
		$generator->get_pdf( $mode );
		die();
	}

	/**
	 *
	 */
	public function single_pdf_card() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Only admin can perform this action.' );
		}
		$order_id   = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$item_id    = isset( $_GET['item_id'] ) ? intval( $_GET['item_id'] ) : 0;
		$order_item = new \WC_Order_Item_Product( $item_id );
		$merger     = TestPdfMerger::combinePDFs( $order_item, $order_id );
		die();
	}
}
