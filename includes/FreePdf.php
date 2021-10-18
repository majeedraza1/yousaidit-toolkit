<?php

namespace YouSaidItCards;

use tFPDF;
use YouSaidItCards\Modules\InnerMessage\Fonts;

defined( 'ABSPATH' ) || exit;

class FreePdf {

	/**
	 * Card sizes
	 *
	 * @var array
	 */
	protected $sizes = [
		'square' => [ 300, 150 ],
		'a4'     => [ 426, 303 ],
		'a5'     => [ 303, 216 ],
		'a6'     => [ 216, 154 ],
	];

	/**
	 * @var array
	 */
	protected $size = [];

	/**
	 * @return array
	 */
	public function get_size(): array {
		if ( empty( $this->size ) ) {
			$this->size = $this->sizes['square'];
		}

		return $this->size;
	}

	/**
	 * @param string $size
	 */
	public function set_size( string $size ): void {
		if ( array_key_exists( $size, $this->sizes ) ) {
			$this->size = $this->sizes[ $size ];
		}
	}

	/**
	 * @param string|array $pdf_size
	 * @param array $items
	 * @param array $background
	 */
	public function generate( $pdf_size, array $items, array $background = [] ) {
		$this->set_size( $pdf_size );

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
		$fpd->Output();
	}

	/**
	 * @param tFPDF $fpd
	 * @param array $item
	 */
	public function add_text( tFPDF &$fpd, array $item ) {
		$item        = wp_parse_args( $item, [
			'label'        => 'Section 1',
			'section_type' => 'static-text',
			'position'     => [ 'top' => 0, 'left' => 0 ],
			'text'         => '',
			'placeholder'  => '',
			'textOptions'  => [
				'fontFamily'  => 'Arial',
				'size'        => 16,
				'align'       => 'left',
				'color'       => '#323232',
				'marginRight' => 0
			]
		] );
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
		$item   = wp_parse_args( $item, [
			'label'        => 'Section 1',
			'section_type' => 'static-image',
			'position'     => [ 'top' => 0, 'left' => 0 ],
			'imageOptions' => [
				'img'         => [ 'id' => 0 ],
				'width'       => 10,
				'height'      => 'auto',
				'align'       => 'left',
				'marginRight' => 0
			]
		] );
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

	/**
	 * Find RGB color from a color
	 *
	 * @param string $color
	 *
	 * @return string|array
	 */
	private static function find_rgb_color( string $color ) {
		if ( '' === $color ) {
			return '';
		}

		// Trim unneeded whitespace
		$color = str_replace( ' ', '', $color );

		// 3 or 6 hex digits, or the empty string.
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			// Format the hex color string.
			$hex = str_replace( '#', '', $color );

			if ( 3 == strlen( $hex ) ) {
				$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) .
				       str_repeat( substr( $hex, 1, 1 ), 2 ) .
				       str_repeat( substr( $hex, 2, 1 ), 2 );
			}

			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );

			return array( $r, $g, $b, 1 );
		}

		// If this is rgb color
		if ( 'rgb(' === substr( $color, 0, 4 ) ) {
			list( $r, $g, $b ) = sscanf( $color, 'rgb(%d,%d,%d)' );

			return array( $r, $g, $b, 1 );
		}

		// If this is rgba color
		if ( 'rgba(' === substr( $color, 0, 5 ) ) {
			list( $r, $g, $b, $alpha ) = sscanf( $color, 'rgba(%d,%d,%d,%f)' );

			return array( $r, $g, $b, $alpha );
		}

		return '';
	}

	/**
	 * Convert millimeters to pixels
	 *
	 * @param int|float $mm
	 *
	 * @return float
	 */
	public static function mm_to_px( float $mm ): float {
		return round( $mm * 3.7795275591 );
	}

	public static function px_to_mm( float $px ): float {
		return round( $px * 0.2645833333 );
	}

	public static function points_to_mm( float $points ): float {
		return round( $points * 0.352778 );
	}
}
