<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;

class DynamicSizePdfMerger extends PDFMerger {

	public static function combinePDFs( array $order_items, $card_width, $card_height, $inner_message ) {
		static::$print_inner_message = $inner_message;

		$pdf_merger = new PDFMerger();
		$pdf_merger->set_orientation( 'l' );
		$pdf_merger->set_unit( 'mm' );
		$pdf_merger->set_card_width( $card_width );
		$pdf_merger->set_card_height( $card_height );
		$pdf_merger->set_size( [ $pdf_merger->get_card_width(), $pdf_merger->get_card_height() ] );

		$pdf = $pdf_merger->get_fpdi_instance();

		try {
			static::add_page_to_pdf( $order_items, $pdf );
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
}
