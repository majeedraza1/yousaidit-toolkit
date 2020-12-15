<?php

namespace YouSaidItCards\Modules\Auth;

use Exception;
use Firebase\JWT\JWT;
use WP_Error;
use WP_User;
use YouSaidItCards\Modules\Auth\Models\SocialAuthProvider;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

class Auth {

	/**
	 * Secret key
	 *
	 * @var string
	 */
	protected static $secret_key = null;

	/**
	 * CORS enabled by default
	 *
	 * @var bool
	 */
	protected static $enable_cors = true;

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var WP_Error
	 */
	private static $jwt_error;

	/**
	 * Init config
	 */
	public static function init_config() {
		if ( defined( 'JWT_AUTH_SECRET_KEY' ) ) {
			static::$secret_key = JWT_AUTH_SECRET_KEY;
		} else if ( defined( 'SECURE_AUTH_KEY' ) ) {
			static::$secret_key = SECURE_AUTH_KEY;
		}

		if ( defined( 'JWT_AUTH_CORS_ENABLE' ) ) {
			static::$enable_cors = JWT_AUTH_CORS_ENABLE;
		}
	}

	/**
	 * Init frontend functionality
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', [ self::$instance, 'add_cors_support' ] );
			add_filter( 'determine_current_user', [ self::$instance, 'determine_current_user' ], 20 );
			add_filter( 'rest_pre_dispatch', [ self::$instance, 'jwt_auth_error_response' ] );
		}

		return self::$instance;
	}

	/**
	 * Add CORS support to the request.
	 */
	public static function add_cors_support() {
		static::init_config();
		if ( static::$enable_cors ) {
			$allow_headers = [
				'Access-Control-Allow-Headers',
				'Content-Type',
				'Authorization',
				'X-WP-Nonce',
				'X-Auth-Token'
			];
			$headers       = apply_filters( 'jwt_auth_cors_allow_headers', implode( ', ', $allow_headers ) );
			header( sprintf( 'Access-Control-Allow-Headers: %s', $headers ) );
		}
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the
	 * token send.
	 *
	 * @param int|bool $user_id Logged User ID
	 *
	 * @return int|bool
	 */
	public static function determine_current_user( $user_id ) {

		// Don't authenticate twice
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		/**
		 * This hook only should run on the REST API requests to determine
		 * if the user in the Token (if any) is valid, for any other
		 * normal call ex. wp-admin/.* return the user.
		 **/
		$rest_api_slug = rest_get_url_prefix();
		$valid_api_uri = strpos( $_SERVER['REQUEST_URI'], $rest_api_slug );
		if ( ! $valid_api_uri ) {
			return $user_id;
		}

		/*
		 * if the request URI is for validate the token don't do anything,
		 * this avoid double calls to the validate_token function.
		 */
		$validate_uri = strpos( $_SERVER['REQUEST_URI'], 'token/validate' );
		if ( $validate_uri > 0 ) {
			return $user_id;
		}

		// If REST nonce authentication available then use it
		if ( ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			return $user_id;
		}

		$token = static::get_auth_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$token = self::validate_token( $token );

		if ( is_wp_error( $token ) ) {
			if ( $token->get_error_code() != 'jwt_auth_no_auth_header' ) {
				/** If there is a error, store it to show it after see rest_pre_dispatch */
				static::$jwt_error = $token;

				return $user_id;
			} else {
				return $user_id;
			}
		}

		/** Everything is ok, return the user ID stored in the token*/
		return $token->data->user->id;
	}

	/**
	 * Filter to hook the rest_pre_dispatch, if the is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param mixed $result
	 *
	 * @return mixed
	 */
	public static function jwt_auth_error_response( $result ) {
		if ( is_wp_error( static::$jwt_error ) ) {
			return apply_filters( 'jwt_auth_error_response', static::$jwt_error );
		}

		return $result;
	}

	/**
	 * Get token for a user
	 *
	 * @param WP_User $user
	 * @param int     $for_week
	 *
	 * @return string|WP_Error
	 */
	public static function get_token_for_user( $user, $for_week = 4 ) {
		static::init_config();

		/** First thing, check the secret key if not exist return a error*/
		if ( empty( static::$secret_key ) ) {
			return new WP_Error( 'jwt_auth_bad_config',
				__( 'JWT is not configured properly, please contact the admin', 'stackonet-jwt-auth' ),
				array( 'status' => 403, )
			);
		}

		/** Valid credentials, the user exists create the according Token */
		$issued_at  = time();
		$for_week   = max( 1, intval( $for_week ) );
		$not_before = apply_filters( 'jwt_auth_not_before', $issued_at, $issued_at );
		$expire     = apply_filters( 'jwt_auth_expire', $issued_at + ( WEEK_IN_SECONDS * $for_week ), $issued_at );

		$token = array(
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->ID,
				),
			),
		);

		/** Let the user modify the token data before the sign. */
		$payload = apply_filters( 'jwt_auth_token_before_sign', $token, $user );

