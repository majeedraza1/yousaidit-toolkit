<?php

namespace YouSaidItCards\Modules\DynamicCard\REST;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\ChunkFileUploader;
use YouSaidItCards\Modules\InnerMessage\BackgroundCopyVideoToServer;
use YouSaidItCards\Modules\InnerMessage\VideoEditor;
use YouSaidItCards\Providers\AWSElementalMediaConvert;
use YouSaidItCards\Providers\GoogleVisionClient;
use YouSaidItCards\REST\ApiController;
use YouSaidItCards\Utils;

class UserMediaController extends ApiController {
	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/dynamic-cards/media', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/dynamic-cards/media/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/dynamic-cards/video', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_video_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'upload_video' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/video/async-upload', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'async_upload' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/dynamic-cards/video/status', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_video_status' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$ids = array_filter( array_map( 'intval', (array) $request->get_param( 'images' ) ) );

		$response      = [];
		$user_id       = get_current_user_id();
		$perform_query = false;

		if ( $user_id || count( $ids ) || current_user_can( 'manage_options' ) ) {
			$perform_query = true;
		}

		if ( ! $perform_query ) {
			return $this->respondUnauthorized( null, null, [ 'user' => $user_id ] );
		}

		$args = [
			'posts_per_page' => 12,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
		];
		if ( ! current_user_can( 'manage_options' ) ) {
			$args['author'] = $user_id;
		}
		if ( count( $ids ) ) {
			$args['include'] = $ids;
		}
		$posts_array = get_posts( $args );

		foreach ( $posts_array as $item ) {
			$response[] = $this->_prepare_item_for_response( $item->ID );
		}

		return $this->respondOK( $response );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$current_user = wp_get_current_user();

		$files = UploadedFile::getUploadedFiles();

		if ( ! isset( $files['file'] ) ) {
			return $this->respondForbidden();
		}

		if ( ! $files['file'] instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		$should_check_adult_content = SettingPage::get_option( 'enable_adult_content_check', '1' );

		if ( $should_check_adult_content ) {
			$image_path    = $files['file']->getFile();
			$content       = base64_encode( file_get_contents( $image_path ) );
			$vision_client = new GoogleVisionClient();
			$safe_search   = $vision_client->safe_search( $content );
			if ( is_wp_error( $safe_search ) ) {
				if ( current_user_can( 'manage_options' ) ) {
					return $this->respondWithWpError( $safe_search );
				}

				return $this->respondUnprocessableEntity( $safe_search->get_error_code(),
					'Failed to verify adult content. Please contact with admin.' );
			}

			if ( isset( $safe_search['safeSearchAnnotation'] ) && $vision_client->is_adult( $safe_search['safeSearchAnnotation'] ) ) {
				return $this->respondUnprocessableEntity( 'forbidden_adult_content',
					'Sorry, Adult content is not allowed.' );
			}
		}

		$attachment_id = Uploader::uploadSingleFile( $files['file'] );
		if ( is_wp_error( $attachment_id ) ) {
			return $this->respondUnprocessableEntity(
				$attachment_id->get_error_code(),
				$attachment_id->get_error_message()
			);
		}

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $attachment_id, '_delete_token', $token );

		if ( ! $current_user->exists() ) {
			update_post_meta( $attachment_id, '_should_delete_after_time', ( time() + DAY_IN_SECONDS ) );
		}

		$response = $this->_prepare_item_for_response( $attachment_id );

