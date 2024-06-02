<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use YouSaidItCards\FreePdfExtended;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\ShipStation\OrderItem;

class MugMerger {
	/**
	 * @param  OrderItem[]  $orders
	 *
	 * @return void
	 */
	public static function combinePDFs( array $orders ) {

		$fpd = new FreePdfExtended( 'landscape', 'mm', [ 210, 99 ] );
		foreach ( $orders as $order ) {
			$card_id = $order->get_card_id();
			$card    = ( new DesignerCard )->find_by_id( $card_id );
			if ( ! $card instanceof DesignerCard ) {
				continue;
			}
			$fpd->AddPage();

			$image_id = $card->get_image_id();
			$img      = wp_get_attachment_image_src( $image_id, 'full' );
			$fpd->Image( $img[0], 0, 0, $fpd->GetPageWidth(), $fpd->GetPageHeight() );
		}
		$output_file_name = 'orders-pdf-' . uniqid() . '.pdf';
		$fpd->Output( 'D', $output_file_name );
	}
}