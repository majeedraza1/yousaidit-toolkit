<?php

namespace YouSaidItCards\Modules\Reminders\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use YouSaidItCards\Modules\Reminders\Models\ReminderQueue;
use YouSaidItCards\REST\ApiController;

class AdminReminderQueueController extends ApiController {
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
		register_rest_route( $this->namespace, 'admin/reminders-queue', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => $this->get_collection_params(),
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$status   = $request->get_param( 'status' );
		if ( 'all' !== $status ) {
			$status = in_array( $status, ReminderQueue::STATUSES ) ? $status : ReminderQueue::STATUS_PENDING;
		}

		$queues = ReminderQueue::list( $page, $per_page, $status );

		$counts     = ( new ReminderQueue() )->count_records();
		$pagination = self::get_pagination_data( $counts[ $status ], $per_page, $page );


		return $this->respondOK( [
			'items'         => $queues,
			'pagination'    => $pagination,
			'status_counts' => $counts
		] );
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
}
