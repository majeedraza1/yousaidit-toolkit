<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PageBoundaries;
use WC_Order_Item_Product;

class TestPdfMerger extends PDFMerger {
	/**
	 * @param WC_Order_Item_Product $order_item
	 * @param int $order_id
	 *
	 * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
	 * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
	 * @throws \setasign\Fpdi\PdfReader\PdfReaderException
	 */
	public static function combinePDFs( $order_item, $order_id = 0 ) {
		$product = $order_item->get_product();
		$order   = wc_get_order( $order_item->get_order_id() );
		$qty     = 0;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$qty += $item->get_quantity();
		}
		if ( ! $order_id ) {
			$order_id = $order_item->get_order_id();
		}

		$pdf_id  = (int) $product->get_meta( '_pdf_id', true );
		$width   = (int) get_post_meta( $pdf_id, '_pdf_width_millimeter', true );
		$height  = (int) get_post_meta( $pdf_id, '_pdf_height_millimeter', true );
		$pdf_url = wp_get_attachment_url( $pdf_id );

		$pdf_merger = new PDFMerger();
		$pdf_merger->set_orientation( 'l' );
		$pdf_merger->set_unit( 'mm' );
		$pdf_merger->set_card_width( $width );
		$pdf_merger->set_card_height( $height );
		$pdf_merger->set_size( [ $pdf_merger->get_card_width(), $pdf_merger->get_card_height() ] );

		$pdf = $pdf_merger->get_fpdi_instance();
		// Import card
		$cardContent = file_get_contents( $pdf_url, 'rb' );
		$stream      = StreamReader::createByString( $cardContent );
		$pdf->addPage();
		$pdf->setSourceFile( $stream );
		$pageId = $pdf->importPage( 1, PageBoundaries::MEDIA_BOX );
		list( $card_width, $card_height ) = $pdf->getImportedPageSize( $pageId );
		$pdf->useImportedPage( $pageId, 0, 0, $card_width, $card_height );

		// Add qr code
		self::add_qr_code( $pdf, $order_id, $card_width, $card_height );
		self::add_total_qty( $pdf, $card_height, $qty );

		$pdf->Output( 'I' );
	}
}
