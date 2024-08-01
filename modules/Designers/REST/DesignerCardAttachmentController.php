<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Exception;
use Imagick;
use ImagickException;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\UploadedFile;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Models\CardSizeAttribute;
use YouSaidItCards\Utils;

defined( 'ABSPATH' ) || exit;

class DesignerCardAttachmentController extends ApiController {

	/**
	 * List of capabilities based on the request method.
	 *
	 * @var array
	 */
	protected $capabilities = [
		'read_items' => 'read',
		'read_item'  => 'read',
		'create'     => 'read',
		'update'     => 'read',
		'delete'     => 'read',
		'batch'      => 'delete_others_pages',
	];

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
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/designers-attachment/(?P<attachment_id>\d+)', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
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
				$response[] = Utils::prepare_media_item_for_response( $item->ID );
			}
		}

		return $this->respondOK( $response );
	}

	/**
	 * Upload a file to a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object.
	 * @throws Exception
	 */
	public function create_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$files = UploadedFile::parse_uploaded_files( $request->get_file_params() );
		if ( ! isset( $files['file'] ) ) {
			return $this->respondForbidden();
		}

		$uploadedFile = $files['file'];

		if ( ! $uploadedFile instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		$accepted_size = wp_convert_hr_to_bytes( '10MB' );
		if ( $uploadedFile->get_size() > $accepted_size ) {
			$this->setStatusCode( 413 );

			return $this->respondUnprocessableEntity( 'file_size_too_large', 'Maximum allowed filesize is 10MB.' );
		}

		$card_type = $request->get_param( 'card_type' );
		if ( in_array( $card_type, [ 'standard_card', 'photo_card' ], true ) ) {
			return $this->upload_square_card_image( $uploadedFile );
		}

		if ( 'mug' === $card_type ) {
			return $this->upload_mug_image( $uploadedFile );
		}

		$user_id = (int) $request->get_param( 'user_id' );

		$type                 = $request->get_param( 'type' );
		$profile_images_types = [ 'avatar', 'cover' ];
		$card_file_types      = [ 'card_pdf', 'card_gallery_images', 'card_image', 'card-logo' ];
		$valid_types          = array_merge( $profile_images_types, $card_file_types );
		$type                 = in_array( $type, $valid_types ) ? $type : 'card_image';

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

		if ( 'card_pdf' == $type ) {
			$valid_file_types = [ 'application/pdf' ];
		} else {
			$valid_file_types = [ 'image/jpeg', 'image/jpg', 'image/png' ];
		}

		if ( ! in_array( $uploadedFile->getClientMediaType(), $valid_file_types ) ) {
			return $this->respondUnprocessableEntity( 'invalid_media_type', 'File type not valid.' );
		}

		$image_id = Uploader::upload_single_file( $uploadedFile );

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $image_id, '_delete_token', $token );

		$attachment = Utils::prepare_media_item_for_response( $image_id );

		return $this->respondOK( [ 'attachment' => $attachment, 'query' => $query_args ] );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function delete_item( $request ) {
		$attachment_id = (int) $request->get_param( 'attachment_id' );
		$attachment    = get_post( $attachment_id );
		if ( ! $attachment instanceof \WP_Post ) {
			return $this->respondNotFound();
		}

		if ( $attachment->post_author !== get_current_user_id() || ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		wp_delete_attachment( $attachment_id );

		return $this->respondOK( [
			'id' => $attachment_id,
		] );
	}

	/**
	 * Upload photo card
	 *
	 * @param  UploadedFile  $uploadedFile
	 *
	 * @return WP_REST_Response
	 */
	private function upload_square_card_image( UploadedFile $uploadedFile ): WP_REST_Response {
		$valid_file_types = [ 'image/jpeg', 'image/jpg', 'image/png' ];
		if ( ! in_array( $uploadedFile->get_mime_type(), $valid_file_types ) ) {
			return $this->respondUnprocessableEntity( 'invalid_media_type', 'Only JPEG or PNG is supported.' );
		}

		// 150mm + 1mm bleed on left + 3mm bleed on right.
		$min_width = Utils::millimeter_to_pixels( 154 );
		// 150mm + 3mm bleed on top + 3mm bleed on bottom.
		$min_height = Utils::millimeter_to_pixels( 156 );

		try {
			$imagick = new Imagick( $uploadedFile->get_file() );
			if ( $imagick->getImageWidth() < $min_width ) {
				return $this->respondUnprocessableEntity( 'image_width_error',
					sprintf( 'Minimum image width is %spx. Your uploaded image width is %spx.', $min_width,
						$imagick->getImageWidth() )
				);
			}
			if ( $imagick->getImageHeight() < $min_height ) {
				return $this->respondUnprocessableEntity( 'image_height_error',
					sprintf( 'Minimum image height is %spx. Your uploaded image height is %spx.', $min_height,
						$imagick->getImageHeight() )
				);
			}
			$expected_height = intval( $imagick->getImageWidth() * ( $min_height / $min_width ) );
			if ( $expected_height !== $imagick->getImageHeight() ) {
				return $this->respondUnprocessableEntity( 'image_dimension_error',
					sprintf(
						'Aspect ratio does not match. Image dimension should be %sx%s px or higher keeping same aspect ratio. Expected image height is %spx but actual height is %s.',
						$min_width, $min_height, $expected_height, $imagick->getImageHeight()
					)
				);
			}
			// $min_width = $min_height
			// 1 = $min_width/$min_height;

		} catch ( ImagickException $e ) {
			return $this->respondInternalServerError( null, 'Fail to read image width and height.' );
		}

		$image_id = Uploader::upload_single_file( $uploadedFile );
		if ( is_wp_error( $image_id ) ) {
			return $this->respondWithWpError( $image_id );
		}

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $image_id, '_delete_token', $token );

		$attachment = Utils::prepare_media_item_for_response( $image_id );

		return $this->respondCreated( [ 'attachment' => $attachment ] );
	}

	/**
	 * Upload photo card
	 *
	 * @param  UploadedFile  $uploadedFile
	 *
	 * @return WP_REST_Response
	 */
	private function upload_mug_image( UploadedFile $uploadedFile ): WP_REST_Response {
		$valid_file_types = [ 'image/jpeg', 'image/jpg' ];
		if ( ! in_array( $uploadedFile->get_mime_type(), $valid_file_types, true ) ) {
			return $this->respondUnprocessableEntity( 'invalid_media_type', 'Only JPEG or PNG is supported.' );
		}

		$min_width  = Utils::millimeter_to_pixels( 210 );
		$min_height = Utils::millimeter_to_pixels( 99 );

		try {
			$imagick = new Imagick( $uploadedFile->get_file() );
			$message = sprintf( 'Minimum image size is %sx%s px. Your uploaded image size is %sx%s px.',
				$min_width, $min_height, $imagick->getImageWidth(), $imagick->getImageHeight()
			);
			if ( $imagick->getImageWidth() < $min_width ) {
				return $this->respondUnprocessableEntity( 'image_width_error', $message );
			}
			if ( $imagick->getImageHeight() < $min_height ) {
				return $this->respondUnprocessableEntity( 'image_height_error', $message );
			}
			$expected_height = intval( $imagick->getImageWidth() * ( $min_height / $min_width ) );
			if ( $expected_height !== $imagick->getImageHeight() ) {
				return $this->respondUnprocessableEntity( 'image_dimension_error',
					sprintf(
						'Aspect ratio does not match. Image dimension should be %sx%s px or higher keeping same aspect ratio. Expected image height is %spx but actual height is %s.',
						$min_width, $min_height, $expected_height, $imagick->getImageHeight()
					)
				);
			}
		} catch ( ImagickException $e ) {
			return $this->respondInternalServerError( null, 'Fail to read image width and height.' );
		}

		$image_id = Uploader::upload_single_file( $uploadedFile );
		if ( is_wp_error( $image_id ) ) {
			return $this->respondWithWpError( $image_id );
		}

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $image_id, '_delete_token', $token );


		$attachment = Utils::prepare_media_item_for_response( $image_id );

		return $this->respondCreated( [ 'attachment' => $attachment ] );
	}
}
