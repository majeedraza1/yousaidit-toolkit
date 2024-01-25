<?php

namespace YouSaidItCards\Modules\FontManager;

use Stackonet\WP\Framework\Media\UploadedFile;
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
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/fonts/custom', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_custom_font' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_custom_font' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
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
			'extra_fonts'   => Font::get_extra_fonts_with_path_and_url(),
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

		if ( Font::is_pre_installed_font( $slug ) ) {
			Font::update_pre_installed_fonts_permissions( $slug, [
				'for_designer' => Validate::checked( $for_designer ),
				'for_public'   => Validate::checked( $for_public ),
			] );
		} else {
			Font::update_extra_font_permission( $slug, [
				'for_designer' => Validate::checked( $for_designer ),
				'for_public'   => Validate::checked( $for_public ),
			] );
		}

		return $this->respondCreated( [
			'default_fonts' => Font::get_pre_installed_fonts_with_permissions(),
			'extra_fonts'   => Font::get_extra_fonts_with_path_and_url(),
		] );
	}

	public function create_custom_font( WP_REST_Request $request ) {
		$file      = UploadedFile::get_uploaded_files();
		$font_file = $file['font_file'] ?? false;
		if ( ! $font_file instanceof UploadedFile ) {
			return $this->respondUnprocessableEntity();
		}

		$filename = sanitize_file_name( $font_file->get_client_filename() );
		$target   = join( DIRECTORY_SEPARATOR, [ Font::get_base_directory(), $filename ] );
		if ( file_exists( $target ) ) {
			return $this->respondUnprocessableEntity( null, 'Font file already exists.' );
		}

		$font_family = $request->get_param( 'font_family' );
		if ( empty( $font_family ) ) {
			return $this->respondUnprocessableEntity( null, 'Font family cannot be empty.' );
		}

		$extra_fonts = Font::get_extra_fonts_with_path_and_url();
		$slug        = sanitize_title_with_dashes( $font_family, '', 'save' );
		if ( isset( $extra_fonts[ $slug ] ) ) {
			return $this->respondUnprocessableEntity( null, 'Already a font exists with that name.' );
		}

		$font_group = $request->get_param( 'group' );
		if ( empty( $font_group ) ) {
			return $this->respondUnprocessableEntity( null, 'Font group cannot be empty.' );
		}

		$for_public   = Validate::checked( $request->get_param( 'for_public' ) );
		$for_designer = Validate::checked( $request->get_param( 'for_designer' ) );

		$font_file->move_to( $target );
		// Set correct file permissions.
		$stat  = stat( dirname( $target ) );
		$perms = $stat['mode'] & 0000666;
		@chmod( $target, $perms );

		$data = [
			'slug'         => sanitize_title_with_dashes( $font_family, '', 'save' ),
			'font_family'  => $font_family,
			'font_file'    => $filename,
			'group'        => $font_group,
			'for_public'   => $for_public,
			'for_designer' => $for_designer,
		];

		$all_fonts = Font::add_extra_font( $data );

		return $this->respondCreated( [
			'extra_fonts' => array_values( $all_fonts ),
		] );
	}

	public function delete_custom_font( WP_REST_Request $request ) {
		$slug        = $request->get_param( 'slug' );
		$extra_fonts = Font::get_extra_fonts_with_path_and_url();
		$slugs       = wp_list_pluck( $extra_fonts, 'slug' );
		if ( ! in_array( $slug, $slugs, true ) ) {
			return $this->respondUnprocessableEntity( null, 'Already a font exists with that name.' );
		}

		$fonts = Font::delete_extra_font( $slug );

		return $this->respondOK( [
			'extra_fonts' => array_values( $fonts ),
		] );
	}
}