		return JWT::encode( $payload, static::$secret_key );
	}

	/**
	 * Generate token
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return array|WP_Error
	 */
	public static function generate_token( $username, $password ) {
		static::init_config();

		/** First thing, check the secret key if not exist return a error*/
		if ( empty( static::$secret_key ) ) {
			return new WP_Error( 'jwt_auth_bad_config',
				__( 'JWT is not configured properly, please contact the admin', 'stackonet-jwt-auth' ),
				[ 'status' => 403, ]
			);
		}

		/** Try to authenticate the user with the passed credentials*/
		/** @var WP_User|WP_Error $user */
		$user = wp_authenticate( $username, $password );

		/** If the authentication fails return a error*/
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$token = static::get_token_for_user( $user );

		return [ 'token' => $token, 'user' => $user ];
	}

	/**
	 * Main validation function, this function try to get the Authentication
	 * headers and decoded.
	 *
	 * @param string $token
	 *
	 * @return object|WP_Error
	 */
	public static function validate_token( $token ) {
		static::init_config();

		if ( empty( $token ) ) {
			return new WP_Error( 'jwt_auth_bad_auth_header',
				__( 'Authorization header malformed.', 'stackonet-jwt-auth' ),
				array( 'status' => 403, )
			);
		}

		/** First thing, check the secret key if not exist return a error*/
		if ( empty( static::$secret_key ) ) {
			return new WP_Error( 'jwt_auth_bad_config',
				__( 'JWT is not configured properly, please contact the admin', 'stackonet-jwt-auth' ),
				[ 'status' => 403, ]
			);
		}

		/** Try to decode the token */
		try {
			$token = JWT::decode( $token, static::$secret_key, array( 'HS256' ) );
			/** The Token is decoded now validate the iss */
			if ( $token->iss != get_bloginfo( 'url' ) ) {
				/** The iss do not match, return error */
				return new WP_Error( 'jwt_auth_bad_iss',
					__( 'The iss do not match with this server', 'stackonet-jwt-auth' ),
					[ 'status' => 403, ]
				);
			}
			/** So far so good, validate the user id in the token */
			if ( ! isset( $token->data->user->id ) ) {
				/** No user id in the token, abort!! */
				return new WP_Error( 'jwt_auth_bad_request',
					__( 'User ID not found in the token', 'stackonet-jwt-auth' ),
					[ 'status' => 403, ]
				);
			}

			/** Everything looks good return the decoded token if the $output is false */
			return $token;
		} catch ( Exception $e ) {
			/** Something is wrong trying to decode the token, send back the error */
			return new WP_Error( 'jwt_auth_invalid_token', $e->getMessage(),
				array( 'status' => 403, )
			);
		}
	}

	/**
	 * Get auth token
	 *
	 * @return string|WP_Error
	 */
	public static function get_auth_token() {
		// Looking for the HTTP_AUTHORIZATION header, if not present just return the user.
		$auth = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : false;

		// Double check for different auth header string (server dependent)
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
		}

		if ( ! $auth ) {
			$auth = isset( $_SERVER['HTTP_X_AUTH_TOKEN'] ) ? $_SERVER['HTTP_X_AUTH_TOKEN'] : false;
		}

		$data_location = 'header';
		// Add support for auth key from query string
		if ( ! $auth ) {
			$auth          = isset( $_REQUEST['_auth_token'] ) ? $_REQUEST['_auth_token'] : false;
			$data_location = 'request_url';
		}

		if ( ! $auth ) {
			return new WP_Error( 'jwt_auth_no_auth_header',
				'Authorization header not found.',
				array( 'status' => 403, )
			);
		}

		if ( '' == $data_location ) {
			$token = $auth;
		} else {
			// The HTTP_AUTHORIZATION is present verify the format if the format is wrong return the user.
			list( $token ) = sscanf( $auth, 'Bearer %s' );
		}
		if ( ! $token ) {
			return new WP_Error( 'jwt_auth_bad_auth_header',
				__( 'Authorization header malformed.', 'stackonet-jwt-auth' ),
				[ 'status' => 403, ]
			);
		}

		return $token;
	}

	/**
	 * Retrieves the query params for the generate token.
	 *
	 * @return array
	 */
	public static function generate_token_rest_params(): array {
		return [
			'username'    => [
				'description'       => __( 'WordPress username.', 'stackonet-jwt-auth' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'password'    => [
				'description'       => __( 'User login password.', 'stackonet-jwt-auth' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'remember'    => [
				'description'       => __( 'Remember user for long time.' ),
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'provider'    => [
				'description'       => __( 'Social Auth provider' ),
				'type'              => 'string',
				'default'           => 'default',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_merge( SocialAuthProvider::get_providers(), [ 'default' ] ),
			],
			'provider_id' => [
				'description'       => __( 'Social Auth provider unique id' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Retrieves the query params for the validate token.
	 *
	 * @return array
	 */
	public static function validate_token_rest_params() {
		return [
			'token' => [
				'description'       => __( 'Auth token', 'stackonet-jwt-auth' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			]
		];
	}

	/**
	 * Prepare user for response
	 *
	 * @param WP_User $user
	 *
	 * @return array
	 */
	public static function prepare_user_for_response( WP_User $user ) {
		return [
			'id'           => $user->ID,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'avatar_url'   => get_avatar_url( $user, [ 'default' => 'mm' ] ),
		];
	}
}
