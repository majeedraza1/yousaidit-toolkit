<?php

namespace YouSaidItCards\Modules\Auth\REST;

use WP_REST_Server;
use YouSaidItCards\Modules\Auth\CopyAvatarFromSocialProvider;
use YouSaidItCards\Modules\Auth\Models\SocialAuthProvider;
use YouSaidItCards\Modules\Auth\RegistrationConfirmEmail;
use YouSaidItCards\REST\ApiController;

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
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'create_item' ],
				'args'     => $this->create_item_params(),
			],
		] );
		register_rest_route( $this->namespace, '/registration/token', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'generate_token' ],
				'args'     => [
					'email' => [
						'type' => 'string',
					]
				],
			],
		] );
		register_rest_route( $this->namespace, '/registration/verify', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'verify_token' ],
				'args'     => [
					'token' => [
						'type' => 'string',
					]
				],
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		// Registration closed
		if ( ! get_option( 'users_can_register' ) ) {
			return $this->respondUnprocessableEntity( 'register_disabled', 'User registration is not enabled.' );
		}

		$email = strtolower( $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return $this->respondUnprocessableEntity( 'rest_required_field_missing', 'Email is required.' );
		}

		// Check if user already registered
		if ( username_exists( $email ) || email_exists( $email ) || SocialAuthProvider::email_exists( $email ) ) {
			return $this->respondUnprocessableEntity( 'rest_email_address_exists',
				'An account exists with this email address.' );
		}

		$name         = sanitize_text_field( $request->get_param( 'name' ) );
		$phone_number = $request->get_param( 'phone_number' );

		$username = $request->get_param( 'username' );
		if ( empty( $username ) ) {
			$username = $email;
		}

		$password         = $request->get_param( 'password' );
		$confirm_password = $request->get_param( 'confirm_password' );

		$provider    = $request->get_param( 'provider' );
		$provider_id = $request->get_param( 'provider_id' );
		if ( $provider && $provider_id ) {
			$password = $confirm_password = wp_generate_password();
		}

		$errors = self::validate( $name, $username, $password, $confirm_password );

		// Exit if there is any error
		if ( count( $errors ) > 0 ) {
			return $this->respondUnprocessableEntity( 'invalid_request', 'One or more fields has an error.', $errors );
		}

		$user_id = wp_insert_user( self::format_data_for_database( $email, $username, $name, $password ) );

		if ( is_wp_error( $user_id ) ) {
			return $this->respondUnprocessableEntity( 'undefined_error', 'Something went wrong. Please try again.' );
		}

		if ( in_array( $provider, SocialAuthProvider::get_providers() ) && ! empty( $provider_id ) ) {
			$name_parts = explode( " ", $name );
			$last_name  = array_pop( $name_parts );
			$first_name = count( $name_parts ) > 0 ? implode( " ", $name_parts ) : "";

			$data = [
				'user_id'       => $user_id,
				'email_address' => $email,
				'phone_number'  => $phone_number,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'provider'      => $provider,
				'provider_id'   => $provider_id,
			];

			SocialAuthProvider::create_or_update( $data );

			$avatar_url = $request->get_param( 'avatar_url' );
			new CopyAvatarFromSocialProvider( $user_id, $avatar_url );
		}

		$user = get_user_by( 'id', $user_id );
		// Send email to user
		( new RegistrationConfirmEmail( $user ) )->send();

		return $this->respondCreated( [ 'user' => $user ] );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function generate_token( \WP_REST_Request $request ) {
		$token = '';
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function verify_token( \WP_REST_Request $request ) {
		$token = '';
	}

	/**
	 * @param string      $email
	 * @param string|null $username
	 * @param string      $name
	 * @param string      $password
	 *
	 * @return array
	 */
	private static function format_data_for_database( string $email, ?string $username, string $name, string $password ): array {
		$user_data = array(
			'user_email' => $email,
			'user_login' => $email,
			'user_pass'  => $password,
		);

		// User Login
		if ( ! empty( $username ) ) {
			$user_data['user_login'] = $username;
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

		return $user_data;
	}

	/**
	 * @param mixed $name
	 * @param mixed $username
	 * @param mixed $password
	 * @param mixed $confirm_password
	 *
	 * @return array
	 */
	private static function validate( $name = null, $username = null, $password = null, $confirm_password = null ): array {
		$errors = array();

		if ( empty( $name ) ) {
			$errors['name'][] = 'Name is required.';
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

		// Check if username has minimum length
		if ( empty( $password ) ) {
			$errors['password'][] = 'Password is required.';
		} else {
			if ( 6 > strlen( $password ) ) {
				$errors['password'][] = 'Password too short. At least 6 characters is required.';
			} else if ( $password != $confirm_password ) {
				$errors['password'][] = 'Password does not match with confirm password.';
			}
		}

		return $errors;
	}

	/**
	 * @return array[]
	 */
	public function create_item_params(): array {
		return [
			'email'            => array(
				'description'       => __( 'User email address. Must be unique.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'username'         => array(
				'description'       => __( 'User username. Must be unique.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'name'             => array(
				'description'       => __( 'User name.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'password'         => array(
				'description'       => __( 'User password.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'confirm_password' => array(
				'description'       => __( 'User confirm password.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'phone_number'     => array(
				'description'       => __( 'User phone number.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'avatar_url'       => array(
				'description'       => __( 'User avatar url from social provider' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			),
		];
	}
}
