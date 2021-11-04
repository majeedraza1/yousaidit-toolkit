<?php

namespace YouSaidItCards;

use tFPDF;
use YouSaidItCards\Modules\InnerMessage\Fonts;
use YouSaidItCards\Utilities\FreePdfBase;

defined( 'ABSPATH' ) || exit;

class FreePdf extends FreePdfBase {

	/**
	 * @param string|array $pdf_size
	 * @param array $layers
	 * @param array $bg
	 * @param array $args
	 */
	public function generate( $pdf_size, array $layers, array $bg = [], array $args = [] ) {
		$this->set_size( $pdf_size );
		$this->set_background( $bg );
		$this->set_layers( $layers );

		$items      = $this->get_layers();
		$background = $this->get_background();

		$size = $this->get_size();

		$fpd = new tFPDF( 'P', 'mm', [ $size[0] / 2, $size[1] ] );

		// Add custom fonts
		$fonts_list  = Fonts::get_list();
		$added_fonts = [];
		foreach ( $items as $item ) {
			if ( ! in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				continue;
			}
			$font_family = str_replace( ' ', '', $item['textOptions']['fontFamily'] );
			if ( in_array( $font_family, $added_fonts ) ) {
				continue;
			}
			$added_fonts[] = $font_family;
			$font          = $fonts_list[ $font_family ];
			$fpd->AddFont( $font_family, '', $font['fileName'], true );
		}

		$fpd->AddPage();

		// Set PDF background color
		$color = $background['color'] ?? '';
		if ( 'color' == $background['type'] && ! in_array( $color, [ 'white', '#fff', '#ffffff' ], true ) ) {
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
	 * @param tFPDF $fpd
	 * @param array $item
	 */
	public function add_text( tFPDF &$fpd, array $item ) {
		$font_size   = intval( $item['textOptions']['size'] );
		$font_family = str_replace( ' ', '', $item['textOptions']['fontFamily'] );
		$text_align  = strtolower( $item['textOptions']['align'] );
		$marginRight = intval( $item['textOptions']['marginRight'] );
		$text        = ! empty( $item['text'] ) ? sanitize_text_field( $item['text'] ) : $item['placeholder'];
		$x_pos       = intval( $item['position']['left'] );
		// Fix y-pos as text start from baseline
		$y_pos = (int) ( intval( $item['position']['top'] ) + self::points_to_mm( $font_size * 0.75 ) );
		list( $red, $green, $blue ) = self::find_rgb_color( $item['textOptions']['color'] );
		$fpd->SetFont( $font_family, '', $font_size );
		$fpd->SetTextColor( $red, $green, $blue );
		if ( 'center' == $text_align ) {
			$x_pos = $fpd->GetPageWidth() / 2 - $fpd->GetStringWidth( $text ) / 2;
		}
		if ( 'right' == $text_align ) {
			$x_pos = $fpd->GetPageWidth() - ( $fpd->GetStringWidth( $text ) + $marginRight );
		}

		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * Add Image
	 *
	 * @param tFPDF $fpd
	 * @param array $item
	 */
	private function add_image( tFPDF $fpd, array $item ) {
		$x_pos  = intval( $item['position']['left'] );
		$y_pos  = intval( $item['position']['top'] );
		$height = 'auto'; //$item['imageOptions']['height'];
//		$height      = 'auto' == $height ? 'auto' : intval( $height );
		$marginRight = intval( $item['imageOptions']['marginRight'] );
		$image_id    = intval( $item['imageOptions']['img']['id'] );
		$width       = intval( $item['imageOptions']['width'] );
		$align       = intval( $item['imageOptions']['align'] );
		$src         = wp_get_attachment_image_src( $image_id, 'full' );
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
		$fpd->Image( $image, $x_pos, $y_pos, $width, intval( $height ) );
	}
}
