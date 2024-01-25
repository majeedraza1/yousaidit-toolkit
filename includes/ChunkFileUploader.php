<?php

namespace YouSaidItCards;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Supports\Logger;
use WP_Error;

/**
 * ChunkFileUploader
 */
class ChunkFileUploader {
	/**
	 * Upload file by chunk
	 *
	 * @param UploadedFile $file
	 * @param array $additional_info
	 *
	 * @return WP_Error|UploadedFile|int
	 */
	public static function upload( UploadedFile $file, array $additional_info = [] ) {
		$additional_info = wp_parse_args( $additional_info, [
			'chunk'  => 0,
			'chunks' => 0,
			'name'   => '',
		] );

		$request_key = 'file';
		/** Check and get file chunks. */
		$chunk        = isset( $additional_info['chunk'] ) ? intval( $additional_info['chunk'] ) : 0; //zero index
		$current_part = $chunk + 1;
		$chunks       = isset( $additional_info['chunks'] ) ? intval( $additional_info['chunks'] ) : 0;

		/** Get file name and path + name. */
		$file_name = ! empty( $additional_info['name'] ) ? $additional_info['name'] : $file->get_client_filename();

		// Temp upload directory
		$bfu_temp_dir = WP_CONTENT_DIR . '/big-file-uploads-temp';
		//only run on first chunk
		if ( 0 === $chunk ) {
			// Create temp directory if it doesn't exist
			if ( ! @is_dir( $bfu_temp_dir ) ) {
				wp_mkdir_p( $bfu_temp_dir );
			}

			// Protect temp directory from browsing.
			$index_pathname = $bfu_temp_dir . '/index.php';
			if ( ! file_exists( $index_pathname ) ) {
				$_file = fopen( $index_pathname, 'w' );
				if ( false !== $_file ) {
					fwrite( $_file, "<?php\n// Silence is golden.\n" );
					fclose( $_file );
				}
			}

			//scan temp dir for files older than 24 hours and delete them.
			$files = glob( $bfu_temp_dir . '/*.part' );
			if ( is_array( $files ) ) {
				foreach ( $files as $_file ) {
					if ( @filemtime( $_file ) < time() - DAY_IN_SECONDS ) {
						@unlink( $_file );
					}
				}
			}
		}

		$filePath = sprintf( '%s/%d-%s.part', $bfu_temp_dir, get_current_blog_id(), sha1( $file_name ) );
		//debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$size = file_exists( $filePath ) ? size_format( filesize( $filePath ), 3 ) : '0 B';
			Logger::log( "Big File Uploader: Processing \"$file_name\" part $current_part of $chunks as $filePath. $size processed so far." );
		}

		$max_size = wp_max_upload_size();
		if ( file_exists( $filePath ) && filesize( $filePath ) + $file->get_size() > $max_size ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				Logger::log( "Big File Uploader: File size limit exceeded." );
			}

			return new WP_Error( 'filesize_limit_exceeded', __( 'The file size has exceeded the maximum file size setting.' ), [
				'filename' => $file_name,
			] );
		}

		// Open temp file.
		if ( $chunk == 0 ) {
			$out = @fopen( $filePath, 'wb' );
		} elseif ( is_writable( $filePath ) ) {
			$out = @fopen( $filePath, 'ab' );
		} else {
			$out = false;
		}

		if ( ! $out ) {
			/** Failed to open output stream. */
			Logger::log( "Big File Uploader: Failed to open output stream $filePath to write part $current_part of $chunks." );

			return new WP_Error(
				'fail_to_open_temp_file',
				sprintf( __( 'There was an error opening the temp file %s for writing. Available temp directory space may be exceeded or the temp file was cleaned up before the upload completed.' ), esc_html( $filePath ) ),
				[
					'status'   => 202,
					'filename' => $file_name,
				]
			);
		}

		/** Read binary input stream and append it to temp file. */
		$in = @fopen( $file->get_file(), 'rb' );

		if ( ! $in ) {
			/** Failed to open input stream. */
			/** Attempt to clean up unfinished output. */
			@fclose( $out );
			@unlink( $filePath );
			Logger::log( "Big File Uploader: Error reading uploaded part $current_part of $chunks." );

			return new WP_Error(
				'fail_to_read_uploaded_file',
				sprintf( __( 'There was an error reading uploaded part %d of %d.', 'tuxedo-big-file-uploads' ), $current_part, $chunks ),
				[
					'status'   => 202,
					'filename' => $file_name,
				]
			);
		}

		while ( $buff = fread( $in, 4096 ) ) {
			fwrite( $out, $buff );
		}

		@fclose( $in );
		@fclose( $out );
		@unlink( $file->get_file() );

		/** Check if file has finished uploading all parts. */
		if ( ! $chunks || $chunk == $chunks - 1 ) {

			//debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$size = file_exists( $filePath ) ? size_format( filesize( $filePath ), 3 ) : '0 B';
				error_log( "Big File Uploader: Completing \"$file_name\" upload with a $size final size." );
			}

			$wp_filetype = wp_check_filetype_and_ext( $file->get_file(), $file->get_client_filename() );

			/** Recreate upload in $_FILES global and pass off to WordPress. */
			$_FILES[ $request_key ]['tmp_name'] = $filePath;
			$_FILES[ $request_key ]['name']     = $file_name;
			$_FILES[ $request_key ]['size']     = $file->get_size();
			$_FILES[ $request_key ]['type']     = $file->get_mime_type();

			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

//			$media_id = media_handle_upload( $request_key, 0, [], [
//				'action'    => 'wp_handle_sideload',
//				'test_form' => false
//			] );
//
//			if ( is_wp_error( $media_id ) ) {
//				$media_id->add_data( $_FILES[ $request_key ] );
//			}

			return new UploadedFile( $filePath, $file_name, $file->get_mime_type(), filesize( $filePath ) );
		}

		return 0;
	}
}
