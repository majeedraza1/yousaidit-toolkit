<?php

namespace YouSaidItCards\Modules\InnerMessage;

use JoyPixels\Client;
use JoyPixels\Ruleset;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\Utilities\Filesystem;
use YouSaidItCards\Utilities\FreePdfBase;
use YouSaidItCards\Utils;

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

		$meta2       = $order_item->get_meta( '_video_inner_message', true );
		$inner_info2 = is_array( $meta2 ) ? $meta2 : [];
		if ( is_array( $inner_info2 ) && isset( $inner_info2['type'] ) ) {
			if ( 'video' === $inner_info2['type'] && isset( $inner_info2['video_id'] ) && is_numeric( $inner_info2['video_id'] ) ) {
				$url                = Utils::get_video_message_url( intval( $inner_info2['video_id'] ) );
				$video_delete_after = get_post_meta( $inner_info2['video_id'], '_should_delete_after_time', true );
				if ( $video_delete_after ) {
					$this->video_delete_after = (int) $video_delete_after;
				}
				if ( $url ) {
					$this->video_message_qr_code = QrCode::generate_video_message( $url );
					$this->has_video_message     = true;
				}
			}
			if ( 'text' === $inner_info2['type'] && ! empty( $inner_info2['content'] ) ) {
				$this->left_page_message     = $inner_info2;
				$this->has_left_page_message = true;
			}
		}

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
}
