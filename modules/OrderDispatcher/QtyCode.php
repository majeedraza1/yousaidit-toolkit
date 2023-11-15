<?php

namespace YouSaidItCards\Modules\OrderDispatcher;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;
use YouSaidItCards\Utilities\Filesystem;

class QtyCode {
	/**
	 * @param  string|int  $qty
	 * @param $fileName
	 * @param  int  $size
	 *
	 * @return string
	 */
	public static function generate( $qty, $fileName, $size = 96 ): string {
		$image_data = self::get_dynamic_image( $size, (string) $qty );

		$filesystem = Filesystem::update_file_content( $image_data, $fileName );
		if ( $filesystem ) {
			return $fileName;
		}

		return '';
	}

	/**
	 * Get QR code file
	 *
	 * @param  int|string  $qty
	 *
	 * @return string
	 */
	public static function get_qty_code_file( $qty ): string {
		$upload_dir = wp_get_upload_dir();
		$baseDir    = $upload_dir['basedir'] . '/qty-codes/';
		$fileName   = $baseDir . $qty . '.png';

		// Create base directory if not exists
		if ( ! file_exists( $baseDir ) ) {
			wp_mkdir_p( $baseDir );
		}

		// Create QR Image if not exists
		if ( ! file_exists( $fileName ) ) {
			static::generate( $qty, $fileName );
		}

		return $fileName;
	}

	/**
	 * Get dynamic image
	 *
	 * @param  int  $font_size
	 * @param  string  $text
	 *
	 * @return string
	 */
	public static function get_dynamic_image( int $font_size, string $text ): string {
		$image_width  = $font_size * strlen( $text );
		$image_height = $font_size;

		$draw      = new ImagickDraw();
		$font_info = Font::find_font_info( 'Open Sans' );
		if ( $font_info instanceof FontInfo && $font_info->is_valid() ) {
			$draw->setFont( $font_info->get_font_path() );
		}
		$draw->setFontSize( $font_size );
		$draw->setStrokeAntialias( true );
		$draw->setTextAntialias( true );
		$draw->setFillColor( new ImagickPixel( '#000000' ) );

		// The Imagick constructor
		$textOnly = new Imagick();
		// Set transparent background color
		$textOnly->setBackgroundColor( new ImagickPixel( 'transparent' ) );
		// Creates a new image
		$textOnly->newImage( $image_width, $image_height, "none" );
		// Sets the format of this particular image
		$textOnly->setImageFormat( 'png' );
		// Annotates an image with text
		$textOnly->annotateImage( $draw, 0, $font_size, 0, $text );

		// Remove edges from the image
		$textOnly->trimImage( 0 );
		// Sets the page geometry of the image
		$textOnly->setImagePage( $textOnly->getimageWidth(), $textOnly->getimageheight(), 0, 0 );

		// Sets the image virtual pixel method
		$textOnly->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
		// Sets the image matte channel
		$textOnly->setImageMatte( true );

		// Sets the format of the Imagick object
		$textOnly->setformat( 'png' );

		return $textOnly->getimageblob();
	}
}
