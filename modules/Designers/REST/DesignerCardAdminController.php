<?php

namespace YouSaidItCards\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\Modules\Designers\Admin\Settings;
use YouSaidItCards\Modules\Designers\Emails\CardAcceptedEmail;
use YouSaidItCards\Modules\Designers\Emails\CardRejectedEmail;
use YouSaidItCards\Modules\Designers\Emails\CardTrashedEmail;
use YouSaidItCards\Modules\Designers\Emails\CommissionChangeEmail;
use YouSaidItCards\Modules\Designers\Models\CardComment;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\CardToProduct;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

defined( 'ABSPATH' ) || exit;

class DesignerCardAdminController extends ApiController {

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
		register_rest_route( $this->namespace, '/designers-cards', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers-cards/(?P<id>\d+)', [
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
		register_rest_route( $this->namespace, '/designers-cards/(?P<id>\d+)/product', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_product' ],
				'permission_callback' => '__return_true',
			],
		] );
		register_rest_route( $this->namespace, '/designers-cards/(?P<id>\d+)/commission', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_commission' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'commission'             => [ 'type' => [ 'array', 'object' ] ],
					'marketplace_commission' => [ 'type' => [ 'array', 'object' ] ],
				]
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

		$page        = $request->get_param( 'page' );
		$per_page    = $request->get_param( 'per_page' );
		$search      = $request->get_param( 'search' );
		$status      = $request->get_param( 'status' );
		$designer_id = (int) $request->get_param( 'designer_id' );
		$status      = in_array( $status, DesignerCard::get_valid_statuses() ) ? $status : 'all';

		$items = ( new DesignerCard() )->find( [
			'search'           => $search,
			'per_page'         => $per_page,
			'paged'            => $page,
			'status'           => $status,
			'designer_user_id' => $designer_id > 0 ? $designer_id : '',
		] );

		$counts     = ( new DesignerCard() )->count_records();
		$pagination = static::get_pagination_data( $counts[ $status ], $per_page, $page );

		$response = [ 'items' => $items, 'pagination' => $pagination ];

		$response['statuses'] = static::get_statuses_with_counts( $counts, $status );

		return $this->respondOK( $response );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new DesignerCard() )->find_by_id( $id );

		if ( ! $item instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		$data = $item->to_array();

		$data['default_commissions'] = [
			'yousaidit'       => Settings::designer_default_commission_for_yousaidit(),
			'yousaidit-trade' => Settings::designer_default_commission_for_yousaidit_trade(),
		];

		$sizes = $item->get( 'card_sizes' );
		foreach ( $sizes as $size ) {
			$sku = Settings::designer_card_sku_prefix();
			$sku = str_replace( '{{card_type}}', $item->get( 'card_type' ) === 'dynamic' ? 'D' : 'S', $sku );
			$sku = str_replace( '{{card_size}}', $size === 'square' ? 'S' : strtoupper( $size ), $sku );
			$sku = str_replace( '{{card_id}}', $item->get_id(), $sku );

			$data['default_price'][ $size ] = Settings::designer_card_price();
			$data['default_sku'][ $size ]   = $sku;
		}


		return $this->respondOK( $data );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new DesignerCard() )->find_by_id( $id );

		if ( ! $item instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		$status       = $request->get_param( 'status' );
		$current_user = wp_get_current_user();

		$data = [ 'id' => $id ];

		$card_sku = $request->get_param( 'card_sku' );
		if ( ! empty( $card_sku ) ) {
			$data['card_sku'] = $card_sku;
		}

		if ( 'change_commission' != $status ) {
			$data['status'] = $status;
		}

		if ( 'accepted' == $status || 'change_commission' == $status ) {

			$commissions = [];
			foreach ( $request->get_param( 'commission' ) as $size_key => $commission ) {
				$commissions[ $size_key ] = floatval( $commission );
			}

			$data['commission_per_sale'] = [
				'commission_type' => $request->get_param( 'commission_type' ),
				'commission'      => $commissions,
			];

			$note_to_designer = $request->get_param( 'note_to_designer' );

			$comment_id = CardComment::insert_comment( $note_to_designer, $item->get( 'id' ), $current_user );
			if ( $comment_id ) {
				update_comment_meta( $comment_id, '_comment_author_role', 'Admin' );
			}

			if ( 'accepted' == $status ) {
				( new CardAcceptedEmail( new CardDesigner( $current_user ), $item ) )
					->send_email();
			}
		}

		if ( 'rejected' == $status ) {
			$reject_reason = $request->get_param( 'reject_reason' );

			$comment_id = CardComment::insert_comment( $reject_reason, $item->get( 'id' ), $current_user );
			if ( $comment_id ) {
				update_comment_meta( $comment_id, '_comment_author_role', 'Admin' );
			}

			( new CardRejectedEmail( new CardDesigner( $current_user ), $item ) )
				->set_reject_reason( $reject_reason )
				->send_email();
		}

		$updated = ( new DesignerCard() )->update( $data );
		$item    = ( new DesignerCard() )->find_by_id( $id );

		if ( 'change_commission' == $status ) {
			( new CommissionChangeEmail( new CardDesigner( $current_user ), $item ) )
				->send_email();
		}

		if ( $updated ) {
			return $this->respondOK( $item );
		}

		return $this->respondInternalServerError();
	}

	public function update_commission( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id   = $request->get_param( 'id' );
		$item = ( new DesignerCard() )->find_by_id( $id );

		if ( ! $item instanceof DesignerCard ) {
			return $this->respondNotFound();
		}
		$commission_data = $item->get_commission_data();
		$commission_type = $commission_data['commission_type'] ?? '';

		$commissions            = $request->get_param( 'commission' );
		$marketplace_commission = $request->get_param( 'marketplace_commission' );

		$data = [ 'id' => $id ];

		$data['commission_per_sale'] = [ 'commission_type' => $commission_type, 'commission' => $commissions ];

		if ( is_array( $marketplace_commission ) && count( $marketplace_commission ) ) {
			$data['marketplace_commission'] = $marketplace_commission;
		}

		( new DesignerCard )->update( $data );

		return $this->respondOK();
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_product( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id         = (int) $request->get_param( 'id' );
		$args       = [
			'product_sku'   => $request->get_param( 'product_sku' ),
			'product_price' => $request->get_param( 'product_price' ),
		];
		$product_id = CardToProduct::create( $id, $args );

		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		return $this->get_item( $request );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id     = (int) $request->get_param( 'id' );
		$action = $request->get_param( 'action' );

		$card = ( new DesignerCard() )->find_by_id( $id );
		if ( ! $card instanceof DesignerCard ) {
			return $this->respondNotFound();
		}

		if ( 'delete' == $action ) {
			( new DesignerCard() )->delete( $id );
		} elseif ( 'restore' == $action ) {
			( new DesignerCard() )->restore( $id );
		} else {
			( new DesignerCard() )->trash( $id );
			( new CardTrashedEmail( $card ) )->send_email();
		}

		return $this->respondOK();
	}

	/**
	 * Get statuses data with count
	 *
	 * @param array $counts
	 * @param string $status
	 *
	 * @return array
	 */
	private static function get_statuses_with_counts( array $counts, $status ) {
		$_statuses = DesignerCard::get_available_statuses();
		$statuses  = [
			[
				'key'    => 'all',
				'label'  => 'All',
				'count'  => isset( $counts['all'] ) ? $counts['all'] : 0,
				'active' => 'all' == $status,
			]
		];
		foreach ( $_statuses as $key => $label ) {
			$statuses[] = [
				'key'    => $key,
				'label'  => $label,
				'count'  => isset( $counts[ $key ] ) ? $counts[ $key ] : 0,
				'active' => $key == $status,
			];
		}

		return $statuses;
	}
}
