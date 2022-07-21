<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Exception;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User_Query;
use YouSaidItCards\Modules\Designers\Admin\Settings;
use YouSaidItCards\Modules\Designers\Emails\Mailer;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;

defined( 'ABSPATH' ) || exit;

class DesignerController extends ApiController {

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
		register_rest_route( $this->namespace, '/designers', [
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

		register_rest_route( $this->namespace, '/designers/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item', ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item', ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item', ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( $this->namespace, '/designers/extend-card-limit', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'extend_card_limit' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$search   = $request->get_param( 'search' );

		$args = [ 'number' => $per_page, 'paged' => $page ];

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$args['meta_key']   = '_is_card_designer';
		$args['meta_value'] = 'yes';

		$user_search = new WP_User_Query( $args );

		$users = (array) $user_search->get_results();

		$commissions = DesignerCommission::get_commission_by_designers();
		$items       = [];
		foreach ( $users as $user ) {
			$designer = new CardDesigner( $user );
			$data     = $designer->to_array();
			if ( isset( $commissions[ $data['id'] ] ) ) {
				$data = array_merge( $data, $commissions[ $data['id'] ] );
			} else {
				$data = array_merge( $data, [ 'unpaid_commission' => 0, 'paid_commission' => 0 ] );
			}
			$data['currency_symbol'] = get_woocommerce_currency_symbol();

			$items[] = $data;
		}

		$pagination = static::get_pagination_data( $user_search->get_total(), $per_page, $page );

		return $this->respondOK( [
			'items'      => $items,
			'pagination' => $pagination
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
		$id           = (int) $request->get_param( 'id' );

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		if ( $current_user->ID != $id && ! current_user_can( 'manage_options' ) ) {
			$id = $current_user->ID;
		}

		$designer          = new CardDesigner( $id );
		$count             = ( new DesignerCard )->count_records( $id );
		$commission        = ( new DesignerCommission() )->get_total_commission_earned( $id );
		$unpaid_commission = ( new DesignerCommission() )->get_total_commission_earned_unpaid( $id );
		$paid_commission   = ( $commission - $unpaid_commission );
		$unique_customers  = ( new DesignerCommission() )->count_unique_customers( $id );
		$total_orders      = ( new DesignerCommission() )->count_total_orders( $id );

		return $this->respondOK( [
			'designer'             => $designer->to_array(),
			'statuses'             => $this->get_statuses( $count ),
			'total_commission'     => get_woocommerce_currency_symbol() . number_format( $commission, 2 ),
			'unpaid_commission'    => get_woocommerce_currency_symbol() . number_format( $unpaid_commission, 2 ),
			'paid_commission'      => get_woocommerce_currency_symbol() . number_format( $paid_commission, 2 ),
			'unique_customers'     => count( $unique_customers ),
			'total_orders'         => $total_orders,
			'maximum_allowed_card' => $designer->get_maximum_allowed_card(),
			'can_add_dynamic_card' => $designer->can_add_dynamic_card(),
			'total_cards'          => $designer->get_total_cards_count(),
		] );
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		return $this->respondCreated();
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$data = [];

		$first_name = $request->get_param( 'first_name' );
		if ( ! empty( $first_name ) ) {
			$data['first_name'] = $first_name;
		}

		$last_name = $request->get_param( 'last_name' );
		if ( ! empty( $last_name ) ) {
			$data['last_name'] = $last_name;
		}

		$display_name = $request->get_param( 'display_name' );
		if ( ! empty( $display_name ) ) {
			$data['display_name'] = $display_name;
		}

		$description = $request->get_param( 'description' );
		if ( ! empty( $description ) ) {
			$data['description'] = wp_strip_all_tags( $description );
		}

		$user_url = $request->get_param( 'user_url' );
		if ( ! empty( $user_url ) && filter_var( $user_url, FILTER_VALIDATE_URL ) ) {
			$data['user_url'] = $user_url;
		}

		// Update User login
		$user_login = $request->get_param( 'user_login' );
		if ( ! empty( $user_login ) ) {
			$username_error = [];

			if ( ! validate_username( $user_login ) ) {
				$username_error[] = 'Only alphanumeric, _, space, ., -, @ are allowed.';
			}

			$user_login = sanitize_user( $user_login, true );

			$user1 = get_user_by( 'login', $user_login );

			if ( $user1 instanceof \WP_User ) {
				if ( $user1->ID !== $current_user->ID ) {
					$username_error[] = 'Username already taken.';
				}
			}

			if ( is_email( $user_login ) ) {
				$user2 = get_user_by( 'email', $user_login );

				if ( $user2 instanceof \WP_User ) {
					if ( $user2->ID !== $current_user->ID ) {
						$username_error[] = 'Username already taken.';
					}
				}
			}

			if ( count( $username_error ) ) {
				return $this->respondUnprocessableEntity( 'password_error', $username_error[0], $username_error );
			}

			global $wpdb;
			$query = $wpdb->prepare( "UPDATE {$wpdb->users} SET user_login = %s WHERE user_login = %s", $user_login, $current_user->user_login );
			$wpdb->query( $query );
		}

		if ( ! empty( $data ) ) {
			( new CardDesigner() )->update( $current_user->ID, $data );
		}

		$meta_data = [];
		// update paypal Email
		$paypal_email = $request->get_param( 'paypal_email' );
		if ( is_email( $paypal_email ) ) {
			$meta_data['paypal_email'] = $paypal_email;
		}

		$location = $request->get_param( 'location' );
		if ( ! empty( $location ) ) {
			$meta_data['location'] = $location;
		}

		$business_name = $request->get_param( 'business_name' );
		if ( ! empty( $business_name ) ) {
			$meta_data['business_name'] = $business_name;
		}

		$business_address = $request->get_param( 'business_address' );
		if ( ! empty( $business_address ) ) {
			$meta_data['business_address'] = $business_address;
		}

		$vat_registration_number = $request->get_param( 'vat_registration_number' );
		if ( ! empty( $vat_registration_number ) ) {
			$meta_data['vat_registration_number'] = $vat_registration_number;
		}

		$vat_certificate_issue_date = $request->get_param( 'vat_certificate_issue_date' );
		if ( ! empty( $vat_certificate_issue_date ) ) {
			$meta_data['vat_certificate_issue_date'] = $vat_certificate_issue_date;
		}

		$avatar_id = $request->get_param( 'avatar_id' );
		if ( is_numeric( $avatar_id ) ) {
			$meta_data['avatar_id'] = intval( $avatar_id );
		}

		$card_logo_id = $request->get_param( 'card_logo_id' );
		if ( is_numeric( $card_logo_id ) ) {
			$meta_data['card_logo_id'] = intval( $card_logo_id );
		}

		$cover_photo_id = $request->get_param( 'cover_photo_id' );
		if ( is_numeric( $cover_photo_id ) ) {
			$meta_data['cover_photo_id'] = intval( $cover_photo_id );
		}
		if ( $request->has_param( 'can_add_dynamic_card' ) ) {
			$meta_data['can_add_dynamic_card'] = Validate::checked( $request->get_param( 'can_add_dynamic_card' ) ) ? 'yes' : 'no';
		}

		if ( ! empty( $meta_data ) ) {
			( new CardDesigner() )->update_meta_data( $current_user->ID, $meta_data );
		}

		// Update User password
		$current_password = $request->get_param( 'current_password' );
		$new_password     = $request->get_param( 'new_password' );
		$confirm_password = $request->get_param( 'confirm_password' );

		if ( ! empty( $current_password ) || ! empty( $new_password ) || ! empty( $confirm_password ) ) {
			$password_error = [];
			if ( ! wp_check_password( $current_password, $current_user->user_pass, $current_user->ID ) ) {
				$password_error[] = 'Current password does not match';
			}

			if ( strlen( $new_password ) < 6 ) {
				$password_error[] = 'Password length must me at least 8 characters.';
			}

			if ( $new_password !== $confirm_password ) {
				$password_error[] = 'Confirm password does not match.';
			}

			if ( $new_password == $current_password ) {
				$password_error[] = 'New password cannot be same.';
			}

			if ( count( $password_error ) ) {
				return $this->respondUnprocessableEntity( 'password_error', $password_error[0], $password_error );
			}

			wp_set_password( $new_password, $current_user->ID );
		}

		$designer = new CardDesigner( $current_user->ID );

		return $this->respondOK( [ 'designer' => $designer ] );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		return $this->respondOK();
	}

	/**
	 * Extend card limit request
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function extend_card_limit( WP_REST_Request $request ): WP_REST_Response {
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return $this->respondUnauthorized();
		}

		$new_limit = (int) $request->get_param( 'up_limit_to' );

		try {
			$table_data = [
				[ 'label' => 'User Email', 'value' => $current_user->user_email ],
				[ 'label' => 'New Limit Request', 'value' => $new_limit ],
			];
			$mailer     = new Mailer();
			$mailer->set_intro_lines( $mailer->all_fields_table( $table_data ) );
			$mailer->set_greeting( 'Hello!' );
			$mailer->setReceiver( Settings::email_for_card_limit_extension() );
			$mailer->setSubject( __( 'Request to extend card limit.', 'ap-toolkit' ) );
			$mailer->setFrom( $current_user->user_email, $current_user->display_name );
			$mailer->setReplyTo( $current_user->user_email, $current_user->display_name );
			$mailer->send();

			return $this->respondOK();
		} catch ( Exception $e ) {
			return $this->respondInternalServerError();
		}
	}

	/**
	 * @param string $first_name
	 * @param string $last_name
	 *
	 * @return string
	 */
	private function get_display_name( $first_name = '', $last_name = '' ) {
		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			return sprintf( "%s %s", $first_name, $last_name );
		}
		if ( ! empty( $last_name ) ) {
			return $last_name;
		}
		if ( ! empty( $first_name ) ) {
			return $first_name;
		}

		return '';
	}

	/**
	 * Get statuses
	 *
	 * @param array $count
	 *
	 * @return array
	 */
	private function get_statuses( array $count ) {
		return [
			[ 'key' => 'all', 'label' => 'Total Designs Uploaded', 'count' => $count['all'], 'color' => '#f4d4e4' ],
			[ 'key' => 'accepted', 'label' => 'Approved', 'count' => $count['accepted'], 'color' => '#fae8ba' ],
			[ 'key' => 'rejected', 'label' => 'Rejected', 'count' => $count['rejected'], 'color' => '#cceace' ],
			[ 'key' => 'processing', 'label' => 'Processing', 'count' => $count['processing'], 'color' => '#dde4ff' ],
		];
	}
}
