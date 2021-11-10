<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use tFPDF;
use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\Utilities\FreePdfBase;

class OrderItemDynamicCard {
	protected $card_id = 0;
	protected $ship_station_id = 0;
	protected $card_size;
	protected $card_width = 0;
	protected $card_height = 0;
	/**
	 * @var CardBackgroundOption
	 */
	protected $background;
	protected $background_type = 'color';
	protected $background_color = '#ffffff';
	protected $background_image = 0;
	protected $card_sections = [];
	protected $order;
	protected $order_item;
	protected $product;
	protected $card_payload_read = false;

	public function __construct( WC_Order $order, WC_Order_Item_Product $order_item ) {
		$this->order      = $order;
		$this->order_item = $order_item;
		$this->product    = $order_item->get_product();
		$this->card_id    = (int) $this->product->get_meta( '_card_id', true );
		$this->read_card_payload();
	}

	public function get_ship_station_order_id(): int {
		return $this->ship_station_id;
	}

	/**
	 * Read card payload
	 */
	public function read_card_payload() {
		if ( $this->card_payload_read ) {
			return;
		}
		$payload_value = (array) $this->order_item->get_meta( '_dynamic_card', true );
		$changed_data  = [];
		foreach ( $payload_value as $item ) {
			if ( ! isset( $item['value'] ) ) {
				continue;
			}
			$changed_data[] = is_numeric( $item['value'] ) ? intval( $item['value'] ) : sanitize_text_field( $item['value'] );
		}
		if ( count( $changed_data ) < 1 ) {
			return;
		}

		$payload               = $this->product->get_meta( '_dynamic_card_payload', true );
		$this->card_size       = $payload['card_size'];
		$this->background_type = $payload['card_bg_type'];
		if ( 'image' == $this->background_type ) {
			$this->background_image = $payload['card_background']['id'];
		} else {
			$this->background_color = $payload['card_bg_color'];
		}

		$sizes = FreePdfBase::get_sizes();
		if ( array_key_exists( $this->card_size, $sizes ) ) {
			list( $this->card_width, $this->card_height ) = $sizes[ $this->card_size ];
		}

		$this->background = new CardBackgroundOption( [
			'type'        => $this->background_type,
			'color'       => $this->background_color,
			'image'       => $this->background_image,
			'card_width'  => $this->card_width,
			'card_height' => $this->card_height,
		] );

		$sections = [];
		foreach ( $payload['card_items'] as $index => $item ) {
			$item['dirty'] = false;
			$changed_value = $changed_data[ $index ] ?? '';
			if ( in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				if ( ! empty( $changed_value ) && $changed_value != $item['text'] ) {
					$item['text']  = $changed_value;
					$item['dirty'] = true;
				}
				$sections[ $index ] = new CardSectionTextOption( $item );
			}
			if ( in_array( $item['section_type'], [ 'static-image', 'input-image' ] ) ) {
				$current_value = $item['imageOptions']['img']['id'];
				if ( ! empty( $changed_value ) && $changed_value != $current_value ) {
					$item['dirty']                     = true;
					$item['imageOptions']['img']['id'] = $changed_value;
				}
				$sections[ $index ] = new CardSectionImageOption( $item );
			}
		}

		$this->card_sections     = $sections;
		$this->card_payload_read = true;
	}

	public function get_total_quantities_in_order(): int {
		$qty = 0;
		foreach ( $this->order->get_items() as $item ) {
			$qty += $item->get_quantity();
		}

		return $qty;
	}

	public function get_designer_logo() {
		$designer_id  = (int) $this->order_item->get_meta( '_card_designer_id', true );
		$card_logo_id = (int) get_user_meta( $designer_id, '_card_logo_id', true );
		$src          = wp_get_attachment_image_src( $card_logo_id, 'full' );
		if ( is_array( $src ) ) {
			return $src;
		}

		return false;
	}

	public function pdf() {
		$fpd = new tFPDF( 'L', 'mm', [ $this->card_width, $this->card_height ] );
		$fpd->AddPage();

		// Add company logo
		$this->addCompanyLogo( $fpd );

		// Add product sku
		$this->addProductSku( $fpd );

		// Add developer logo
		$this->addDesignerLogo( $fpd );

		// Add total qty
		$this->addTotalQty( $fpd );

		// Add qr code
		$this->addQrCode( $fpd );

		// Add background
		$this->addBackground( $fpd );

		// Add sections

		$fpd->Output( $args['dest'] ?? '', $args['name'] ?? '' );
	}

	/**
	 * @param tFPDF $fpd
	 *
	 * @return void
	 */
	private function addCompanyLogo( tFPDF &$fpd ) {
		$logo_path  = YOUSAIDIT_TOOLKIT_PATH . '/assets/static-images/logo-yousaidit.png';
		$image_info = getimagesize( $logo_path );
		$width      = ( $fpd->GetPageWidth() / 2 ) / 3;
		$height     = $image_info[1] / $image_info[0] * $width;
		$x_pos      = ( $fpd->GetPageWidth() / 4 ) - ( $width / 2 );
		$y_pos      = ( $fpd->GetPageHeight() - $height ) - ( 20 );
		$fpd->Image( $logo_path, $x_pos, $y_pos, $width, $height );
	}

	/**
	 * @param tFPDF $fpd
	 *
	 * @return void
	 */
	private function addProductSku( tFPDF &$fpd ) {
		$fpd->SetFont( 'arial', '', 11 );
		$fpd->SetTextColor( 0, 0, 0 );
		$text  = "Code: " . $this->product->get_sku();
		$x_pos = ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 );
		$y_pos = $fpd->GetPageHeight() - 10;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addTotalQty( tFPDF &$fpd ): void {
		$text = sprintf( "%s - %s", $this->get_total_quantities_in_order(), $this->get_ship_station_order_id() );
		$fpd->Text( 10, $fpd->GetPageHeight() - 10, $text );
	}

	/**
	 * @param tFPDF $pdf
	 */
	private function addQrCode( tFPDF &$pdf ) {
		$qr_size = 10;

		$pdf->Image(
			QrCode::get_qr_code_file( $this->get_ship_station_order_id() ), // QR file Path
			( ( $this->card_width / 2 ) - ( $qr_size + 10 ) ), // x position
			( $this->card_height - ( $qr_size + 5 ) ), // y position
			$qr_size, $qr_size, 'jpeg' );
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addDesignerLogo( tFPDF &$fpd ) {
		$designer_logo = $this->get_designer_logo();
		if ( ! is_array( $designer_logo ) ) {
			return;
		}

		$logo_size   = 40;
		$logo_height = $designer_logo[2] / $designer_logo[1] * $logo_size;
		$x_position  = ( $fpd->GetPageWidth() / 4 ) - ( $logo_size / 2 );
		$y_position  = ( $fpd->GetPageHeight() / 4 ) - ( $logo_height / 2 );
		$fpd->Image( $designer_logo[0], $x_position, $y_position, $logo_size, $logo_height );

		$text = "Designed by";
		$fpd->SetFont( 'arial', '', 11 );
		$fpd->SetTextColor( 0, 0, 0 );
		$x_pos = ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 );
		$y_pos = $y_position - 5;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addBackground( tFPDF &$fpd ): void {
		$fpd->Image( $this->background->get_image(), $fpd->GetPageWidth() / 2, 0,
			$fpd->GetPageWidth() / 2, $fpd->GetPageHeight() );
	}
}
