<?php

namespace YouSaidItCards\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\Utilities\MarketPlace;

defined( 'ABSPATH' ) || exit;

class DesignerCommissionAdminController extends ApiController {
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
		register_rest_route( $this->namespace, '/designers-commissions', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers-commissions/(?P<id>\d+)', [
			'args' => [
				'id' => [
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
				],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
			],
		] );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 * @throws \Exception
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$report_type    = $request->get_param( 'report_type' );
		$report_type    = in_array( $report_type, DesignerCommission::get_report_types() ) ? $report_type : 'today';
		$date_from      = $request->get_param( 'date_from' );
		$date_to        = $request->get_param( 'date_to' );
		$page           = (int) $request->get_param( 'page' );
		$per_page       = (int) $request->get_param( 'per_page' );
		$designer_id    = (int) $request->get_param( 'designer_id' );
		$payment_status = $request->get_param( 'payment_status' );
		$order_status   = $request->get_param( 'order_status' );

		list( $from, $to ) = DesignerCommission::get_start_and_end_date( $report_type, $date_from, $date_to );

		$args = [
			'from'     => $from,
			'to'       => $to,
			'per_page' => $per_page,
			'paged'    => $page,
		];

		if ( $designer_id ) {
			$args['designer_id'] = $designer_id;
		}

		if ( $payment_status ) {
			$args['payment_status'] = $payment_status;
		}

		if ( ! empty( $order_status ) ) {
			$args['order_status'] = $order_status;
		}

		$items      = ( new DesignerCommission() )->find( $args );
		$count      = ( new DesignerCommission )->count_records( $args );
		$pagination = static::get_pagination_data( $count, $per_page, $page );

		return $this->respondOK( [
			'commissions'  => $items,
			'pagination'   => $pagination,
			'marketplaces' => MarketPlace::all(),
		] );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$model = new DesignerCommission();
		$item  = $model->find_single( $id );
		if ( ! $item ) {
			return $this->respondNotFound();
		}

		$item->delete( $id );

		return $this->respondOK();
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}
}
