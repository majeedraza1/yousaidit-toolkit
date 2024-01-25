<?php

namespace YouSaidItCards\REST;

use Exception;
use Stackonet\WP\Framework\Emails\Mailer;
use Stackonet\WP\Framework\Supports\Logger;
use WP_REST_Server;

class ContactController extends ApiController {
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
		register_rest_route( $this->namespace, 'feedback', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_item' ],
			'permission_callback' => '__return_true',
			'args'                => $this->get_create_item_params(),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$user = wp_get_current_user();
		if ( $user->exists() ) {
			$request->set_param( 'name', $user->display_name );
			$request->set_param( 'email', $user->user_email );
		}

		$name    = $request->get_param( 'name' );
		$email   = $request->get_param( 'email' );
		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );

		try {
			$mailer = new Mailer();
			$mailer->setFrom( $email, $name );
			$mailer->setTo( get_option( 'admin_email' ) );
			$mailer->setSubject( $subject );
			$mailer->setMessage( $message );
			$mailer->send();
		} catch ( Exception $e ) {
			Logger::log( $e );
		}
	}

	/**
	 * Get create item params
	 *
	 * @return array[]
	 */
	public function get_create_item_params(): array {
		$user = wp_get_current_user();

		return [
			'subject' => [
				'description'       => __( 'Subject' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'message' => [
				'description'       => __( 'Message' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'name'    => [
				'description'       => __( 'Customer name' ),
				'type'              => 'string',
				'required'          => ! $user->exists(),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'email'   => [
				'description'       => __( 'Customer email' ),
				'type'              => 'string',
				'required'          => ! $user->exists(),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
