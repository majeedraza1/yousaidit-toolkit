<?php

namespace YouSaidItCards\Utilities;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;

class ImagickUtils {

	/**
	 * Get imagick canvas
	 *
	 * @param  int  $width_px  Image width in pixels.
	 * @param  int  $height_px  Image height in pixels.
	 * @param  int  $ppi  Image resolution. 72 for web preview. 300 for print.
	 *
	 * @return Imagick
	 * @throws ImagickException
	 */
	public function getImagickCanvas( int $width_px, int $height_px, int $ppi = 300 ): Imagick {
		$ppi   = max( 72, min( 300, $ppi ) );
		$image = new Imagick();
		$image->setResolution( $ppi, $ppi );
		$image->setSize( $width_px, $height_px );
		$image->newImage( $width_px, $height_px, new ImagickPixel( 'transparent' ) );
		$image->setImageFormat( "png" );

		return $image;
	}

	public static function mm_to_px( float $value, int $ppi = 72 ): float {
		return round( $value / 25.4 * $ppi );
	}

	public static function px_to_mm( float $pixels, int $ppi = 72 ): float {
		return round( $pixels * ( 25.4 / $ppi ), 2 );
	}

	public static function font_size_pt_to_px( int $font_size, int $ppi = 72 ): float {
		return round( $font_size * $ppi / 72 );
	}

	/**
	 * Get font metrics
	 *
	 * @param  string  $text  The string to test for font metrics.
	 * @param  string  $font_family  The font family.
	 * @param  int  $font_size  The font size.
	 *
	 * @return array|false {
	 * Array of font metrics info
	 *
	 * @type float $characterWidth maximum character ("em") width
	 * @type float $characterHeight maximum character height
	 * @type float $ascender the height of character ascensions (i.e. the straight bit on a 'b')
	 * @type float $descender the height of character descensions (i.e. the straight bit on a 'p')
	 * @type float $textWidth width of drawn text in pixels
	 * @type float $textHeight height of drawn text in pixels
	 * }
	 */
	public static function get_font_metrics(
		string $font_family,
		int $font_size,
		string $text = '',
		int $resolution = 300
	) {
		if ( empty( $text ) ) {
			$text = 'A quick brown fox jumps over the lazy dogs.';
		}
		$font_info = Font::find_font( $font_family );
		if ( ! $font_info instanceof FontInfo ) {
			return false;
		}
		try {
			$im = new Imagick();
			$im->setResolution( $resolution, $resolution );
			$draw = new ImagickDraw();
			$draw->setFont( $font_info->get_font_path() );
			$draw->setFontSize( $font_size );

			return $im->queryFontMetrics( $draw, $text );
		} catch ( ImagickDrawException|ImagickException $e ) {
			Logger::log( $e );

			return false;
		}
	}

	/**
	 * @param  string  $text
	 * @param  string  $font_family
	 * @param  int  $font_size
	 * @param  int  $resolution
	 *
	 * @return int[] The width and height of drawn text in pixels.
	 */
	public static function get_string_size(
		string $text,
		string $font_family,
		int $font_size,
		int $resolution = 300
	): array {
		$info = static::get_font_metrics( $font_family, $font_size, $text, $resolution );
		if ( is_array( $info ) && isset( $info['textWidth'], $info['textHeight'] ) ) {
			return [
				ceil( $info['textWidth'] ),
				ceil( $info['textHeight'] )
			];
		}

		return [ 0, 0 ];
	}

	/**
	 * Scale down font size base on max content width
	 *
	 * @param  array  $lines
	 * @param  float  $max_content_width
	 * @param  string  $font_family
	 * @param  int  $font_size
	 * @param  int  $resolution
	 *
	 * @return int|float
	 */
	public static function get_computed_font_size_for_text_box_width(
		array $lines,
		float $max_content_width,
		string $font_family,
		int $font_size,
		int $resolution = 300
	) {
		$_font_size = $font_size;
		foreach ( $lines as $line ) {
			list( $string_width ) = static::get_string_size( $line, $font_family, $font_size, $resolution );
			if ( $string_width > $max_content_width ) {
				$_font_size = static::get_computed_font_size_for_text_box_width( $lines, $max_content_width,
					$font_family, $font_size - 4, $resolution );
			}
		}

		return $_font_size;
	}

	/**
	 * Scale down font size base on max content width
	 *
	 * @param  array  $lines
	 * @param  float  $max_content_height
	 * @param  string  $font_family
	 * @param  int  $font_size
	 * @param  int  $resolution
	 *
	 * @return int|float
	 */
	public static function get_computed_font_size_for_text_box_height(
		array $lines,
		float $max_content_height,
		string $font_family,
		int $font_size,
		int $resolution = 300
	) {
		$_font_size  = $font_size;
		$text_height = 0;
		foreach ( $lines as $line ) {
			list( $string_width, $string_height ) = static::get_string_size( $line, $font_family, $font_size,
				$resolution );
			$text_height += $string_height;
		}

		if ( $text_height > $max_content_height ) {
			$_font_size = static::get_computed_font_size_for_text_box_width( $lines, $max_content_height, $font_family,
				$font_size - 8, $resolution );
		}

		return $_font_size;
	}
}
