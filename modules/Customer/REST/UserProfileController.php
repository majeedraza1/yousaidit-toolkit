<?php

namespace YouSaidItCards\Modules\Customer\REST;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\GoogleVisionClient;
use YouSaidItCards\Modules\Auth\Auth;
use YouSaidItCards\Modules\Customer\Models\Customer;
use YouSaidItCards\REST\ApiController;

class UserProfileController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'me', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
				'args'                => $this->get_update_item_params(),
			]
		] );

		register_rest_route( $this->namespace, '/me/avatar', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_avatar' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
				'args'                => [
					'avatar' => [
						'description'       => __( 'User avatar' ),
						'type'              => 'string',
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
			]
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		return $this->respondOK( [ 'user' => Auth::prepare_user_for_response( $current_user ) ] );
	}

	/**
	 * @inheritDoc
	 */
	public function update_item( $request ) {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$params = $request->get_params();
		if ( count( $params ) < 1 ) {
			return $this->respondUnprocessableEntity( 'no_parameters', 'No parameters provided.' );
		}

		$data = Customer::update( $user->ID, $params );
		if ( $data ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_avatar() {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$files  = UploadedFile::getUploadedFiles();
		$avatar = $files['avatar'] ?? false;
		$avatar = is_array( $avatar ) ? $avatar[0] : $avatar;

		if ( ! $avatar instanceof UploadedFile ) {
			return $this->respondUnprocessableEntity( 'file_not_found', 'Please upload a JPG or PNG image file.' );
		}
		if ( ! $avatar->isImage() ) {
			return $this->respondUnprocessableEntity( 'unsupported_file_format', 'Please upload a JPG or PNG image file.' );
		}

		if ( $avatar->getSize() > ( 2 * MB_IN_BYTES ) ) {
			return $this->respondUnprocessableEntity( 'file_size_too_large', '2MB Maximum file size allowed.' );
		}

		$is_adult = GoogleVisionClient::is_adult_image( $avatar->getFile() );
		if ( true !== $is_adult ) {
			return $this->respondUnprocessableEntity( 'forbidden_adult_content',
				'Sorry, Adult content is not allowed.' );
		}

		$ids = Uploader::upload( $avatar );
		if ( isset( $ids[0]['attachment_id'] ) ) {
			$avatar_id = intval( $ids[0]['attachment_id'] );
			Customer::update_avatar_id( $user->ID, $avatar_id );

			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_update_item_params(): array {
		return [
			'name' => [
				'description'       => __( 'User full name' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
