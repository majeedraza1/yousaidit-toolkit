<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Supports\Attachment;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Admin\Settings;
use YouSaidItCards\Modules\Designers\Emails\NewDesignerEmail;

defined( 'ABSPATH' ) || exit;

class UserRegistrationController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
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
		register_rest_route( $this->namespace, '/registration', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'args'                => $this->create_item_params(),
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designer-signup', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'designer_signup' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designer-signup/validate', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate_designer_signup' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object
	 */
	public function validate_designer_signup( WP_REST_Request $request ): WP_REST_Response {
		$errors = array();

		$paypal_email = $request->get_param( 'paypal_email' );
		if ( ! empty( $paypal_email ) ) {
			$status = $this->rest_validate_paypal_email( $paypal_email );
			if ( is_wp_error( $status ) ) {
				$errors['paypal_email'] = $status->get_error_data();
			}
		}
		$email = $request->get_param( 'email' );
		if ( ! empty( $email ) ) {
			$status = $this->rest_validate_user_email( $email );
			if ( is_wp_error( $status ) ) {
				$errors['email'] = $status->get_error_data();
			}
		}

		$username = $request->get_param( 'username' );
		if ( ! empty( $username ) ) {
			$status = $this->rest_validate_username( $username );
			if ( is_wp_error( $status ) ) {
				$errors['username'] = $status->get_error_data();
			}
		}

		// Exit if there is any error
		if ( count( $errors ) > 0 ) {
			return $this->respondUnprocessableEntity( 'invalid_request', 'One or more fields has an error.', $errors );
		}

		return $this->respondOK();
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object
	 */
	public function designer_signup( WP_REST_Request $request ): WP_REST_Response {
		$paypal_email = $request->get_param( 'paypal_email' );
		$username     = $request->get_param( 'username' );
		$email        = $request->get_param( 'email' );
		$validation   = $this->validate_email_username( $username, $email, $paypal_email );
		if ( is_wp_error( $validation ) ) {
			return $this->respondWithWpError( $validation );
		}

		$newsletter_signup = Validate::checked( $request->get_param( 'newsletter_signup' ) );
		$accept_terms      = Validate::checked( $request->get_param( 'accept_terms' ) );

		$name       = $request->get_param( 'name' );
		$name       = ! empty( $name ) ? $name : $username;
		$name_parts = explode( " ", $name );
		$last_name  = array_pop( $name_parts );
		$first_name = count( $name_parts ) > 0 ? implode( " ", $name_parts ) : "";

		$user_data = array(
			'show_admin_bar_front' => false,
			'role'                 => Settings::get_designer_role(),
			'user_email'           => $email,
			'meta_input'           => [
				'_is_card_designer' => 'yes',
				'_paypal_email'     => $paypal_email,
				'_business_name'    => $request->get_param( 'brand_name' ),
				'_location'         => $request->get_param( 'brand_location' ),
				'_instagram_url'    => $request->get_param( 'brand_instagram_url' ),
			],
		);

		$description = $request->get_param( 'brand_details' );
		if ( ! empty( $description ) ) {
			$user_data['description'] = wp_strip_all_tags( $description );
		}

		// User Login
		if ( ! empty( $username ) ) {
			$user_data['user_login'] = $username;
		} else {
			$user_data['user_login'] = $email;
		}

		// First name
		if ( ! empty( $first_name ) ) {
			$user_data['first_name'] = $first_name;
		}

		// Last name
		if ( ! empty( $last_name ) ) {
			$user_data['last_name'] = $last_name;
		}

		// Generate the password so that the subscriber will have to check email...
		$user_data['user_pass'] = wp_generate_password( 20, true, true );

		$uploaded_files = UploadedFile::parse_uploaded_files( $request->get_file_params() );

		foreach ( $uploaded_files as $name => $uploaded_file ) {
			if ( $uploaded_file instanceof UploadedFile ) {
				if ( ! in_array( $uploaded_file->get_mime_type(), [ 'image/jpeg', 'image/png', ], true ) ) {
					continue;
				}
				if ( 'profile_logo' === $name ) {
					$image_id = Attachment::upload_single_file( $uploaded_file );
					if ( is_numeric( $image_id ) ) {
						$user_data['meta_input']['_avatar_id'] = $image_id;
					}
				}
				if ( 'profile_banner' === $name ) {
					$image_id = Attachment::upload_single_file( $uploaded_file );
					if ( is_numeric( $image_id ) ) {
						$user_data['meta_input']['_cover_photo_id'] = $image_id;
					}
				}
				if ( 'card_logo' === $name ) {
					$image_id = Attachment::upload_single_file( $uploaded_file );
					if ( is_numeric( $image_id ) ) {
						$user_data['meta_input']['_card_logo_id'] = $image_id;
					}
				}
			}
		}

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $this->respondUnprocessableEntity( 'undefined_error', 'Something went wrong. Please try again.' );
		}

		( new NewDesignerEmail( $user_id ) )->send_mail();

		return $this->respondCreated( [ 'user_id' => $user_id ] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		// Registration closed
		if ( ! get_option( 'users_can_register' ) ) {
			return $this->respondUnprocessableEntity( 'register_disabled', 'User registration is not enabled.' );
		}

		$email    = $request->get_param( 'email' );
		$username = $request->get_param( 'username' );
		$name     = sanitize_text_field( $request->get_param( 'name' ) );

		$errors = array();

		if ( empty( $name ) ) {
			$errors['name'][] = 'Name is required.';
		}

		if ( ! is_email( $email ) ) {
			$errors['email'][] = 'Email is required.';
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors['email'][] = 'An account exists with this email address.';
		}

		if ( empty( $username ) ) {
			$errors['username'][] = 'Username is required.';
		} else {

			// Check if username exists
			if ( username_exists( $username ) || email_exists( $username ) ) {
				$errors['username'][] = 'An account exists with this username.';
			}

			// Check if username has minimum length
			if ( 4 > strlen( $username ) ) {
				$errors['username'][] = 'Username too short. At least 4 characters is required.';
			}

			if ( ! validate_username( $username ) ) {
				$errors['username'][] = 'Sorry, the username you entered is not valid.';
			}
		}

		// Exit if there is any error
		if ( count( $errors ) > 0 ) {
			return $this->respondUnprocessableEntity( 'invalid_request', 'One or more fields has an error.', $errors );
		}

		$user_data = array(
			'user_email' => $email,
		);

		// User Login
		if ( ! empty( $username ) ) {
			$user_data['user_login'] = $username;
		} else {
			$user_data['user_login'] = $email;
		}

		$name_parts = explode( " ", $name );
		$last_name  = array_pop( $name_parts );
		$first_name = count( $name_parts ) > 0 ? implode( " ", $name_parts ) : "";

		// First name
		if ( ! empty( $first_name ) ) {
			$user_data['first_name'] = $first_name;
		}

		// Last name
		if ( ! empty( $last_name ) ) {
			$user_data['last_name'] = $last_name;
		}

		// Generate the password so that the subscriber will have to check email...
		$user_data['user_pass'] = wp_generate_password( 20, true, true );

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $this->respondUnprocessableEntity( 'undefined_error', 'Something went wrong. Please try again.' );
		}

		( new NewDesignerEmail( $user_id ) )->send_mail();

		return $this->respondCreated( [ 'user_id' => $user_id ] );
	}

	public function validate_email_username( string $username, string $email, ?string $paypal_email = '' ) {
		$errors = array();

		$status = $this->rest_validate_user_email( $email );
		if ( is_wp_error( $status ) ) {
			$errors['email'] = $status->get_error_data();
		}

		$status = $this->rest_validate_username( $username );
		if ( is_wp_error( $status ) ) {
			$errors['username'] = $status->get_error_data();
		}

		if ( ! empty( $paypal_email ) ) {
			$status = $this->rest_validate_paypal_email( $paypal_email );
			if ( is_wp_error( $status ) ) {
				$errors['paypal_email'] = $status->get_error_data();
			}
		}

		// Exit if there is any error
		if ( count( $errors ) > 0 ) {
			return new WP_Error( 'invalid_request', 'One or more fields has an error.', $errors );
		}

		return true;
	}

	public function rest_validate_user_email( string $email ) {
		$errors = [];
		if ( ! is_email( $email ) ) {
			$errors[] = 'Invalid email address.';
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors[] = 'An account exists with this email address.';
		}

		if ( count( $errors ) ) {
			return new WP_Error( 'email_error', 'Email error.', $errors );
		}

		return true;
	}

	public function rest_validate_paypal_email( string $paypal_email ) {
		$errors = [];
		if ( ! is_email( $paypal_email ) ) {
			$errors[] = 'Email is required.';
		}

		if ( username_exists( $paypal_email ) || email_exists( $paypal_email ) ) {
			$errors[] = 'An account exists with this email address.';
		}

		global $wpdb;
		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->usermeta WHERE meta_key = '_paypal_email' and meta_value = %s",
				$paypal_email
			),
			ARRAY_A
		);
		if ( is_array( $record ) && isset( $record['user_id'] ) ) {
			$errors[] = 'An account exists with this email address.';
		}

		if ( count( $errors ) ) {
			return new WP_Error( 'paypal_email_error', 'PayPal Email error.', $errors );
		}

		return true;
	}

	public function rest_validate_username( string $username ) {
		$errors = [];
		// Check if username exists
		if ( username_exists( $username ) || email_exists( $username ) ) {
			$errors[] = 'An account exists with this username.';
		}

		// Check if username has minimum length
		if ( 4 > strlen( $username ) ) {
			$errors[] = 'Username too short. At least 4 characters is required.';
		}

		if ( ! validate_username( $username ) ) {
			$errors[] = 'Sorry, the username you entered is not valid.';
		}

		if ( count( $errors ) ) {
			return new WP_Error( 'username_error', 'Username error.', $errors );
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function create_item_params(): array {
		return [
			'email'    => array(
				'description'       => __( 'User email address. Must be unique.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'username' => array(
				'description'       => __( 'User username. Must be unique.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'name'     => array(
				'description'       => __( 'User name.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		];
	}
}
