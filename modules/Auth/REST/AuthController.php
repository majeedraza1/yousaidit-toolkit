<?php

namespace YouSaidItCards\Modules\Auth\REST;

use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;
use YouSaidItCards\Modules\Auth\Auth;
use YouSaidItCards\Modules\Auth\Models\SocialAuthProvider;
use YouSaidItCards\Modules\Auth\Models\User;
use YouSaidItCards\REST\ApiController;

class AuthController extends ApiController {
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

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'token', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'generate_token' ],
			'args'                => Auth::generate_token_rest_params(),
			'permission_callback' => '__return_true',
		] );
		register_rest_route( $this->namespace, 'token/validate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'validate_token' ],
			'args'                => Auth::validate_token_rest_params(),
			'permission_callback' => '__return_true',
		] );
		register_rest_route( $this->namespace, '/auth-provider', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_auth_provider' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'provider'    => [
					'description'       => __( 'Auth provider', 'stackonet-jwt-auth' ),
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => 'rest_validate_request_arg',
					'enum'              => SocialAuthProvider::get_providers()
				],
				'provider_id' => [
					'description'       => __( 'Auth provider id', 'stackonet-jwt-auth' ),
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => 'rest_validate_request_arg',
				],
				'email'       => [
					'description'       => __( 'Public email from auth provider.', 'stackonet-jwt-auth' ),
					'type'              => 'string',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'mode'        => [
					'description'       => __( 'action mode', 'stackonet-jwt-auth' ),
					'type'              => 'string',
					'validate_callback' => 'rest_validate_request_arg',
					'enum'              => [ 'link', 'unlink' ]
				]
			]
		] );
	}

	/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function generate_token( $request ) {
		$current_user = wp_get_current_user();
		if ( $current_user->exists() ) {
			return new WP_Error( 'rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.' ), [ 'status' => 401 ] );
		}

		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );
		$remember = Validate::checked( $request->get_param( 'remember' ) );

		$provider = $request->get_param( 'provider' );
		$provider = in_array( $provider, SocialAuthProvider::get_providers() ) ? $provider : 'default';
		$email    = $request->get_param( 'email' );

		if ( 'default' == $provider ) {
			if ( ! ( username_exists( $username ) || email_exists( $username ) ) ) {
				return $this->respondNotFound( 'username_not_found', 'No user found with this email address.' );
			}
			/** @var WP_User|WP_Error $user */
			$user = wp_authenticate( $username, $password );
		} else {
			$provider_id = $request->get_param( 'provider_id' );
			/** @var WP_User|WP_Error $user */
			$user = SocialAuthProvider::authenticate( $provider, $provider_id );

			if ( ! $user instanceof WP_User && is_email( $email ) ) {
				$_user = get_user_by( 'email', $email );
				if ( $_user instanceof WP_User ) {
					$data = [
						'user_id'       => $_user->ID,
						'email_address' => $email,
						'provider'      => $provider,
						'provider_id'   => $provider_id,
					];

					SocialAuthProvider::create_or_update( $data );
					$user = $_user;
				}
			}
		}

		if ( is_wp_error( $user ) ) {
			if ( $user->get_error_code() == 'incorrect_password' ) {
				return $this->respondUnprocessableEntity( $user->get_error_code(), 'The password you entered is incorrect.' );
			}

			return $this->respondWithError( $user );
		}

		$auth_user = new User( $user );
		if ( ! $auth_user->is_registration_verified() ) {
			return $this->respondUnauthorized( null, 'Please verify you email first.' );
		}

		$token = Auth::get_token_for_user( $user, $remember ? 52 : 4 );
		if ( is_wp_error( $token ) ) {
			return $this->respondWithError( $token );
		}

		/** The token is signed, now create the object with no sensible user data to the client*/
		$data = [ 'token' => $token, 'user' => new User( $user ) ];

		return $this->respondOK( $data );
	}

	/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function validate_token( $request ) {
		$token = $request->get_param( 'token' );

		$validate = Auth::validate_token( $token );
		if ( is_wp_error( $validate ) ) {
			return $this->respondWithError( $validate );
		}

		$user = get_user_by( 'id', $validate->data->user->id );
		if ( ! $user instanceof \WP_User ) {
			return $this->respondNotFound( 'user_not_found', 'No user found' );
		}

		$response = [
			'url'        => $validate->iss,
			'issued_at'  => date( 'Y-m-d\TH:i:s', $validate->iat ),
			'valid_from' => date( 'Y-m-d\TH:i:s', $validate->nbf ),
			'valid_to'   => date( 'Y-m-d\TH:i:s', $validate->exp ),
			'user'       => new User( $user ),
		];

		return $this->respondOK( $response );
	}

	/**
	 * Add or remove auth provider
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function create_auth_provider( $request ) {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$provider    = $request->get_param( 'provider' );
		$provider_id = $request->get_param( 'provider_id' );
		$email       = $request->get_param( 'email' );
		$mode        = $request->get_param( 'mode' );
		$mode        = in_array( $mode, [ 'link', 'unlink' ] ) ? $mode : 'link';

		if ( empty( $provider ) || empty( $provider_id ) ) {
			return $this->respondUnprocessableEntity( null, 'Both provider and provider_id are required.' );
		}

		if ( ! in_array( $provider, SocialAuthProvider::get_providers() ) ) {
			return $this->respondUnprocessableEntity( null, 'Provider does not support.' );
		}

		if ( 'unlink' == $mode ) {
			$unlink = SocialAuthProvider::unlink( [ 'provider' => $provider, 'provider_id' => $provider_id, ] );
			if ( $unlink ) {
				return $this->respondOK( 'Social auth provider has been removed.' );
			}

			return $this->respondInternalServerError();
		}
		SocialAuthProvider::create_or_update( [
			'user_id'       => $user->ID,
			'provider'      => $provider,
			'provider_id'   => $provider_id,
			'email_address' => $email,
		] );

		return $this->respondOK();
	}
}
