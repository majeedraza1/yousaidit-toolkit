<?php

namespace YouSaidItCards\Modules\FontManager;

use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

/**
 * AdminFontController
 */
class AdminFontController extends ApiController {
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
		register_rest_route( $this->namespace, '/fonts', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function get_items( $request ) {
		return $this->respondOK( [
			'default_fonts' => Font::get_pre_installed_fonts_with_permissions(),
		] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function create_item( $request ) {
		$slug         = $request->get_param( 'slug' );
		$for_designer = $request->get_param( 'for_designer' );
		$for_public   = $request->get_param( 'for_public' );

		Font::update_pre_installed_fonts_permissions( $slug, [
			'for_designer' => Validate::checked( $for_designer ),
			'for_public'   => Validate::checked( $for_public ),
		] );

		return $this->respondCreated( [
			'default_fonts' => Font::get_pre_installed_fonts_with_permissions(),
		] );
	}
}
