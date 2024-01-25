<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use Exception;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\OrderItem;

class DynamicSizePdfMerger extends PDFMerger {

	/**
	 * Get orientation from card size
	 *
	 * @param int $card_width
	 * @param int $card_height
	 *
	 * @return string
	 */
	public static function get_orientation_from_size( int $card_width, int $card_height ): string {
		if ( $card_width < $card_height ) {
			return 'portrait';
		}

		return 'landscape';
	}

	/**
	 * @param OrderItem[] $order_items
	 * @param int $card_width Card width in mm
	 * @param int $card_height Card height in mm
	 * @param bool $inner_message Should show inner message
	 * @param string $type Card type 'inner message', 'card' or both
	 */
	public static function combinePDFs( array $order_items, int $card_width, int $card_height, bool $inner_message, $type = 'both' ) {
		static::$print_inner_message = $inner_message;

		$pdf_merger = new PDFMerger();
		$pdf_merger->set_orientation( self::get_orientation_from_size( $card_width, $card_height ) );
		$pdf_merger->set_unit( 'mm' );
		$pdf_merger->set_card_width( $card_width );
		$pdf_merger->set_card_height( $card_height );
		$pdf_merger->set_size( [ $pdf_merger->get_card_width(), $pdf_merger->get_card_height() ] );

		$args = [
			'card_width'    => $card_width,
			'card_height'   => $card_height,
			'inner_message' => $inner_message,
			'type'          => in_array( $type, [ 'both', 'pdf', 'im' ] ) ? $type : 'both',
		];

		$pdf = $pdf_merger->get_fpdi_instance();

		try {
			static::add_page_to_pdf( $order_items, $pdf, $args );
		} catch ( Exception $e ) {
		}

		if ( empty( $output_file_name ) ) {
			$output_file_name = 'orders-pdf-' . uniqid() . '.pdf';
		}

		$pdf->Output( 'D', $output_file_name );
	}

	/**
	 * Get order pdf
	 *
	 * @param Order $order
	 */
	public static function get_order_pdf( Order $order ) {
		$order_item = $order->get_order_items();
		$item       = $order_item[0];
		self::combinePDFs( [ $item ], $item->get_pdf_width(), $item->get_pdf_height(), $item->has_inner_message() );
	}
}
