<?php

namespace YouSaidItCards;

use tFPDF;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;
use YouSaidItCards\Utilities\FreePdfBase;

defined( 'ABSPATH' ) || exit;

class FreePdf extends FreePdfBase {

	/**
	 * @param  string|array  $pdf_size
	 * @param  array  $layers
	 * @param  array  $bg
	 * @param  array  $args
	 */
	public function generate( $pdf_size, array $layers, array $bg = [], array $args = [] ) {
		$this->set_size( $pdf_size );
		$this->set_background( $bg );
		$this->set_layers( $layers );

		$items      = $this->get_layers();
		$background = $this->get_background();

		$size   = $this->get_size();
		$option = $this->get_option();

		$fpd = new FreePdfExtended( 'P', 'mm', [ $option['front_width'], $option['height'] ] );

		// Add custom fonts
		$added_fonts = [];
		foreach ( $items as $item ) {
			if ( ! in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				continue;
			}
			$font = Font::find_font_info( $item['textOptions']['fontFamily'] );
			if ( ! $font instanceof FontInfo ) {
				continue;
			}
			if ( in_array( $font->get_font_family_for_dompdf(), $added_fonts ) ) {
				continue;
			}
			$added_fonts[] = $font->get_font_family_for_dompdf();
			$fpd->AddFont( $font->get_font_family_for_dompdf(), '', $font->get_font_file(), true );
		}

		$fpd->AddPage();

		// Set PDF background color
		$color = $background['color'] ?? '';
		if ( 'color' == $background['type'] ) {
			$bg_color_image = add_query_arg( [
				'action' => 'yousaidit_color_image',
				'w'      => $size[0] / 2,
				'h'      => $size[1],
				'c'      => rawurlencode( $color )
			], admin_url( 'admin-ajax.php' ) );
			$fpd->Image( $bg_color_image, 0, 0, $fpd->GetPageWidth(), $fpd->GetPageHeight(), 'png' );
		}

		if ( 'image' == $background['type'] ) {
			$image_id = $background['image']['id'] ?? 0;
			$src      = wp_get_attachment_image_src( $image_id, 'full' );
			if ( is_array( $src ) ) {
				$fpd->Image( $src[0], 0, 0, $fpd->GetPageWidth(), $fpd->GetPageHeight(), '' );
			}
		}

		// Add sections
		foreach ( $items as $item ) {
			if ( in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				$this->add_text( $fpd, $item );
			}
			if ( in_array( $item['section_type'], [ 'static-image', 'input-image' ] ) ) {
				$this->add_image( $fpd, $item );
			}
		}

		$fpd->Output( $args['dest'] ?? '', $args['name'] ?? '' );
	}

	/**
	 * @param  tFPDF  $fpd
	 * @param  array  $item
	 */
	public function add_text( FreePdfExtended &$fpd, array $item ) {
		$textOptions = $item['textOptions'] ?? [];
		$font_size   = intval( $textOptions['size'] );
		$font_family = str_replace( ' ', '', $textOptions['fontFamily'] );
		$text_align  = strtolower( $textOptions['align'] );
		$marginRight = isset( $textOptions['marginRight'] ) ? intval( $textOptions['marginRight'] ) : 0;
		$text        = ! empty( $item['text'] ) ? sanitize_text_field( $item['text'] ) : $item['placeholder'];
		$x_pos       = intval( $item['position']['left'] );
		// Fix y-pos as text start from baseline
		$y_pos = (int) ( intval( $item['position']['top'] ) + self::points_to_mm( $font_size * 0.75 ) );
		list( $red, $green, $blue ) = self::find_rgb_color( $textOptions['color'] );
		$fpd->SetFont( $font_family, '', $font_size );
		$fpd->SetTextColor( $red, $green, $blue );
		if ( 'center' == $text_align ) {
			$x_pos = $fpd->GetPageWidth() / 2 - $fpd->GetStringWidth( $text ) / 2;
		}
		if ( 'right' == $text_align ) {
			$x_pos = $fpd->GetPageWidth() - ( $fpd->GetStringWidth( $text ) + $marginRight );
		}

		$rotation = 0;
		$spacing  = 0;
		if ( isset( $textOptions['rotation'] ) && is_numeric( $textOptions['rotation'] ) ) {
			$rotation = intval( $textOptions['rotation'] );
		}
		if ( isset( $textOptions['spacing'] ) && is_numeric( $textOptions['spacing'] ) ) {
			$spacing = intval( $textOptions['spacing'] );
		}
		if ( $spacing ) {
			$fpd->SetFontSpacing( $spacing );
		}
		if ( $rotation ) {
			$fpd->RotatedText( $x_pos, $y_pos, $text, $rotation );
		} else {
			$fpd->Text( $x_pos, $y_pos, $text );
		}
	}

	/**
	 * Add Image
	 *
	 * @param  tFPDF  $fpd
	 * @param  array  $item
	 */
	private function add_image( tFPDF $fpd, array $item ) {
		$option = $this->get_option();
		$x_pos  = intval( $item['position']['left'] ); // + $option['left_margin']
		$y_pos  = intval( $item['position']['top'] ); //  + $option['top_margin']
		$height = 'auto'; //$item['imageOptions']['height'];
//		$height      = 'auto' == $height ? 'auto' : intval( $height );
		$imageOptions = $item['imageOptions'] ?? [];
		$marginRight  = isset( $imageOptions['marginRight'] ) ? intval( $imageOptions['marginRight'] ) : 0;
		$image_id     = intval( $imageOptions['img']['id'] );
		$width        = intval( $imageOptions['width'] );
		$align        = intval( $imageOptions['align'] );
		$src          = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! is_array( $src ) ) {
			return;
		}
		$image        = get_attached_file( $image_id );
		$actual_width = self::px_to_mm( $src[1] );
		if ( $actual_width < $width ) {
			$width = $actual_width;
		}
		$actual_height = self::px_to_mm( $src[2] );
		if ( 'auto' == $height ) {
			$height = $width * ( $actual_height / $actual_width );
		}
		if ( 'center' == $align ) {
			$x_pos = $fpd->GetPageWidth() / 2 - $width / 2;
		}
		if ( 'right' == $align ) {
			$x_pos = $fpd->GetPageWidth() - ( $width + $marginRight );
		}
		$fpd->Image( $src[0], $x_pos, $y_pos, $width, intval( $height ) );
	}
}
