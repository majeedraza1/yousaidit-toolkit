<?php

namespace YouSaidItCards\Modules\FontManager\REST;

use Stackonet\WP\Framework\Media\UploadedFile;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\DesignerFont;
use YouSaidItCards\REST\ApiController;

class DesignerFontController extends ApiController {
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

	public function register_routes() {
		register_rest_route( $this->namespace, '/designers/fonts', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'auth_user_permissions_check' ],
				'args'                => $this->get_collection_params(),
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'auth_user_permissions_check' ],
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
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$user_id     = get_current_user_id();
		$fonts       = DesignerFont::get_fonts( $user_id, $per_page, $page );
		$total_items = DesignerFont::get_total_fonts_count( $user_id );
		$pagination  = static::get_pagination_data( $total_items, $per_page, $page );

		return $this->respondOK( [
			'items'               => $fonts,
			'pagination'          => $pagination,
			'pre_installed_fonts' => Font::get_pre_installed_fonts_with_permissions(),
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
		$file      = UploadedFile::parse_uploaded_files( $request->get_file_params() );
		$font_file = $file['font_file'] ?? false;
		if ( ! $font_file instanceof UploadedFile ) {
			return $this->respondUnprocessableEntity();
		}

		$mime_types = [ 'font/sfnt', 'font/ttf' ];
		if ( ! in_array( $font_file->get_mime_type(), $mime_types, true ) ) {
			return $this->respondUnprocessableEntity( null, 'Unsupported file format. Only TTF file is allowed.' );
		}

		$font_family = $request->get_param( 'font_family' );
		if ( empty( $font_family ) ) {
			return $this->respondUnprocessableEntity( null, 'Font family cannot be empty.' );
		}

		$font_group = $request->get_param( 'group' );
		if ( empty( $font_group ) ) {
			return $this->respondUnprocessableEntity( null, 'Font group cannot be empty.' );
		}

		$designer_id = get_current_user_id();
		$filename    = sprintf( 'designer-%s-%s',
			$designer_id,
			sanitize_file_name( $font_file->get_client_filename() )
		);
		$target      = join( DIRECTORY_SEPARATOR, [ Font::get_base_directory(), $filename ] );
		if ( ! file_exists( $target ) ) {
			$font_file->move_to( $target );
			// Set correct file permissions.
			$stat  = stat( dirname( $target ) );
			$perms = $stat['mode'] & 0000666;
			@chmod( $target, $perms );
		}

		$data = [
			'slug'        => sanitize_title_with_dashes( $font_family, '', 'save' ),
			'font_family' => sanitize_text_field( $font_family ),
			'font_file'   => $filename,
			'group'       => $font_group,
			'designer_id' => get_current_user_id(),
			'for_public'  => false,
		];

		$font_id = DesignerFont::create( $data );
		if ( $font_id ) {
			$font = DesignerFont::find_single( $font_id );

			return $this->respondCreated( [
				'fontInfo' => $font->to_array(),
			] );
		}

		return $this->respondInternalServerError();
	}
}