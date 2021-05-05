<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\OrderItem;

class DynamicSizePdfMerger extends PDFMerger {

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
		$pdf_merger->set_orientation( 'l' );
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
		} catch ( CrossReferenceException $e ) {
		} catch ( FilterException $e ) {
		} catch ( PdfTypeException $e ) {
		} catch ( PdfParserException $e ) {
		} catch ( PdfReaderException $e ) {
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
