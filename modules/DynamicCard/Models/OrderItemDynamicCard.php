<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Supports\Validate;
use tFPDF;
use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Assets;
use YouSaidItCards\FreePdfExtended;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\Utilities\FreePdfBase;
use YouSaidItCards\Utils;

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
	/**
	 * @var CardSectionTextOption[]|CardSectionImageOption[]
	 */
	protected $card_sections = [];
	protected $order;
	protected $order_item;
	protected $product;
	protected $card_payload_read = false;

	public function __construct( WC_Order $order, WC_Order_Item_Product $order_item ) {
		$this->order           = $order;
		$this->order_item      = $order_item;
		$this->product         = $order_item->get_product();
		$this->card_id         = (int) $this->product->get_meta( '_card_id', true );
		$this->ship_station_id = (int) $this->product->get_meta( '_shipstation_order_id', true );

		$this->read_card_payload();
		$this->read_ship_station_id();
	}

	public function get_ship_station_id(): int {
		return $this->ship_station_id;
	}

	/**
	 * @param  int  $ship_station_id
	 * @param  bool  $update_order
	 */
	public function set_ship_station_id( int $ship_station_id, bool $update_order = false ) {
		$this->ship_station_id = $ship_station_id;
		if ( $update_order ) {
			$this->order->update_meta_data( '_shipstation_order_id', $ship_station_id );
			$this->order->save_meta_data();
		}
	}

	public function read_ship_station_id() {
		if ( $this->get_ship_station_id() ) {
			return;
		}
		$shipstation_orders = Order::get_orders( [ 'force' => true ] );
		/** @var Order $item */
		foreach ( $shipstation_orders['items'] as $shipstation_order_item ) {
			if ( $this->order->get_id() != $shipstation_order_item->get_wc_order_id() ) {
				continue;
			}
			$this->set_ship_station_id( $shipstation_order_item->get_id(), true );
		}
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

		$payload = $this->order_item->get_meta( '_dynamic_card_payload', true );
		if ( ! empty( $payload ) ) {
			$payload = json_decode( $payload, true );
		}
		if ( empty( $payload ) ) {
			$payload = $this->product->get_meta( '_dynamic_card_payload', true );
		}
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

	public function pdf( string $filename = '', string $dest = 'I', array $args = [] ) {
		$is_debugging = isset( $args['debug'] ) && Validate::checked( $args['debug'] );

		$fpd = new FreePdfExtended( 'L', 'mm', [ $this->card_width, $this->card_height ] );

		// Add custom fonts
		$this->addCustomFonts( $fpd );

		// Add page
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
		$this->addSections( $fpd );

		if ( 'production' !== wp_get_environment_type() || $is_debugging ) {
			// Draw rect on back side
			$fpd->SetAlpha( .05 );
			$fpd->SetLineWidth( 0 );
			$fpd->SetFillColor( 255, 0, 0 );
			$fpd->Rect( 0, 0, $fpd->GetPageWidth() - 154, $fpd->GetPageHeight(), 'DF' );
			$fpd->SetAlpha( 1 );

			// Output to browser
			$fpd->Output();
			die;
		} else {
			$fpd->Output( $dest, $filename );
		}
	}

	/**
	 * @param  tFPDF  $fpd
	 *
	 * @return void
	 */
	private function addCompanyLogo( tFPDF &$fpd ) {
		$logo_path  = Assets::get_asset_path( 'static-images/logo-yousaidit@300ppi.jpg' );
		$image_info = [ 660, 292 ];
		$width      = ( $fpd->GetPageWidth() / 2 ) / 3;
		$height     = $image_info[1] / $image_info[0] * $width;
		$x_pos      = ( ( $fpd->GetPageWidth() / 4 ) - ( $width / 2 ) ) + 3; // 3mm bleed
		$y_pos      = ( $fpd->GetPageHeight() - $height ) - ( 20 );
		$fpd->Image( $logo_path, $x_pos, $y_pos, $width, $height );
	}

	/**
	 * @param  tFPDF  $fpd
	 *
	 * @return void
	 */
	private function addProductSku( tFPDF &$fpd ) {
		$fpd->SetFont( 'arial', '', 10 );
		$fpd->SetTextColor( 0, 0, 0 );
		$text  = "Code: " . $this->product->get_sku();
		$x_pos = ( ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 ) ) + 3; // 3mm bleed
		$y_pos = $fpd->GetPageHeight() - 10;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @param  tFPDF  $fpd
	 */
	private function addTotalQty( tFPDF &$fpd ): void {
		$text  = sprintf( "%s - %s", $this->get_total_quantities_in_order(), $this->get_ship_station_id() );
		$x_pos = 10 + 3; // 3mm bleed
		$fpd->Text( $x_pos, $fpd->GetPageHeight() - 10, $text );
	}

	/**
	 * @param  tFPDF  $pdf
	 */
	private function addQrCode( tFPDF &$pdf ) {
		$qr_size = 10;

		$x_pos = ( ( ( $this->card_width / 2 ) - ( $qr_size + 10 ) ) ) + 3; // 3mm bleed

		$pdf->Image(
			QrCode::get_qr_code_file( $this->get_ship_station_id() ), // QR file Path
			$x_pos, // x position
			( $this->card_height - ( $qr_size + 5 ) ), // y position
			$qr_size, $qr_size, 'jpeg' );
	}

	/**
	 * @param  tFPDF  $fpd
	 */
	private function addDesignerLogo( tFPDF &$fpd ) {
		$designer_logo = $this->get_designer_logo();
		if ( ! is_array( $designer_logo ) ) {
			return;
		}

		$logo_size   = 40;
		$logo_height = $designer_logo[2] / $designer_logo[1] * $logo_size;
		$x_position  = ( ( $fpd->GetPageWidth() / 4 ) - ( $logo_size / 2 ) ) + 3; // 3mm bleed
		$y_position  = ( $fpd->GetPageHeight() / 4 ) - ( $logo_height / 2 );
		$fpd->Image( $designer_logo[0], $x_position, $y_position, $logo_size, $logo_height );

		$text = "Designed by";
		$fpd->SetFont( 'arial', '', 11 );
		$fpd->SetTextColor( 0, 0, 0 );
		$x_pos = ( ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 ) ) + 3; // 3mm bleed
		$y_pos = $y_position - 5;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @param  tFPDF  $fpd
	 */
	private function addBackground( tFPDF &$fpd ): void {
		if ( ! $this->background instanceof CardBackgroundOption ) {
			return;
		}

		$width     = Utils::SQUARE_CARD_WIDTH_MM;
		$height    = Utils::SQUARE_CARD_HEIGHT_MM;
		$x_pos     = $fpd->GetPageWidth() - $width;
		$y_pos     = 0;
		$image_src = $this->background->get_image_src();
		if ( empty( $image_src ) ) {
			return;
		}
		// @TODO check it
		$fpd->Image( $image_src, $x_pos, $y_pos, $width, $height, $this->background->get_image_extension() );
	}

	private function addSections( FreePdfExtended &$fpd ) {
		foreach ( $this->card_sections as $index => $section ) {
			if ( in_array( $section['section_type'], [ 'static-text', 'input-text' ] ) ) {
				$this->addTextSection( $fpd, $section );
			}
			if ( in_array( $section['section_type'], [ 'static-image', 'input-image' ] ) ) {
				$this->addImageSection( $fpd, $section );
			}
		}
	}

	private function addTextSection( FreePdfExtended &$fpd, CardSectionTextOption $section ) {
		list( $red, $green, $blue ) = FreePdfBase::find_rgb_color( $section->get_text_option( 'color' ) );
		$fpd->SetTextColor( $red, $green, $blue );
		$font = Font::find_font_info( $section->get_font_family() );
		$fpd->SetFont( $font->get_font_family_for_dompdf(), '', $section->get_text_option( 'size' ) );

		$text_width = $fpd->GetStringWidth( $section->get_text() );
		$y_pos      = $section->get_position_from_top_mm() + FreePdfBase::points_to_mm( $section->get_text_option( 'size' ) );


		$back_width = $fpd->GetPageWidth() - Utils::SQUARE_CARD_WIDTH_MM;

		$x_pos = $back_width + $section->get_position_from_left_mm();
		if ( 'center' == $section->get_text_option( 'align' ) ) {
			$x_pos = ( $back_width + ( Utils::SQUARE_CARD_WIDTH_MM / 2 ) ) - ( $text_width / 2 );
		}
		if ( 'right' == $section->get_text_option( 'align' ) ) {
			$x_pos = $fpd->GetPageWidth() - ( $text_width + $section->get_text_option( 'marginRight' ) );
		}

		if ( $section->get_text_spacing() ) {
			$fpd->SetFontSpacing( $section->get_text_spacing() );
		}
		$fpd->RotatedText( $x_pos, $y_pos, $section->get_text(), $section->get_rotation() );
	}

	private function addImageSection( FreePdfExtended &$fpd, CardSectionImageOption $section ) {
		$image = $section->get_image();
		if ( ! is_array( $image ) ) {
			return;
		}

		$image_area_width  = $section->get_image_area_width_mm();
		$image_area_height = $section->get_image_area_height_mm();
		$image_width       = $section->get_image_width_mm();
		$image_height      = $section->get_image_height_mm();

		$back_width = $fpd->GetPageWidth() - Utils::SQUARE_CARD_WIDTH_MM;


		$x_pos = $back_width + $section->get_position_from_left_mm();
		if ( $section->is_image_alignment_center() ) {
			$x_pos = ( $back_width + ( Utils::SQUARE_CARD_WIDTH_MM / 2 ) ) - ( $image_area_width / 2 );
		}
		if ( $section->is_image_alignment_right() ) {
			$x_pos = $fpd->GetPageWidth() - ( $image_area_width + $section->get_image_option( 'marginRight' ) );
		}

		$y_pos = $section->get_position_from_top_mm();

		$user_x_pos = $section->get_user_position_from_left_mm();
		if ( $user_x_pos ) {
			$x_pos            += $user_x_pos;
			$image_area_width -= $user_x_pos;
		}

		$user_y_pos = $section->get_user_position_from_top_mm();
		if ( $user_y_pos ) {
			$y_pos             += $user_y_pos;
			$image_area_height -= $user_y_pos;
		}

		$zoom        = $section->get_user_zoom();
		$zoom_width  = ( $image_area_width * absint( $zoom ) / 100 );
		$zoom_height = ( $image_area_height * absint( $zoom ) / 100 );
		if ( $zoom > 0 ) {
			$image_area_width  += $zoom_width;
			$image_area_height += $zoom_height;
		} elseif ( $zoom < 0 ) {
			$image_area_width  -= $zoom_width;
			$image_area_height -= $zoom_height;
		}

		$rotation = $section->get_user_rotation();
		if ( $rotation ) {
			$fpd->RotatedImage(
				$section->get_image_url(),
				$x_pos,
				$y_pos,
				min( $image_area_width, 154 ),
				min( $image_area_height, 156 ),
				$section->get_user_rotation()
			);
		} else {
			$image_type = '';
			if ( $section->is_dynamic_image() ) {
				$x_pos      = $back_width;
				$y_pos      = 0;
				$image_type = 'png';
			}
			$fpd->Image(
				$section->get_dynamic_image_url(),
				$x_pos,
				$y_pos,
				min( $image_area_width, 154 ),
				min( $image_area_height, 156 ),
				$image_type
			);
		}
	}

	/**
	 * @param  tFPDF  $fpd
	 */
	private function addCustomFonts( tFPDF &$fpd ) {
		$added_fonts = [];
		foreach ( $this->card_sections as $item ) {
			if ( ! $item instanceof CardSectionTextOption ) {
				continue;
			}

			$font = Font::find_font_info( $item->get_font_family() );
			if ( ! $font instanceof FontInfo ) {
				continue;
			}
			if ( in_array( $font->get_font_family_for_dompdf(), $added_fonts ) ) {
				continue;
			}
			$added_fonts[] = $font->get_font_family_for_dompdf();
			$fpd->AddFont( $font->get_font_family_for_dompdf(), '', $font->get_font_file(), true );
		}
	}

	public function get_background(): CardBackgroundOption {
		return $this->background;
	}

	/**
	 * @return CardSectionBase[]
	 */
	public function get_card_sections(): array {
		return $this->card_sections;
	}
}
