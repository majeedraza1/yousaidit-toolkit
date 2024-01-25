<?php

namespace YouSaidItCards\Modules\Reminders\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;
use YouSaidItCards\REST\ApiController;

class AdminReminderGroupController extends ApiController {

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
		register_rest_route( $this->namespace, 'admin/reminders/groups', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			]
		] );
		register_rest_route( $this->namespace, 'admin/reminders/groups/(?P<id>\d+)', [
			'args' => [
				'id' => [
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
				],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			]
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$items = ( new ReminderGroup )->find_multiple();

		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );

		$cats = [];
		foreach ( $terms as $term ) {
			$cats[] = [ 'label' => sprintf( '%s (%s)', $term->name, $term->count ), 'value' => $term->term_id ];
		}

		return $this->respondOK( [
			'items'        => $items,
			'product_cats' => $cats
		] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$data = $this->prepare_item_for_database( $request );
		$id   = ( new ReminderGroup() )->create( $data );

		return $this->respondOK( [ 'id' => $id ] );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$class = new ReminderGroup();

		$item = $class->find_single( $id );
		if ( ! $item ) {
			return $this->respondNotFound();
		}

		$data       = $this->prepare_item_for_database( $request );
		$data['id'] = $id;

		$class->update( $data );

		return $this->respondOK( [ 'id' => $id ] );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$id = $request->get_param( 'id' );

		$class = new ReminderGroup();

		$item = $class->find_single( $id );
		if ( ! $item ) {
			return $this->respondNotFound();
		}

		if ( ! $class->delete( $id ) ) {
			return $this->respondInternalServerError();
		}

		return $this->respondOK( [ 'id' => $id ] );
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ) {
		$title              = $request->get_param( 'title' );
		$cta_link           = $request->get_param( 'cta_link' );
		$occasion_date      = $request->get_param( 'occasion_date' );
		$menu_order         = (int) $request->get_param( 'menu_order' );
		$product_categories = $request->get_param( 'product_categories' );
		if ( is_string( $product_categories ) ) {
			$product_categories = explode( ',', $product_categories );
		}
		if ( is_array( $product_categories ) ) {
			$product_categories = array_filter( $product_categories );
			$product_categories = count( $product_categories ) ? array_map( 'intval', $product_categories ) : [];
		}

		return [
			'title'              => $title,
			'product_categories' => $product_categories,
			'cta_link'           => $cta_link,
			'menu_order'         => $menu_order,
			'occasion_date'      => $occasion_date,
		];
	}
}
