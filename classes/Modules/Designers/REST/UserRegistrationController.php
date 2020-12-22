<?php

namespace Yousaidit\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yousaidit\Modules\Designers\Emails\NewDesignerEmail;

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
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
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

	/**
	 * @return array
	 */
	public function create_item_params() {
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
