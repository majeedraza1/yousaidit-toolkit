<?php


namespace Yousaidit\Modules\CardMerger\PDFMergers;


use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use YouSaidItCards\ShipStation\Order;

class A4PDFMerger extends PDFMerger {
	/**
	 * Combine multiple files into one PDF
	 *
	 * @param Order[] $orders
	 * @param bool $inner_message
	 * @param null|string $output_file_name
	 */
	public static function combinePDFs( $orders, $inner_message = false, $output_file_name = null ) {
		static::$print_inner_message = $inner_message;

		$pdf_merger = new PDFMerger();
		$pdf_merger->set_orientation( 'l' );
		$pdf_merger->set_unit( 'mm' );
		$pdf_merger->set_card_width( 426 );
		$pdf_merger->set_card_height( 303 );
		$pdf_merger->set_size( [ $pdf_merger->get_card_width(), $pdf_merger->get_card_height() ] );

		$pdf = $pdf_merger->get_fpdi_instance();

		foreach ( $orders as $order ) {
			try {
				$order_items = $order->get_order_items();
				static::add_page_to_pdf( $order_items, $pdf );
			} catch ( CrossReferenceException $e ) {
			} catch ( FilterException $e ) {
			} catch ( PdfTypeException $e ) {
			} catch ( PdfParserException $e ) {
			} catch ( PdfReaderException $e ) {
			}
		}

		if ( empty( $output_file_name ) ) {
			$output_file_name = 'orders-pdf-' . uniqid() . '.pdf';
		}

		$pdf->Output( 'D', $output_file_name );
	}
}
