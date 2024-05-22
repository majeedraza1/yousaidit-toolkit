<?php

namespace YouSaidItCards\Modules\Reminders\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\REST\ApiController;

class AdminReminderController extends ApiController {
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
		register_rest_route( $this->namespace, 'admin/reminders', [
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
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$reminders  = ( new Reminder() )->find_multiple( [
			'page'     => $page,
			'per_page' => $per_page,
		] );
		$user_ids   = wp_list_pluck( $reminders, 'user_id' );
		$users_list = get_users( [ 'include' => $user_ids ] );
		$users      = [];
		foreach ( $users_list as $user ) {
			$users[ $user->ID ] = [
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'edit_url'     => add_query_arg( [ 'user_id' => $user->ID ], admin_url( 'user-edit.php' ) )
			];
		}
		$total_reminders = ( new Reminder() )->count_records();
		$pagination      = self::get_pagination_data( $total_reminders, $per_page, $page );

		$items = [];
		foreach ( $reminders as $reminder ) {
			$reminder['user']               = $users[ $reminder['user_id'] ];
			$reminder['email_template_url'] = add_query_arg( [
				'action'      => 'reminder_email_template',
				'reminder_id' => $reminder->get( 'id' )
			], admin_url( 'admin-ajax.php' ) );
			$items[]                        = $reminder;
		}

		return $this->respondOK( [
			'reminders'  => $items,
			'users'      => $users,
			'pagination' => $pagination
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
