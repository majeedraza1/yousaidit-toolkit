<?php

namespace YouSaidItCards\Modules\StabilityAi;

use ImagickException;
use Stackonet\WP\Framework\BackgroundProcessing\BackgroundProcessWithUiHelper;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use WP_Post;

/**
 * BackgroundGenerateThumbnail class
 */
class BackgroundGenerateThumbnail extends BackgroundProcessWithUiHelper {

	/**
	 * The action name
	 *
	 * @var string
	 */
	protected $action = 'stability_ai_thumbnail';

	/**
	 * Admin notice heading.
	 *
	 * @var string
	 */
	protected $admin_notice_heading = 'A background task is running to generate {{total_items}} thumbnail images using Stability AI.';

	/**
	 * The instance of the class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * The instance of the class
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Generate thumbnail for post.
	 *
	 * @param  WP_Post  $post  The post object.
	 *
	 * @return false|int
	 */
	public static function generate_thumbnail_for_post(
		WP_Post $post,
		?string $prompt = null,
		bool $force = false
	) {
		$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
		if ( $thumbnail_id && false === $force ) {
			Logger::log( sprintf( 'There is a thumbnail for post #%s', $post->ID ) );

			return false;
		}
		if ( empty( $prompt ) ) {
			$prompt = Settings::get_default_prompt();
		}
		$prompt = str_replace( '{{title}}', $post->post_title, $prompt );
		$prompt = str_replace( '{{meta_description}}', $post->post_title, $prompt );

		$image_string = StabilityAiClient::generate_text_to_image( $prompt );
		if ( is_wp_error( $image_string ) ) {
			Logger::log( sprintf( 'Error for post #%s: %s', $post->ID, $image_string->get_error_message() ) );

			return false;
		}

		if ( 'uuid' === Settings::get_file_naming_method() ) {
			$filename = wp_generate_uuid4() . '.webp';
		} else {
			$filename = sanitize_title_with_dashes( $post->post_title . ' image' ) . '.webp';
		}

		$directory     = rtrim( Uploader::get_upload_dir(), DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		try {
			$imagick = new \Imagick();
			$imagick->readImageBlob( $image_string );
			$imagick->setImageFormat( 'webp' );
			$imagick->setImageCompressionQuality( 83 );
			$imagick->writeImage( $new_file_path );

			$imagick->destroy();

			// Set correct file permissions.
			$stat  = stat( dirname( $new_file_path ) );
			$perms = $stat['mode'] & 0000666;
			chmod( $new_file_path, $perms );
		} catch ( ImagickException $e ) {
			Logger::log( $e->getMessage() );

			return false;
		}

		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_text_field( $post->post_title . ' image' ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => 'image/webp',
			'post_author'    => $post->post_author,
		];

		$attachment_id = wp_insert_attachment( $data, $new_file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_read_video_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/media.php';
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );

			set_post_thumbnail( $post, $attachment_id );
			update_post_meta( $attachment_id, '_create_via', 'stability.ai' );

			return $attachment_id;
		}

		return false;
	}

	/**
	 * Perform task
	 *
	 * @param  array  $item  The data to be processed.
	 *
	 * @return bool
	 */
	protected function task( $item ) {
		$post_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			Logger::log( sprintf( 'No post found for id #%s', $post_id ) );

			return false;
		}

		static::generate_thumbnail_for_post( $post );

		return false;
	}

	/**
	 * Add to queue
	 *
	 * @param  int  $post_id  The post id.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $post_id ) {
		$self    = new static();
		$pending = $self->get_pending_items();
		if ( count( $pending ) ) {
			$ids = wp_list_pluck( $pending, 'id' );
			if ( ! in_array( $post_id, $ids, true ) ) {
				$self->push_to_queue( [ 'id' => $post_id ] );
			}

			return;
		}
		$self->push_to_queue( [ 'id' => $post_id ] );
	}

	/**
	 * Is it in queue
	 *
	 * @param  int  $post_id
	 *
	 * @return bool
	 */
	public static function is_in_queue( int $post_id ): bool {
		$self    = new static();
		$pending = $self->get_pending_items();
		if ( count( $pending ) ) {
			$ids = wp_list_pluck( $pending, 'id' );

			return in_array( $post_id, $ids, true );
		}

		return false;
	}
}
