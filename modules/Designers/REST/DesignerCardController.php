<?php

namespace YouSaidItCards\Modules\Designers\REST;

use ImagickException;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\DynamicCard;
use YouSaidItCards\Modules\Designers\Emails\CardRemoveRequestEmail;
use YouSaidItCards\Modules\Designers\Helper;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\DynamicCard\EnvelopeColours;
use YouSaidItCards\Utils;

defined( 'ABSPATH' ) || exit;

class DesignerCardController extends ApiController {

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
	 * Create item permissions check
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		if ( Utils::is_current_user_designer() || current_user_can( 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get statuses data with count
	 *
	 * @param  array  $counts
	 * @param  string  $status
	 *
	 * @return array
	 */
	private static function get_statuses_with_counts( array $counts, $status ) {
		$_statuses = DesignerCard::get_available_statuses();
		$statuses  = [
			[
				'key'    => 'all',
				'label'  => 'All',
				'count'  => $counts['all'] ?? 0,
				'active' => 'all' == $status,
			]
		];
		foreach ( $_statuses as $key => $label ) {
			if ( in_array( $key, [ 'need-modification', 'draft', 'trash' ] ) ) {
				continue;
			}
			$statuses[] = [
				'key'    => $key,
				'label'  => $label,
				'count'  => $counts[ $key ] ?? 0,
				'active' => $key == $status,
			];
		}

		return $statuses;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/cards', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/standard-cards', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_standard_card' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/photo-cards', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_photo_card' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/text-cards', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_text_card' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/mug', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_mug_card' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/cards/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/cards/(?P<id>\d+)/requests', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item_requests' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers/(?P<user_id>\d+)/cards/(?P<id>\d+)/comments', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_comments' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return $this->respondUnauthorized();
		}

		$current_user = wp_get_current_user();
		$user_id      = (int) $request->get_param( 'user_id' );

		if ( $user_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$search   = $request->get_param( 'search' );
		$status   = $request->get_param( 'status' );
		$status   = in_array( $status, DesignerCard::get_valid_statuses() ) ? $status : 'all';

		$items = ( new DesignerCard() )->find( [
			'designer_user_id' => $user_id,
			'search'           => $search,
			'per_page'         => $per_page,
			'paged'            => $page,
			'status'           => $status,
		] );

		$designer   = new CardDesigner( $current_user );
		$counts     = ( new DesignerCard() )->count_records( $user_id );
		$pagination = static::get_pagination_data( $counts[ $status ], $per_page, $page );
		$response   = [
			'items'                => $items,
			'counts'               => $counts,
			'pagination'           => $pagination,
			'maximum_allowed_card' => $designer->get_maximum_allowed_card(),
			'can_add_dynamic_card' => $designer->can_add_dynamic_card(),
			'total_cards'          => $designer->get_total_cards_count(),
		];

		$response['statuses'] = static::get_statuses_with_counts( $counts, $status );

		return $this->respondOK( $response );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		return $this->respondOK();
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_comments( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user_id = (int) $request->get_param( 'user_id' );

		if ( $user_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$card_id = (int) $request->get_param( 'id' );
		$card    = ( new DesignerCard() )->find_by_id( $card_id );

		if ( ! $card instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		return $this->respondOK( [ 'comments' => $card->get_comments() ] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user_id   = (int) $request->get_param( 'user_id' );
		$card_type = $request->get_param( 'card_type' );
		$card_type = in_array( $card_type, [ 'dynamic', 'static' ] ) ? $card_type : 'static';
		$is_static = 'static' == $card_type;

		if ( $user_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$required_params = [ 'title', 'sizes', 'categories_ids' ];

		if ( $is_static ) {
			$required_params[] = 'image_id';
		} else {
			$required_params[] = 'dynamic_card_payload';
		}

		$errors = [];

		foreach ( $required_params as $param ) {
			if ( empty( $request->get_param( $param ) ) ) {
				$errors[ $param ][] = ucfirst( str_replace( '_', ' ', $param ) ) . ' is required.';
			}
		}

		if ( count( $errors ) ) {
			return $this->respondUnprocessableEntity( 'rest_missing_callback_param',
				'Missing parameter(s): ' . implode( ', ', array_keys( $errors ) ),
				$errors
			);
		}

		$card_title      = $request->get_param( 'title' );
		$description     = $request->get_param( 'description' );
		$card_sizes      = $request->get_param( 'sizes' );
		$categories_ids  = $request->get_param( 'categories_ids' );
		$tags_ids        = $request->get_param( 'tags_ids' );
		$attributes      = $request->get_param( 'attributes' );
		$image_id        = (int) $request->get_param( 'image_id' );
		$card_images_ids = $request->get_param( 'gallery_images_ids' );
		$pdf_ids         = $request->get_param( 'pdf_ids' );

		if ( ! empty( $description ) ) {
			$allowed_html = [ 'strong' => [], 'em' => [], 'ul' => [], 'ol' => [], 'li' => [], 'b' => [] ];
			$description  = wp_kses( $description, $allowed_html );
		}

		if ( strlen( $card_title ) < 2 ) {
			$errors['card_title'][] = 'Cart title too short.';
		}

		foreach ( $card_sizes as $card_size ) {
			if ( ! in_array( $card_size, [ 'square', 'a4', 'a5', 'a6' ] ) ) {
				$errors['card_size'][] = 'Card size is not one of square, a4, a5, a6.';
			}
		}

		if ( ! ( is_array( $categories_ids ) && count( $categories_ids ) > 0 ) ) {
			$errors['categories_ids'][] = 'Categories ids is required.';
		}

		$categories_ids = array_map( 'intval', $categories_ids );
		$tags_ids       = is_array( $tags_ids ) ? array_map( 'intval', $tags_ids ) : [];

		$valid_cats_ids = DesignerCard::get_valid_categories_ids();
		$valid_tags_ids = DesignerCard::get_valid_tags_ids();

		foreach ( $categories_ids as $category_id ) {
			if ( ! in_array( $category_id, $valid_cats_ids ) ) {
				$errors['categories_ids'][] = 'Category id ' . $category_id . ' is not valid.';
			}
		}

		foreach ( $tags_ids as $tag_id ) {
			if ( ! in_array( $tag_id, $valid_tags_ids ) ) {
				$errors['tags_ids'][] = 'Tag id ' . $tag_id . ' is not valid.';
			}
		}


		if ( $image_id ) {
			$_post = get_post( $image_id );
			if ( ! ( $_post instanceof WP_Post && $_post->post_type == 'attachment' && intval( $_post->post_author ) == $user_id ) ) {
				$errors['attachment_ids'][] = 'Image id ' . $image_id . ' is not valid.';
			}
		}

		if ( $is_static ) {
			foreach ( $pdf_ids as $attachment_ids ) {
				foreach ( $attachment_ids as $attachment_id ) {
					$_post = get_post( $attachment_id );
					if ( ! ( $_post instanceof WP_Post && $_post->post_type == 'attachment' && intval( $_post->post_author ) == $user_id ) ) {
						$errors['attachment_ids'][] = 'PDF id ' . $attachment_id . ' is not valid.';
					}
				}
			}
		}

		if ( count( $errors ) ) {
			return $this->respondUnprocessableEntity( 'rest_invalid_param',
				'One or more parameters has an error. Fix and try again.', $errors );
		}

		$_pdf_ids = [];
		foreach ( $pdf_ids as $size_key => $attachment_ids ) {
			$_pdf_ids[ $size_key ] = array_map( 'intval', $attachment_ids );
		}

		$_attributes = [];
		foreach ( $attributes as $attribute_name => $attribute_ids ) {
			$_attributes[ $attribute_name ] = array_map( 'intval', $attribute_ids );
		}

		$attachment_ids = [
			'image_id'           => $image_id,
			'gallery_images_ids' => is_array( $card_images_ids ) ? array_map( 'intval', $card_images_ids ) : [],
			'pdf_ids'            => $_pdf_ids,
		];

		$market_places = $request->get_param( 'market_places' );
		$market_places = is_array( $market_places ) ? $market_places : [];

		$data = [
			'card_type'        => $card_type,
			'description'      => $description,
			'card_title'       => $card_title,
			'card_sizes'       => $card_sizes,
			'categories_ids'   => $categories_ids,
			'tags_ids'         => $tags_ids,
			'attachment_ids'   => $attachment_ids,
			'attributes'       => $_attributes,
			'designer_user_id' => $user_id,
			'rude_card'        => Validate::checked( $request->get_param( 'rude_card' ) ) ? 'yes' : 'no',
			'status'           => 'processing',
			'suggest_tags'     => sanitize_textarea_field( $request->get_param( 'suggest_tags' ) ),
			'market_places'    => map_deep( $market_places, 'sanitize_text_field' ),
		];

		if ( ! $is_static ) {
			$data['dynamic_card_payload'] = $request->get_param( 'dynamic_card_payload' );
		}

		$id = ( new DesignerCard() )->create( $data );
		if ( $id ) {
			$item = ( new DesignerCard() )->find_by_id( $id );

			update_user_meta( $user_id, '_is_card_designer', 'yes' );

			return $this->respondCreated( $item );
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function create_standard_card( WP_REST_Request $request ): WP_REST_Response {
		$request->set_param( 'card_type', 'static' );
		$data = $this->get_sanitized_card_options( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondWithWpError( $data );
		}

		$user_id = get_current_user_id();
		$id      = ( new DesignerCard() )->create( $data );
		if ( $id ) {
			$item = ( new DesignerCard() )->find_by_id( $id );

			update_user_meta( $user_id, '_is_card_designer', 'yes' );

			try {
				$im = EnvelopeColours::image_to_envelope( $item->get_image_id() );
				Helper::generate_product_image( $im, $item );
			} catch ( ImagickException $e ) {
				Logger::log( $e );
			}

			return $this->respondCreated( $item );
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function create_mug_card( WP_REST_Request $request ): WP_REST_Response {
		$request->set_param( 'card_type', 'static' );
		$data = $this->get_sanitized_card_options( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondWithWpError( $data );
		}

		$data['card_type'] = 'mug';

		$user_id = get_current_user_id();
		$id      = ( new DesignerCard() )->create( $data );
		if ( $id ) {
			$item = ( new DesignerCard() )->find_by_id( $id );

			update_user_meta( $user_id, '_is_card_designer', 'yes' );

			return $this->respondCreated( $item );
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function create_photo_card( WP_REST_Request $request ): WP_REST_Response {
		$dynamic_card_payload = $request->get_param( 'dynamic_card_payload' );
		if ( ! is_array( $dynamic_card_payload ) ) {
			return $this->respondUnprocessableEntity();
		}
		$request->set_param( 'card_type', 'dynamic' );
		$data = $this->get_sanitized_card_options( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondWithWpError( $data );
		}
		$data['dynamic_card_payload'] = $dynamic_card_payload;

		$user_id = get_current_user_id();
		$id      = DesignerCard::create( $data );
		if ( $id ) {
			$item = DesignerCard::find_single( $id );

			update_user_meta( $user_id, '_is_card_designer', 'yes' );

			try {
				$pdf_id        = DynamicCard::create_card_pdf( $item );
				$new_file_path = get_attached_file( $pdf_id );
				DynamicCard::clone_pdf_to_jpg( $item, $new_file_path );
				DynamicCard::pdf_to_image( $new_file_path );
			} catch ( ImagickException $e ) {
				Logger::log( $e );
			}

			return $this->respondCreated( $item );
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function create_text_card( WP_REST_Request $request ): WP_REST_Response {
		$dynamic_card_payload = $request->get_param( 'dynamic_card_payload' );
		if ( ! is_array( $dynamic_card_payload ) ) {
			return $this->respondUnprocessableEntity();
		}
		$request->set_param( 'card_type', 'dynamic' );
		$data = $this->get_sanitized_card_options( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondWithWpError( $data );
		}
		$data['dynamic_card_payload'] = $dynamic_card_payload;

		$user_id = get_current_user_id();
		$id      = DesignerCard::create( $data );
		if ( $id ) {
			$item = DesignerCard::find_single( $id );

			update_user_meta( $user_id, '_is_card_designer', 'yes' );

			try {
				$pdf_id        = DynamicCard::create_card_pdf( $item );
				$new_file_path = get_attached_file( $pdf_id );
				DynamicCard::clone_pdf_to_jpg( $item, $new_file_path );
				DynamicCard::pdf_to_image( $new_file_path );
			} catch ( ImagickException $e ) {
				Logger::log( $e );
			}

			return $this->respondCreated( $item );
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Get sanitized card options
	 *
	 * @param  WP_REST_Request  $request  The request details.
	 *
	 * @return array|WP_Error
	 */
	public function get_sanitized_card_options( WP_REST_Request $request ) {
		$user_id   = get_current_user_id();
		$card_type = $request->get_param( 'card_type' );
		$card_type = in_array( $card_type, [ 'dynamic', 'static' ] ) ? $card_type : 'static';
		$is_static = 'static' == $card_type;
		$errors    = [];

		$description = $request->get_param( 'description' );
		if ( ! empty( $description ) ) {
			$allowed_html = [ 'strong' => [], 'em' => [], 'ul' => [], 'ol' => [], 'li' => [], 'b' => [] ];
			$description  = wp_kses( $description, $allowed_html );
		}

		$card_title = $request->get_param( 'title' );
		if ( strlen( $card_title ) < 2 ) {
			$errors['card_title'][] = 'Cart title too short.';
		}

		$card_sizes = $request->get_param( 'sizes' );
		foreach ( $card_sizes as $card_size ) {
			if ( ! in_array( $card_size, [ 'square', 'a4', 'a5', 'a6' ] ) ) {
				$errors['card_size'][] = 'Card size is not one of square, a4, a5, a6.';
			}
		}

		$categories_ids = $request->get_param( 'categories_ids' );
		$categories_ids = is_array( $categories_ids ) ? array_map( 'intval', $categories_ids ) : [];

		$tags_ids = $request->get_param( 'tags_ids' );
		$tags_ids = is_array( $tags_ids ) ? array_map( 'intval', $tags_ids ) : [];


		$valid_cats_ids = DesignerCard::get_valid_categories_ids();
		foreach ( $categories_ids as $category_id ) {
			if ( ! in_array( $category_id, $valid_cats_ids ) ) {
				$errors['categories_ids'][] = 'Category id ' . $category_id . ' is not valid.';
			}
		}

		$valid_tags_ids = DesignerCard::get_valid_tags_ids();
		foreach ( $tags_ids as $tag_id ) {
			if ( ! in_array( $tag_id, $valid_tags_ids ) ) {
				$errors['tags_ids'][] = 'Tag id ' . $tag_id . ' is not valid.';
			}
		}

		$image_id = (int) $request->get_param( 'image_id' );
		if ( $image_id ) {
			$_post = get_post( $image_id );
			if ( ! ( $_post instanceof WP_Post && $_post->post_type == 'attachment' && intval( $_post->post_author ) == $user_id ) ) {
				$errors['attachment_ids'][] = 'Image id ' . $image_id . ' is not valid.';
			}
		} elseif ( $is_static ) {
			$errors['attachment_ids'][] = 'Image id is required for static card.';
		}

		$_attributes = [];
		$attributes  = $request->get_param( 'attributes' );
		foreach ( $attributes as $attribute_name => $attribute_ids ) {
			$_attributes[ $attribute_name ] = array_map( 'intval', $attribute_ids );
		}

		$attachment_ids = [
			'image_id'           => $image_id,
			'gallery_images_ids' => [],
			'pdf_ids'            => 0,
		];

		$market_places = $request->get_param( 'market_places' );
		$market_places = is_array( $market_places ) ? $market_places : [];

		if ( count( $errors ) ) {
			return new WP_Error( 'rest_invalid_param', 'One or more parameters has an error. Fix and try again.',
				$errors );
		}

		return [
			'card_type'        => $card_type,
			'description'      => $description,
			'card_title'       => $card_title,
			'card_sizes'       => $card_sizes,
			'categories_ids'   => $categories_ids,
			'tags_ids'         => $tags_ids,
			'attachment_ids'   => $attachment_ids,
			'thumbnail_id'     => $image_id,
			'attributes'       => $_attributes,
			'designer_user_id' => $user_id,
			'rude_card'        => Validate::checked( $request->get_param( 'rude_card' ) ) ? 'yes' : 'no',
			'status'           => 'processing',
			'suggest_tags'     => sanitize_textarea_field( $request->get_param( 'suggest_tags' ) ),
			'market_places'    => map_deep( $market_places, 'sanitize_text_field' ),
		];
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item_requests( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user_id = (int) $request->get_param( 'user_id' );

		if ( $user_id != $current_user->ID ) {
			return $this->respondUnauthorized();
		}
		$card_id = (int) $request->get_param( 'id' );
		$card    = ( new DesignerCard() )->find_by_id( $card_id );

		if ( ! $card instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		$request_for = $request->get_param( 'request_for' );
		$message     = $request->get_param( 'message' );

		if ( ! empty( $message ) ) {
			try {
				$mailer = new CardRemoveRequestEmail( new CardDesigner( $current_user ), $card );
				$mailer->set_designer_message( $message );
				$mailer->set_request_for( $request_for );
				$mailer->send_email();
			} catch ( \Exception $e ) {
			}
		}

		return $this->respondOK( $request->get_params() );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		return $this->respondOK();
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$user_id = (int) $request->get_param( 'user_id' );

		if ( $user_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id     = (int) $request->get_param( 'id' );
		$action = $request->get_param( 'action' );

		if ( 'delete' == $action ) {
			( new DesignerCard() )->delete( $id );
		} elseif ( 'restore' == $action ) {
			( new DesignerCard() )->restore( $id );
		} else {
			( new DesignerCard() )->trash( $id );
		}

		return $this->respondOK();
	}

	/**
	 * Retrieves the query params for create item
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_create_item_params() {
		return array(
			'card_title'      => array(
				'description'       => __( 'Limit results to those matching a string.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_categories' => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_tags'       => array(
				'description'       => __( 'Maximum number of items to be returned in result set.' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_attributes' => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_sizes'      => array(
				'description'       => __( 'Limit results to those matching a string.' ),
				'type'              => 'array',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_pdf_ids'    => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_images_ids' => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'card_image_id'   => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'number',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
