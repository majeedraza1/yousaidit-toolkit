<?php

namespace Yousaidit\Modules\PackingSlip;

use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\ShipStation\Order;

class PackingSlipManager {

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

			add_action( 'wp_ajax_stackonet_order_packing_slip', array( self::$instance, 'order_packing_slip' ) );
			add_action( 'wp_ajax_stackonet_order_packing_slips', array( self::$instance, 'order_packing_slips' ) );
		}

		return self::$instance;
	}

	/**
	 * Order packing slip
	 */
	public function order_packing_slip() {
		$id    = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$order = Order::get_order( $id );
		if ( ! $order instanceof Order ) {
			die( 'No order found with this id.' );
		}

		try {
			$packing_slip = new PackingSlip( $order );
			$packing_slip->generate_pdf();
		} catch ( Exception $e ) {
			Logger::log( $e );
			die( 'Could not create PDF.' );
		}

		die();
	}

	/**
	 * Get multiple packing slip
	 *
	 * @throws Exception
	 */
	public function order_packing_slips() {
		$ids     = isset( $_GET['ids'] ) ? $_GET['ids'] : '';
		$ids     = is_string( $ids ) ? array_map( 'intval', explode( ',', $ids ) ) : [];
		$_orders = Order::get_orders();
		$content = '';
		/** @var Order $order */
		foreach ( $_orders['items'] as $index => $order ) {
			if ( ! in_array( $order->get_id(), $ids ) ) {
				continue;
			}
			if ( ! $order->has_product() ) {
				continue;
			}

			if ( $index != 0 ) {
				$content .= "\n<div style=\"page-break-before: always;\"></div>\n";
			}
			$content .= PackingSlip::get_content( $order );
		}
		$html      = PackingSlip::get_document( $content );
		$file_name = 'packing-slim-multi-' . uniqid() . '.pdf';

		PackingSlip::get_pdf( $html, $file_name );
		die;
	}
}
