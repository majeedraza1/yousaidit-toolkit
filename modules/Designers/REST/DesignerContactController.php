<?php

namespace YouSaidItCards\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Emails\Mailer;

class DesignerContactController extends ApiController {

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
		register_rest_route( $this->namespace, '/designer-contact', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'create_item' ],
			],
		] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 * @throws \Exception
	 */
	public function create_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );

		$errors = [];

		if ( empty( $subject ) ) {
			$errors['subject'] = 'Subject is required.';
		}

		if ( empty( $message ) ) {
			$errors['message'] = 'Message is required.';
		}

		if ( ! empty( $errors ) ) {
			return $this->respondUnprocessableEntity(
				'required_fields_missing', 'One or more fields has an error. Fix that and try again.',
				$errors );
		}

		$table_data = [
			[ 'label' => 'Subject', 'value' => wp_unslash( $subject ) ],
			[ 'label' => 'Message', 'value' => wp_unslash( $message ) ],
		];

		$mailer = new Mailer();
		$mailer->set_intro_lines( 'New email from designer.' );
		$mailer->set_intro_lines( $mailer->all_fields_table( $table_data ) );
		$mailer->setSubject( 'Support mail from designer' );
		$mailer->setFrom( $current_user->user_email, $current_user->display_name );
		$mailer->setTo( get_option( 'admin_email' ) );
		$mailer->set_salutation( '&nbsp;' );
		$mailer->send();

		return $this->respondCreated();
	}
}
