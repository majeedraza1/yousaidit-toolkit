<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

/**
 * AdminApiController class
 */
class AdminApiController extends ApiController {
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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'tree-planting', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
			'args'                => $this->get_collection_params(),
		] );
		register_rest_route( $this->namespace, 'tree-planting/(?P<id>\d+)/sync', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'sync_item' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
		] );
		register_rest_route( $this->namespace, 'tree-planting/sync', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'sync_items' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
		] );
		register_rest_route( $this->namespace, 'tree-planting/pending-orders', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_pending_orders' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
			'args'                => $this->get_collection_params(),
		] );
		register_rest_route( $this->namespace, 'tree-planting/batch', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'perform_batch_operation' ],
			'permission_callback' => [ $this, 'batch_operation_permissions_check' ],
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
		$status   = (string) $request->get_param( 'status' );
		$status   = in_array( $status, [ 'complete', 'processing', 'error' ], true ) ? $status : 'complete';

		$items = TreePlanting::find_multiple( [
			'page'     => $page,
			'per_page' => $per_page,
			'status'   => $status,
		] );

		$total_items = TreePlanting::count_records();
		$pagination  = self::get_pagination_data( $total_items[ $status ], $per_page, $page );

		$statuses = [];
		foreach ( $total_items as $key => $count ) {
			if ( 'all' === $key ) {
				continue;
			}
			$statuses[] = [
				'key'    => $key,
				'label'  => ucfirst( $key ),
				'count'  => $count,
				'active' => $key === $status,
			];
		}


		return $this->respondOK( [
			'items'      => $items,
			'pagination' => $pagination,
			'statuses'   => $statuses
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function get_pending_orders( $request ) {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$query = ShipStationOrder::get_query_builder();
		$query->where( 'tree_planting_id', 0 );
		$query->limit( $per_page );
		$query->page( $page );

		$items = $query->get();

		$total_items = ShipStationOrder::get_query_builder()->where( 'tree_planting_id', 0 )->count();
		$pagination  = self::get_pagination_data( $total_items, $per_page, $page );


		return $this->respondOK( [
			'sql'        => $query->get_query_sql(),
			'items'      => $items,
			'pagination' => $pagination
		] );
	}

	public function sync_items( $request ) {
		$force = Validate::checked( $request->get_param( 'force' ) );
		if ( $force ) {
			BackgroundPurchaseTree::sync( true );

			return $this->respondOK();
		}
		BackgroundPurchaseTree::sync();

		return $this->respondAccepted();
	}

	public function sync_item( \WP_REST_Request $request ) {
		$id            = (int) $request->get_param( 'id' );
		$tree_planting = TreePlanting::find_single( $id );
		if ( ! $tree_planting instanceof TreePlanting ) {
			return $this->respondNotFound();
		}
		if ( $tree_planting->is_complete() ) {
			return $this->respondUnprocessableEntity( null, 'Already synced complete.' );
		}
		$response = EcologiClient::purchase_tree();
		if ( is_wp_error( $response ) ) {
			TreePlanting::update( [
				'id'            => $tree_planting->get_id(),
				'status'        => 'error',
				'error_message' => $response->get_error_message(),
			] );

			return $this->respondWithWpError( $response );
		}

		TreePlanting::update( [
			'id'              => $tree_planting->get_id(),
			'status'          => 'complete',
			'amount'          => $response['amount'],
			'currency'        => $response['currency'],
			'tree_url'        => $response['treeUrl'],
			'name'            => $response['name'],
			'project_details' => $response['projectDetails'],
		] );

		return $this->respondOK( $response );
	}

	public function perform_batch_operation( WP_REST_Request $request ) {
		$action  = $request->get_param( 'action' );
		$payload = $request->get_param( 'payload' );

		if ( 'delete' === $action ) {
			$ids = array_map( 'intval', (array) $payload );
			TreePlanting::batch_delete( $ids );
		}

		return $this->respondAccepted();
	}
}