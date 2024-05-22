<?php

namespace YouSaidItCards\Modules\OrderDispatcher;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Zxing\QrReader;

class QrCode {
	/**
	 * @param string $code
	 * @param string $filePath
	 * @param int $size
	 *
	 * @return Writer
	 */
	public static function generate( $code, $filePath, $size = 400 ) {
		$renderer = new ImageRenderer( new RendererStyle( $size ), new ImagickImageBackEnd( 'jpeg' ) );
		$writer   = new Writer( $renderer );
		$writer->writeFile( $code, $filePath );

		return $writer;
	}

	/**
	 * Generate video message QR code
	 *
	 * @param int $item_id
	 * @param string $url
	 * @param int $size
	 *
	 * @return array
	 */
	public static function generate_video_message( string $url, int $size = 400 ): array {
		$upload_dir   = wp_get_upload_dir();
		$baseDir      = $upload_dir['basedir'] . '/qr-codes-video-message/';
		$qr_code_path = $baseDir . basename( $url ) . '.jpg';

		// Create base directory if not exists
		if ( ! file_exists( $baseDir ) ) {
			wp_mkdir_p( $baseDir );
		}

		// Create QR Image if not exists
		if ( ! file_exists( $qr_code_path ) ) {
			static::generate( $url, $qr_code_path, $size );
		}

		$qr_code_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $qr_code_path );

		return [
			'path' => $qr_code_path,
			'url'  => $qr_code_url,
		];
	}

	/**
	 * @param $filePath
	 *
	 * @return string
	 */
	public static function read( $filePath ) {
		$qrReader = new QrReader( $filePath );

		return $qrReader->text();
	}

	/**
	 * Get QR code file
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public static function get_qr_code_file( $id ) {
		$order_id = intval( $id );

		$upload_dir = wp_get_upload_dir();
		$baseDir    = $upload_dir['basedir'] . '/qr-codes/';
		$fileName   = $baseDir . $order_id . '.jpg';

		// Create base directory if not exists
		if ( ! file_exists( $baseDir ) ) {
			wp_mkdir_p( $baseDir );
		}

		// Create QR Image if not exists
		if ( ! file_exists( $fileName ) ) {
			static::generate( $order_id, $fileName );
		}

		return $fileName;
	}
}
