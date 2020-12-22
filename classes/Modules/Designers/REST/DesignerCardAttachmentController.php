<?php

namespace Yousaidit\Modules\Designers\REST;

use Exception;
use Stackonet\WP\Framework\Supports\Attachment;
use Stackonet\WP\Framework\Supports\UploadedFile;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yousaidit\Modules\Designers\Models\CardSizeAttribute;

defined( 'ABSPATH' ) || exit;

class DesignerCardAttachmentController extends ApiController {

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
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/attachment', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_items' ], ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_item' ], ],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		/** @var \WP_User $author */
		$author = wp_get_current_user();

		if ( ! $author->exists() ) {
			return $this->respondUnauthorized();
		}

		$_mime_types = $request->get_param( 'mime_types' );
		$mime_types  = [];
		if ( is_array( $_mime_types ) ) {
			foreach ( $_mime_types as $mime_type ) {
				if ( in_array( $mime_type, get_allowed_mime_types() ) ) {
					$mime_types[] = $mime_type;
				}
			}
		}

		$args = array(
			'posts_per_page' => 25,
			'orderby'        => 'id',
			'order'          => 'DESC',
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'author'         => $author->ID,
		);
		if ( count( $mime_types ) ) {
			$args['post_mime_type'] = $mime_types;
		}
		$query = new \WP_Query( $args );

		$posts_array = $query->get_posts();

		$response = [];

		if ( count( $posts_array ) ) {
			foreach ( $posts_array as $item ) {
				$response[] = $this->prepare_item_for_response( $item->ID, $request );
			}
		}

		return $this->respondOK( $response );
	}

	/**
	 * Upload a file to a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object.
	 * @throws Exception
	 */
	public function create_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user_id = (int) $request->get_param( 'user_id' );

		$type = $request->get_param( 'type' );
		$type = in_array( $type, [ 'card_pdf', 'card_gallery_images', 'card_image' ] ) ? $type : 'card_image';

		$card_size = $request->get_param( 'card_size' );

		if ( $type == 'card_pdf' && ! CardSizeAttribute::init()->is_valid_card_size( $card_size ) ) {
			return $this->respondUnprocessableEntity( 'unsupported_card_sizes', 'Card size does not support.' );
		}

		if ( $user_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$query_args = [ 'type' => $type ];
		if ( 'card_pdf' == $type ) {
			$query_args['card_size'] = $card_size;
		}

		$files = UploadedFile::getUploadedFiles();

		if ( ! isset( $files['file'] ) ) {
			return $this->respondForbidden();
		}

		$uploadedFile = $files['file'];

		if ( ! $uploadedFile instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		if ( 'card_pdf' == $type ) {
			$valid_file_types = [ 'application/pdf' ];
			$accepted_size    = wp_convert_hr_to_bytes( '2MB' );
		} else {
			$valid_file_types = [ 'image/jpeg', 'image/jpg', 'image/png' ];
			$accepted_size    = wp_convert_hr_to_bytes( '1MB' );
		}

		if ( $uploadedFile->getSize() > $accepted_size ) {
			$this->setStatusCode( 413 );

			return $this->respondUnprocessableEntity( 'file_size_too_large', 'File size too large.' );
		}

		if ( ! in_array( $uploadedFile->getClientMediaType(), $valid_file_types ) ) {
			return $this->respondUnprocessableEntity( 'invalid_media_type', 'File type not valid.' );
		}

		$attachments = Attachment::upload( $files['file'] );
		$ids         = wp_list_pluck( $attachments, 'attachment_id' );

		$image_id = $ids[0];

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $image_id, '_delete_token', $token );

		$attachment = $this->prepare_item_for_response( $image_id, $request );

		return $this->respondOK( [ 'attachment' => $attachment, 'query' => $query_args ] );
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function prepare_item_for_response( $item, $request ) {
		$image_id       = $item;
		$title          = get_the_title( $image_id );
		$token          = get_post_meta( $image_id, '_delete_token', true );
		$attachment_url = wp_get_attachment_url( $image_id );

		$is_image = wp_attachment_is_image( $image_id );

		$response = [
			'id'             => $image_id,
			'title'          => $title,
			'attachment_url' => $attachment_url,
			'token'          => $token,
			'thumbnail'      => new \ArrayObject(),
			'full'           => new \ArrayObject(),
		];

		if ( $is_image ) {
			$image      = wp_get_attachment_image_src( $image_id, 'thumbnail' );
			$full_image = wp_get_attachment_image_src( $image_id, 'full' );

			$response['thumbnail'] = [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ];

			$response['full'] = [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2] ];
		}

		return $response;
	}
}
