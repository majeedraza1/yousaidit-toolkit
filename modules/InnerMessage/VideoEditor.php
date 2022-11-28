<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use finfo;
use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use WP_Error;
use YouSaidItCards\AWSElementalMediaConvert;
use YouSaidItCards\Utils;

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

	public static function copy_video( string $file_url, string $job_id ) {
		$video_id = AWSElementalMediaConvert::job_id_to_video_id( $job_id );
		if ( $video_id ) {
			return $video_id;
		}
		if ( ! class_exists( finfo::class ) ) {
			return new WP_Error( 'php_fileinfo_extension_missing', 'It requires ext-fileinfo extension.' );
		}
		$transient_name = 'video_downloading_' . md5( $job_id );
		$transient      = get_transient( $transient_name );
		if ( false !== $transient ) {
			return new WP_Error( 'process_already_running', 'A process is already running.' );
		}
		set_transient( $transient_name, $job_id, MINUTE_IN_SECONDS * 15 );
		$temp_file = download_url( $file_url );
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}
		$mime_type = ( new finfo() )->file( $temp_file, FILEINFO_MIME_TYPE );
		if ( false === $mime_type ) {
			return new WP_Error( 'invalid_file_format', 'It could not detect file format.' );
		}
		if ( ! in_array( $mime_type, [ 'video/mp4', 'video/x-m4v', 'video/webm' ], true ) ) {
			return new WP_Error( 'unsupported_image_format', 'Unsupported image format.', [
				'file_url'  => $file_url,
				'mime_type' => $mime_type,
			] );
		}
		$file = [
			'name'     => basename( $file_url ),
			'type'     => $mime_type,
			'tmp_name' => $temp_file,
			'error'    => 0,
			'size'     => filesize( $temp_file ),
		];

		// Move the temporary file into the uploads directory.
		$results = wp_handle_sideload(
			$file,
			[
				'test_form'   => false,
				'test_size'   => true,
				'test_upload' => true,
			]
		);

		if ( ! empty( $results['error'] ) ) {
			return new WP_Error( 'fail_to_copy_avatar', $results['error'] );
		}

		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $results['file'] ),
			'post_title'     => pathinfo( $results['file'], PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
			'post_mime_type' => $results['type'],
		];

		$attachment_id = wp_insert_attachment( $data, $results['file'] );

		if ( ! is_wp_error( $attachment_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $results['file'] );
			wp_update_attachment_metadata( $attachment_id, $attach_data );
			$token = wp_generate_password( 20, false, false );
			update_post_meta( $attachment_id, '_delete_token', $token );
			update_post_meta( $attachment_id, '_aws_media_convert_job_id', $job_id );
			if ( strlen( $data['post_title'] ) === 64 ) {
				$filename = $data['post_title'];
			} else {
				$filename = sprintf( '%s--%s--%s',
					Utils::generate_uuid(),
					0,
					Utils::str_rand( 64 - ( 36 + 1 + 4 ) )
				);;
			}
			update_post_meta( $attachment_id, '_video_message_filename', $filename );

			$options = get_option( '_aws_media_convert_' . $job_id );
			if ( is_array( $options ) ) {
				foreach ( $options as $meta_key => $meta_value ) {
					update_post_meta( $attachment_id, $meta_key, $meta_value );
				}
				delete_option( '_aws_media_convert_' . $job_id );
			}
		}

		delete_transient( $transient_name );

		return $attachment_id;
	}
}