		return $this->respondOK( $response );
	}

	/**
	 * Get video items
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function get_video_items( $request ) {
		$ids = array_filter( array_map( 'intval', (array) $request->get_param( 'videos' ) ) );

		$response      = [];
		$user_id       = get_current_user_id();
		$perform_query = false;

		if ( $user_id || count( $ids ) || current_user_can( 'manage_options' ) ) {
			$perform_query = true;
		}

		if ( ! $perform_query ) {
			return $this->respondUnauthorized( null, null, [ 'user' => $user_id ] );
		}

		$args = [
			'posts_per_page' => 12,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => [ 'video/mp4', 'video/ogg', 'video/webm' ],
		];
		if ( ! current_user_can( 'manage_options' ) ) {
			$args['author'] = $user_id;
		}
		if ( count( $ids ) ) {
			$args['include'] = $ids;
		}
		$posts_array = get_posts( $args );

		foreach ( $posts_array as $item ) {
			$response[] = $this->prepare_video_for_response( $item->ID );
		}

		return $this->respondOK( $response );
	}

	public function async_upload( WP_REST_Request $request ): WP_REST_Response {
		$files = UploadedFile::getUploadedFiles();
		$file  = $files['file'] ?? null;

		if ( ! $file instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		$attachment_id = ChunkFileUploader::upload( $file, $request->get_params() );
		if ( is_wp_error( $attachment_id ) ) {
			return $this->respondWithWpError( $attachment_id );
		}

		if ( 0 === $attachment_id ) {
			return $this->respondAccepted();
		}

		if ( $attachment_id instanceof UploadedFile ) {
			$attachment_id = Uploader::uploadSingleFile( $attachment_id, null, $request->get_param( 'name' ) );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $this->respondWithWpError( $attachment_id );
		}

		return $this->respondOK( $this->prepare_video_for_response( $attachment_id ) );
	}

	/**
	 * Upload a video
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function upload_video( $request ) {
		$is_chunk     = $request->has_param( 'chunk' ) && $request->has_param( 'chunks' );
		$current_user = wp_get_current_user();

		$files = UploadedFile::getUploadedFiles();
		$file  = $files['file'] ?? null;

		if ( ! $file instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		if ( $file->getSize() > wp_max_upload_size() ) {
			return $this->respondUnprocessableEntity( 'large_file_size', 'File size too large.' );
		}

		$filename = sprintf( '%s--%s--%s',
			Utils::generate_uuid(),
			$current_user->ID,
			Utils::str_rand( 64 - ( 36 + 4 + strlen( (string) $current_user->ID ) ) )
		);

		if ( $is_chunk ) {
			$attachment_id = ChunkFileUploader::upload( $file, [
				'chunk'  => $request->get_param( 'chunk' ),
				'chunks' => $request->get_param( 'chunks' ),
				'name'   => $request->get_param( 'name' ),
			] );

			if ( 0 === $attachment_id ) {
				return $this->respondAccepted();
			}

			if ( $attachment_id instanceof UploadedFile ) {
				$file = $attachment_id;
			}
		}

		$need_convert         = false;
		$browser_supported    = [ 'video/mp4', 'video/ogg', 'video/webm' ];
		$other_mime_types     = [ 'video/quicktime', 'video/x-msvideo', 'video/avi', 'video/x-ms-wmv' ];
		$supported_mime_types = array_merge( $browser_supported, $other_mime_types );

		// only video can be uploaded
		if ( ! in_array( $file->get_mime_type(), $supported_mime_types ) ) {
			return $this->respondForbidden( 'unsupported_video_format', 'Unsupported Video format.', [
				'mime_type' => $file->get_mime_type(),
			] );
		}

		if ( in_array( $file->get_mime_type(), $other_mime_types ) ) {
			$need_convert = true;
		}

		if ( $need_convert ) {
			$converter = SettingPage::get_option( 'video_converter', 'none' );
			if ( 'server' === $converter ) {
				$attachment_id = VideoEditor::convert( $file, null, sprintf( '%s.mp4', $filename ) );
			} elseif ( 'aws' === $converter ) {
				// Store file in temp directory.
				$upload_dir = wp_upload_dir();
				$base_dir   = Uploader::get_upload_dir( 'video-to-convert' );
				$file_path  = Uploader::uploadFile( $file, $base_dir, sprintf( '%s.%s', $filename, $file->get_client_extension() ) );
				$file_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
				if ( 'local' === wp_get_environment_type() && defined( 'AWS_MEDIA_CONVERT_INPUT' ) ) {
					$file_url = AWS_MEDIA_CONVERT_INPUT;
				}
				// Create AWS MediaConvert job to create job to convert video
				$job_id = AWSElementalMediaConvert::create_job( $file_url );

				// Store job id to use it later to check status
				return $this->respondAccepted( [
					'job_id'   => $job_id,
					'filepath' => $file_path,
					'file_url' => $file_url,
				] );
			} else {
				return $this->respondForbidden( 'unsupported_video_format', 'Unsupported Video format.' );
			}
		} else {
			$attachment_id = Uploader::uploadSingleFile( $file, null, sprintf( '%s.%s', $filename, $file->getClientExtension() ) );
		}
		if ( is_wp_error( $attachment_id ) ) {
			return $this->respondUnprocessableEntity(
				$attachment_id->get_error_code(),
				$attachment_id->get_error_message()
			);
		}

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $attachment_id, '_delete_token', $token );
		update_post_meta( $attachment_id, '_video_message_filename', $filename );

		if ( ! $current_user->exists() ) {
			update_post_meta( $attachment_id, '_should_delete_after_time', ( time() + DAY_IN_SECONDS ) );
		}

		return $this->respondOK( $this->prepare_video_for_response( $attachment_id ) );
	}

	/**
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 * @throws \Exception
	 */
	public function get_video_status( $request ) {
		$job_id = $request->get_param( 'job_id' );
		$job    = AWSElementalMediaConvert::get_job( $job_id );
		if ( is_wp_error( $job ) ) {
			return $this->respondWithWpError( $job );
		}

		$data = AWSElementalMediaConvert::format_job_result( $job );
		if ( 'complete' !== $data['status'] ) {
			return $this->respondAccepted( $data );
		}

		BackgroundCopyVideoToServer::init()->push_to_queue( [ 'job_id' => $job_id ] );

		return $this->respondOK( [
			'id'     => $data['id'],
			'url'    => $data['output'],
			'width'  => $data['width'],
			'height' => $data['height'],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_item( $request ) {
		$id    = $request->get_param( 'id' );
		$token = $request->get_param( 'token' );

		$_post = get_post( $id );

		if ( ! $_post instanceof WP_Post ) {
			return $this->respondNotFound( null, 'Image not found!' );
		}

		if ( self::can_delete_media( $_post, $token ) ) {
			return $this->respondUnauthorized();
		}

		wp_delete_post( $id, true );

		return $this->respondOK( null, [ 'deleted' => true ] );
	}

	/**
	 * Any logged in user can upload media
	 *
	 * @return bool
	 */
	public function can_view_media(): bool {
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Any logged-in user can upload media
	 *
	 * @return bool
	 */
	public function can_upload_media(): bool {
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * If current user can delete media
	 *
	 * @param WP_Post $post
	 * @param string $token
	 *
	 * @return bool
	 */
	private static function can_delete_media( WP_Post $post, string $token = '' ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( get_current_user_id() == $post->post_author ) {
			return true;
		}

		$delete_token = get_post_meta( $post->ID, '_delete_token', true );
		if ( $token == $delete_token ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param int $attachment_id
	 *
	 * @return array
	 */
	public function _prepare_item_for_response( int $attachment_id ): array {
		$title          = get_the_title( $attachment_id );
		$token          = get_post_meta( $attachment_id, '_delete_token', true );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$image          = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
		$full_image     = wp_get_attachment_image_src( $attachment_id, 'full' );

		$data = [
			'id'             => $attachment_id,
			'title'          => $title,
			'token'          => $token,
			'attachment_url' => $attachment_url,
			'thumbnail'      => [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ],
			'full'           => [ 'src' => '', 'width' => '', 'height' => '', ],
		];
		if ( is_array( $full_image ) ) {
			$data['full'] = [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2], ];
		}

		return $data;
	}

	/**
	 * @param $attachment_id
	 *
	 * @return array
	 */
	public function prepare_video_for_response( $attachment_id ): array {
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$title = get_the_title( $attachment_id );
		$token = get_post_meta( $attachment_id, '_delete_token', true );

		return [
			'id'     => $attachment_id,
			'title'  => $title,
			'token'  => $token,
			'url'    => wp_get_attachment_url( $attachment_id ),
			'width'  => $meta['width'],
			'height' => $meta['height'],
			'type'   => $meta['mime_type'],
		];
	}
}
