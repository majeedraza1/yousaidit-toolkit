<?php

namespace YouSaidItCards\Modules\Reminders\REST;

use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;
use WC_Address_Book;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;
use YouSaidItCards\REST\ApiController;

class CustomerReminderController extends ApiController {
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
		register_rest_route( $this->namespace, 'reminders', [
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
		register_rest_route( $this->namespace, 'reminders/(?P<id>\d+)', [
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
		$reminders = ( new Reminder() )->find_by_user( get_current_user_id() );
		$groups    = ( new ReminderGroup() )->find_multiple();
		$data      = [
			'groups'    => $groups,
			'reminders' => $reminders,
		];

		return $this->respondOK( $data );
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

		$id = ( new Reminder() )->create( $data );

		if ( $id ) {
			$this->to_woocommerce_address_book( $id, $data );

			return $this->respondCreated( [ 'id' => $id ] );
		}

		return $this->respondInternalServerError();
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
		$class = new Reminder();
		$item  = $class->find_single( $id );

		if ( ! $item ) {
			return $this->respondNotFound();
		}

		$data       = $this->prepare_item_for_database( $request );
		$data['id'] = $id;

		$class->update( $data );

		$this->to_woocommerce_address_book( $id, $data, 'update' );

		return $this->respondOK();
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$class = new Reminder();
		$item  = $class->find_single( $id );

		if ( ! $item ) {
			return $this->respondNotFound();
		}

		$user_id = get_current_user_id();
		if ( $item->get( 'user_id' ) != $user_id ) {
			return $this->respondForbidden();
		}

		if ( $class->delete( $id ) ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ) {
		$occasion_date    = Sanitize::date( $request->get_param( 'occasion_date' ) );
		$remind_day_count = Sanitize::number( $request->get_param( 'remind_days_count' ) );

		$remind_date = null;
		if ( Validate::date( $occasion_date ) ) {
			$dateTime = new \DateTime( $occasion_date );
			$dateTime->modify( "-{$remind_day_count} days" );
			$remind_date = $dateTime->format( 'Y-m-d' );
		}
		$data = [
			'reminder_group_id'  => Sanitize::number( $request->get_param( 'reminder_group_id' ) ),
			'remind_days_count'  => $remind_day_count,
			'occasion_date'      => Validate::date( $occasion_date ) ? $occasion_date : null,
			'remind_date'        => $remind_date,
			'is_recurring'       => Validate::checked( $request->get_param( 'is_recurring' ) ) ? 1 : 0,
			'has_custom_address' => Validate::checked( $request->get_param( 'has_custom_address' ) ) ? 1 : 0,
			'name'               => Sanitize::text( $request->get_param( 'name' ) ),
			'first_name'         => Sanitize::text( $request->get_param( 'first_name' ) ),
			'last_name'          => Sanitize::text( $request->get_param( 'last_name' ) ),
			'address_line1'      => Sanitize::text( $request->get_param( 'address_line1' ) ),
			'address_line2'      => Sanitize::text( $request->get_param( 'address_line2' ) ),
			'postal_code'        => Sanitize::text( $request->get_param( 'postal_code' ) ),
			'city'               => Sanitize::text( $request->get_param( 'city' ) ),
			'state'              => Sanitize::text( $request->get_param( 'state' ) ),
			'country_code'       => Sanitize::text( $request->get_param( 'country_code' ) ),
		];

		$data['user_id'] = get_current_user_id();

		return $data;
	}

	/**
	 * Check if it has address data
	 *
	 * @param array $data The data to be saved.
	 *
	 * @return bool
	 */
	public function has_address_data( array $data ): bool {
		return ! empty( $data['address_line1'] ) && ! empty( $data['postal_code'] ) &&
		       ! empty( $data['city'] ) && ! empty( $data['country_code'] );
	}

	/**
	 * @param int $reminder_id The reminder id.
	 * @param array $data The data to be saved.
	 * @param string $mode The mode. Value can be 'create' or 'update'.
	 *
	 * @return string
	 */
	public function to_woocommerce_address_book( int $reminder_id, array $data, string $mode = 'create' ): string {
		if ( ! $this->has_address_data( $data ) || ! class_exists( WC_Address_Book::class ) ) {
			return '';
		}
		$address = [
			'first_name' => $data['first_name'] ?? '',
			'last_name'  => $data['last_name'] ?? '',
			'company'    => $data['company'] ?? '',
			'address_1'  => $data['address_line1'],
			'address_2'  => $data['address_line2'],
			'city'       => $data['city'],
			'state'      => $data['state'] ?? '',
			'postcode'   => $data['postal_code'],
			'country'    => $data['country_code'],
		];


		$user_id = get_current_user_id();

		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_value = %s AND meta_key LIKE %s";
		$row = $wpdb->get_row(
			$wpdb->prepare( $sql, $user_id, $reminder_id, '%_reminder_id' ),
			ARRAY_A
		);

		$address_book  = WC_Address_Book::get_instance();
		$address_names = $address_book->get_address_names( $user_id, 'shipping' );

		if ( 'update' == $mode && $row ) {
			$name = str_replace( '_reminder_id', '', $row['meta_key'] );
		} else {
			$name = $address_book->set_new_address_name( $address_names, 'shipping' );
		}

		foreach ( $address as $key => $value ) {
			update_user_meta( $user_id, "{$name}_{$key}", $value );
		}

		$address_names[] = $name;
		update_user_meta( $user_id, "{$name}_address_nickname", $data['name'] );
		update_user_meta( $user_id, "wc_address_book_shipping", $address_names );
		update_user_meta( $user_id, "{$name}_reminder_id", $reminder_id );

		return $name;
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}
}
