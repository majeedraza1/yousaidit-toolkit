<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class DesignerAdminController extends ApiController {
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
		register_rest_route( $this->namespace, '/admin/designers', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * @inhericDoc
	 */
	public function create_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$designer_id = $request->get_param( 'designer_id' );

		if ( $request->has_param( 'card_limit' ) ) {
			$card_limit = $request->get_param( 'card_limit' );
			update_user_meta( $designer_id, '_maximum_allowed_card', $card_limit );
		}

		if ( $request->has_param( 'can_add_dynamic_card' ) ) {
			$can_add_dynamic_card = $request->get_param( 'can_add_dynamic_card' );
			update_user_meta(
				$designer_id,
				'_can_add_dynamic_card',
				Validate::checked( $can_add_dynamic_card ) ? 'yes' : 'no'
			);
		}

		return $this->respondOK();
	}
}
