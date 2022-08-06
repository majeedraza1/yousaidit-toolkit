<?php

namespace YouSaidItCards\Modules\InnerMessage;

use JoyPixels\Client;
use JoyPixels\Ruleset;
use WC_Order_Item_Product;
use YouSaidItCards\Utilities\Filesystem;
use YouSaidItCards\Utilities\FreePdfBase;

class PdfGenerator extends PdfGeneratorBase {
	protected $order_id = 0;
	protected $item_id = 0;
	protected $product_id = 0;

	/**
	 * @var WC_Order_Item_Product
	 */
	protected $order_item_product;

	/**
	 * PdfGenerator constructor.
	 *
	 * @param WC_Order_Item_Product|null $order_item
	 */
	public function __construct( ?WC_Order_Item_Product $order_item = null ) {
		if ( $order_item instanceof WC_Order_Item_Product ) {
			$this->setOrderItemProduct( $order_item );
			$this->read_from_wc_order_item();
		}
	}

	public static function get_pdf_for_order_item( WC_Order_Item_Product $item_product ): string {
		$generator = new static( $item_product );

		return $generator->create_if_not_exists();
	}

	public function create_if_not_exists(): string {
		$file = $this->get_filepath();
		if ( ! file_exists( $file ) ) {
			$this->save_to_file_system();
		}

		return $file;
	}

	/**
	 * Check if PDF is generated
	 *
	 * @return bool
	 */
	public function is_pdf_generated(): bool {
		return file_exists( $this->get_filepath() );
	}

	/**
	 * Save PDF to filesystem
	 *
	 * @param string|null $fileName
	 *
	 * @return void
	 */
	public function save_to_file_system( ?string $fileName = null ) {
		if ( empty( $fileName ) ) {
			$fileName = $this->get_filename();
		}
		$dir = Filesystem::get_uploads_dir( 'inner-message' );
		Filesystem::maybe_create_dir( $dir['path'] );
		$dompdf = $this->get_dompdf();
		$dompdf->render();
		$output = $dompdf->output();

		Filesystem::update_file_content( $output, $dir['path'] . '/' . $fileName );
	}


	/**
	 * @return WC_Order_Item_Product
	 */
	public function getOrderItemProduct(): WC_Order_Item_Product {
		return $this->order_item_product;
	}

	/**
	 * @param WC_Order_Item_Product $order_item_product
	 *
	 * @return PdfGenerator
	 */
	public function setOrderItemProduct( WC_Order_Item_Product $order_item_product ): PdfGenerator {
		$this->order_item_product = $order_item_product;

		return $this;
	}

	/**
	 * @return string
	 */
	protected function get_filename(): string {
		return sprintf( "%s-o%s-i%s.pdf", $this->dir, $this->order_id, $this->item_id );
	}

	/**
	 * @return string
	 */
	protected function get_filepath(): string {
		$dir = Filesystem::get_uploads_dir( 'inner-message' );
		Filesystem::maybe_create_dir( $dir['path'] );

		return $dir['path'] . '/' . $this->get_filename();
	}

	/**
	 * @return void
	 */
	private function read_from_wc_order_item(): void {
		$order_item       = $this->getOrderItemProduct();
		$this->item_id    = $order_item->get_id();
		$this->order_id   = $order_item->get_order_id();
		$this->product_id = $order_item->get_product_id();
		$order            = wc_get_order( $this->order_id );

		$product = $order_item->get_product();
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$pdf_id = (int) $product->get_meta( '_pdf_id', true );
		$width  = (int) get_post_meta( $pdf_id, '_pdf_width_millimeter', true );
		$height = (int) get_post_meta( $pdf_id, '_pdf_height_millimeter', true );

		$this->dir  = $order->get_date_created()->format( "Y-m-d" );
		$meta       = $order_item->get_meta( '_inner_message', true );
		$inner_info = is_array( $meta ) ? $meta : [];

		$this->font_size   = isset( $inner_info['size'] ) ? intval( $inner_info['size'] ) : 14;
		$this->line_height = $this->font_size * 1.5;
		$this->text_color  = $inner_info['color'] ?? '#000000';
		$this->text_align  = $inner_info['align'] ?? 'center';
		$this->message     = $inner_info['content'] ?? '';
		$this->font_family = $inner_info['font'] ?? 'Arial';;

		if ( $width && $height ) {
			$this->page_size = [ $width, $height ];
		} else {
			$page_size = $inner_info['page_size'] ?? 'square';
			if ( in_array( $page_size, [ 'a4', 'a5', 'a6' ] ) ) {
				$this->page_size = $page_size;
			} else {
				$this->page_size = [ 306, 156 ];
			}
		}
	}

	public function _test_fpdf() {
		$client = new Client( new Ruleset() );

		$messages = $this->get_message_lines();

		var_dump( $messages );
		die;

		$fontEmoji = Fonts::get_font_info( 'Noto Emoji' );
		$font      = Fonts::get_font_info( $this->font_family );
		$fpd       = new \tFPDF( 'L', 'mm', [ $this->page_size[0], $this->page_size[1] ] );

		// Add font
		$font_family = str_replace( ' ', '', $font['label'] );
		$fpd->AddFont( $font_family, '', $font['fileName'], true );
//		$fpd->AddFont( 'NotoEmoji', '', $fontEmoji['fileName'], true );

		$fpd->AddPage();

		list( $red, $green, $blue ) = FreePdfBase::find_rgb_color( $this->text_color );
		$fpd->SetTextColor( $red, $green, $blue );
		$fpd->SetFont( $font_family, '', $this->font_size );
//		$fpd->SetFont( 'NotoEmoji', '', $this->font_size );

		$line_gap = ( $this->font_size / 3 );
		$y_pos    = ( $fpd->GetPageHeight() / 2 ) - ( count( $messages ) * $line_gap );

		foreach ( $messages as $index => $message ) {
			$x_pos = $fpd->GetPageWidth() / 4 * 3 - $fpd->GetStringWidth( $message ) / 2;
			if ( $index > 0 ) {
				$y_pos += $line_gap;
			}

			if ( false !== strpos( '<img', $message ) ) {
// /(?P<imgTag><img\s.*.src="(?P<imgSrc>\s*.*)"\s?\/>)/mgi
			}
			$text_width = $fpd->GetStringWidth( $message );
			$fpd->Text( $x_pos, $y_pos, $message );
		}

		$fpd->Output( $args['dest'] ?? '', $args['name'] ?? '' );
	}
}
