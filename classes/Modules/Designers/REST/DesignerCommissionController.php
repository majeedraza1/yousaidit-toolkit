<?php

namespace Yousaidit\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yousaidit\Modules\Designers\Models\DesignerCommission;

defined( 'ABSPATH' ) || exit;

class DesignerCommissionController extends ApiController {

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
		register_rest_route( $this->namespace, '/designers/(?P<id>\d+)/commissions', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
				'args'     => $this->get_collection_params(),
			],
		] );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$id = (int) $request->get_param( 'id' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$id = $current_user->ID;
		}

		$report_type = $request->get_param( 'report_type' );
		$report_type = in_array( $report_type, DesignerCommission::get_report_types() ) ? $report_type : 'today';
		$date_from   = $request->get_param( 'date_from' );
		$date_to     = $request->get_param( 'date_to' );
		$page        = (int) $request->get_param( 'page' );
		$per_page    = (int) $request->get_param( 'per_page' );

		list( $from, $to ) = DesignerCommission::get_start_and_end_date( $report_type, $date_from, $date_to );

		$items = ( new DesignerCommission() )->find( [
			'designer_id' => $id,
			'from'        => $from,
			'to'          => $to,
			'per_page'    => $per_page,
			'paged'       => $page,
		] );

		return $this->respondOK( [ 'commissions' => $items ] );
	}
}
