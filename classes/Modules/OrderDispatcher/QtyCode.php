<?php

namespace Yousaidit\Modules\OrderDispatcher;

use Yousaidit\Modules\InnerMessage\Fonts;
use YouSaidItCards\Utilities\Filesystem;

class QtyCode {
	/**
	 * @param string|int $qty
	 * @param $fileName
	 * @param int $size
	 *
	 * @return string
	 */
	public static function generate( $qty, $fileName, $size = 96 ) {
		$path = Fonts::get_font_path( 'Open Sans' );
		$draw = new \ImagickDraw();
		$draw->setFont( $path );
		$draw->setFontSize( $size );
		$draw->setStrokeAntialias( true );
		$draw->setTextAntialias( true );
		$draw->setFillColor( new \ImagickPixel( '#000000' ) );

		// The Imagick constructor
		$textOnly = new \Imagick();
		// Set transparent background color
		$textOnly->setBackgroundColor( new \ImagickPixel( 'transparent' ) );
		// Creates a new image
		$textOnly->newImage( $size, $size, "none" );
		// Sets the format of this particular image
		$textOnly->setImageFormat( 'png' );
		// Annotates an image with text
		$textOnly->annotateImage( $draw, 0, $size, 0, (string) $qty );

		// Remove edges from the image
		$textOnly->trimImage( 0 );
		// Sets the page geometry of the image
		$textOnly->setImagePage( $textOnly->getimageWidth(), $textOnly->getimageheight(), 0, 0 );

		// Sets the image virtual pixel method
		$textOnly->setImageVirtualPixelMethod( \Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
		// Sets the image matte channel
		$textOnly->setImageMatte( true );

		// Sets the format of the Imagick object
		$textOnly->setformat( 'png' );

		$image_data = $textOnly->getimageblob();

		$filesystem = Filesystem::update_file_content( $image_data, $fileName );
		if ( $filesystem ) {
			return $fileName;
		}

		return '';
	}

	/**
	 * Get QR code file
	 *
	 * @param int|string $qty
	 *
	 * @return string
	 */
	public static function get_qty_code_file( $qty ) {
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
}
