<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\BackgroundCommissionSync;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\ShipStationApi;
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
		register_rest_route( $this->namespace, '/designers-commissions/sync', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'sync_items' ],
				'args'                => [
					'order_date_start' => [
						'description'       => 'Orders greater than the specified date.',
						'type'              => 'string',
						'sanitize_callback' => [ Sanitize::class, 'date' ],
						'validate_callback' => 'rest_validate_request_arg',
					],
					'order_date_end'   => [
						'description'       => 'Orders less than or equal to the specified date.',
						'type'              => 'string',
						'sanitize_callback' => [ Sanitize::class, 'date' ],
						'validate_callback' => 'rest_validate_request_arg',
					],
					'order_status'     => [
						'description'       => 'Orders status.',
						'type'              => 'string',
						'enum'              => [
							'',
							'awaiting_payment',
							'awaiting_shipment',
							'pending_fulfillment',
							'shipped',
							'on_hold',
							'cancelled',
							'rejected_fulfillment'
						],
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
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
	 * @param  WP_REST_Request  $request  Full data about the request.
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
	 * Sync order from ShipStation api.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function sync_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}
		$date_from    = $request->get_param( 'order_date_start' );
		$date_to      = $request->get_param( 'order_date_end' );
		$order_status = $request->get_param( 'order_status' );

		$args = [
			'orderStatus' => '',
			'force'       => true,
			'pageSize'    => 100,
			'page'        => 1,
		];
		if ( Validate::date( $date_from ) ) {
			$args['orderDateStart'] = $date_from;
		}
		if ( Validate::date( $date_to ) ) {
			$args['orderDateEnd'] = $date_to;
		}
		if ( ! empty( $order_status ) ) {
			$args['orderStatus'] = $order_status;
		}
		$items = ShipStationApi::init()->get_orders( $args );
		if ( ! isset( $items['orders'] ) ) {
			return $this->respondAccepted();
		}

		foreach ( $items['orders'] as $order ) {
			$_order = new Order( $order );
			BackgroundCommissionSync::add_to_queue( $_order );
		}
		if ( isset( $items['pages'] ) && intval( $items['pages'] ) > 1 ) {
			foreach ( range( 2, intval( $items['pages'] ) ) as $page ) {
				$args['page'] = $page;
				$_items       = ShipStationApi::init()->get_orders( $args );
				if ( isset( $_items['orders'] ) ) {
					foreach ( $_items['orders'] as $order ) {
						$_order = new Order( $order );
						BackgroundCommissionSync::add_to_queue( $_order );
					}
				}
			}
		}

		return $this->respondAccepted();
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$model = new DesignerCommission();
		$item  = $model->find_single( $id );
		if ( ! $item ) {
			return $this->respondNotFound();
		}

		$order   = wc_get_order( $item->get_wc_order_id() );
		$payload = [];
		if ( $order instanceof WC_Order ) {
			try {
				$payload = wc_get_order_item_meta( $item->get_wc_order_item_id(), '_dynamic_card_payload', true );
				if ( Validate::json( $payload ) ) {
					$payload = json_decode( $payload, true );
				}
			} catch ( \Exception $e ) {
			}
		}

		$commission = array_merge(
			$item->to_array(),
			[
				'order_edit_url' => $item->get_admin_order_url(),
				'pdf_url'        => $item->get_pdf_url()
			]
		);

		return $this->respondOK( [
			'commission'           => $commission,
			'dynamic_card_payload' => $payload,
			'wc_order_exists'      => $order instanceof WC_Order,
		] );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
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
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}
}
