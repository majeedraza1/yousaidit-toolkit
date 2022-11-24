<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use WP_Error;

/**
 * VideoEditor class
 */
class VideoEditor {
	/**
	 * @param UploadedFile $file The uploaded file.
	 * @param string|null $dir The directory to upload.
	 * @param string|null $filename The filename.
	 *
	 * @return int|WP_Error The attachment id on success, or WP_Error object on fails.
	 */
	public static function convert( UploadedFile $file, ?string $dir = null, ?string $filename = null ) {
		if ( ! function_exists( 'shell_exec' ) ) {
			return new WP_Error( 'function_not_found', 'shell_exec function not found' );
		}
		$ffmpeg = trim( shell_exec( 'which ffmpeg' ) );
		if ( empty( $ffmpeg ) ) {
			return new WP_Error( 'ffmpeg_binary_not_found', 'FFMpeg binary not found' );
		}
		$FFProbe = trim( shell_exec( 'which ffprobe' ) );
		if ( empty( $FFProbe ) ) {
			return new WP_Error( 'ffprobe_binary_not_found', 'FFProbe binary not found' );
		}
		// Check if upload directory is writable.
		$upload_dir = Uploader::get_upload_dir( $dir );
		if ( is_wp_error( $upload_dir ) ) {
			return $upload_dir;
		}

		if ( empty( $filename ) ) {
			$extension = pathinfo( $file->get_client_filename(), PATHINFO_EXTENSION );
			$basename  = md5( uniqid( wp_rand(), true ) );
			$filename  = sprintf( '%s.%0.8s', $basename, $extension );
		}

		$new_file_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;

		try {
			$ffmpeg = FFMpeg::create( [
				'ffmpeg.binaries'  => $ffmpeg,
				'ffprobe.binaries' => $FFProbe,
			] );
			$video  = $ffmpeg->open( $file->get_file() );
			$video->save( new X264(), $new_file_path );

			// Set correct file permissions.
			$stat  = stat( dirname( $new_file_path ) );
			$perms = $stat['mode'] & 0000666;
			@chmod( $new_file_path, $perms );

			return Uploader::add_attachment_data( $file, $new_file_path );
		} catch ( Exception $exception ) {
			return new WP_Error( $exception->getCode(), $exception->getMessage() );
		}
	}
}
