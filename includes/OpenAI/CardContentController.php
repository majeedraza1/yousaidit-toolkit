<?php

namespace YouSaidItCards\OpenAI;

use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

/**
 * CardContentController class
 */
class CardContentController extends ApiController {
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
		register_rest_route( $this->namespace, 'ai-content-generator', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_item' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'occasion'  => [
					'type'              => 'string',
					'required'          => true,
					'enum'              => wp_list_pluck( Setting::get_occasions(), 'slug' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'recipient' => [
					'type'              => 'string',
					'required'          => true,
					'enum'              => wp_list_pluck( Setting::get_recipients(), 'slug' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'topic'     => [
					'type'              => 'string',
					'required'          => false,
					'enum'              => array_merge( [ "", '__custom' ],
						wp_list_pluck( Setting::get_topics(), 'slug' ) ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'poem'      => [
					'type'              => 'boolean',
					'default'           => false,
					'required'          => false,
					'sanitize_callback' => [ Validate::class, 'checked' ],
					'validate_callback' => 'rest_validate_request_arg',
				],
			],
		] );
		register_rest_route( $this->namespace, 'ai-content-generator/settings', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_setting_item' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'occasions'  => [
				],
				'recipients' => [
				],
				'topics'     => [
				],
			],
		] );
	}

	/**
	 * Generate card content from OpenAI
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$topic        = $request->get_param( 'topic' );
		$custom_topic = $request->get_param( 'custom_topic' );
		if ( ( empty( $topic ) || '__custom' === $topic ) && ! empty( $custom_topic ) ) {
			$topic = $custom_topic;
		}

		$option = new CardOption();
		$option->set_occasion( $request->get_param( 'occasion' ) );
		$option->set_recipient( $request->get_param( 'recipient' ) );
		if ( ! empty( $topic ) ) {
			$option->set_topic( $topic );
		}
		$option->set_type( $request->get_param( 'poem' ) ? 'poem' : 'message' );

		if ( empty( $option->get_instruction() ) ) {
			return $this->respondUnprocessableEntity();
		}

		$message = Client::recreate_article( $option );
		if ( is_wp_error( $message ) ) {
			return $this->respondWithWpError( $message );
		}

		$message  = preg_split( '/\r\n|\r|\n/', stripslashes( $message ) );
		$messages = [];
		foreach ( $message as $item ) {
			$messages[] = trim( $item );
		}

		$response = [
			'message' => $messages,
		];
		if ( current_user_can( 'manage_options' ) ) {
			$response['instruction'] = $option->get_instruction();
			$response['options']     = $option->to_array();
		}

		return $this->respondOK( $response );
	}

	public function create_setting_item( \WP_REST_Request $request ): WP_REST_Response {
		$occasions  = Setting::sanitize_setting( $request->get_param( 'occasions' ) );
		$recipients = Setting::sanitize_setting( $request->get_param( 'recipients' ) );
		$topics     = Setting::sanitize_setting( $request->get_param( 'topics' ) );

		if ( count( $occasions ) ) {
			Setting::update_occasions( $occasions );
		}
		if ( count( $recipients ) ) {
			Setting::update_recipients( $recipients );
		}
		if ( count( $topics ) ) {
			Setting::update_topics( $topics );
		}

		return $this->respondOK( [
			'occasions'  => $occasions,
			'recipients' => $recipients,
			'topics'     => $topics,
		] );
	}
}
