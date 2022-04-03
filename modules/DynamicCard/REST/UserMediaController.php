<?php

namespace YouSaidItCards\Modules\DynamicCard\REST;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Post;
use WP_REST_Server;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\GoogleVisionClient;
use YouSaidItCards\REST\ApiController;

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
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
				'args'     => $this->get_collection_params(),
			],
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'create_item' ],
			],
		] );
		register_rest_route( $this->namespace, '/dynamic-cards/media/(?P<id>\d+)', [
			[
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => [ $this, 'delete_item' ],
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		if ( ! $this->can_view_media() ) {
//			return $this->respondUnauthorized();
		}

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
		if ( ! $this->can_upload_media() ) {
			// return $this->respondUnauthorized();
		}

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
				return $this->respondUnprocessableEntity( $safe_search->get_error_code(),
					'Failed to verify adult content. Please contact with admin.' );
			}

			if ( ! $vision_client->is_adult( $safe_search ) ) {
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

		return [
			'id'             => $attachment_id,
			'title'          => $title,
			'token'          => $token,
			'attachment_url' => $attachment_url,
			'thumbnail'      => [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ],
			'full'           => [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2], ],
		];
	}
}
