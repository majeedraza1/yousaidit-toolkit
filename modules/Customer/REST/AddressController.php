<?php

namespace YouSaidItCards\Modules\Customer\REST;

use Stackonet\WP\Framework\Traits\ApiCrudOperations;
use WP_Error;
use WP_REST_Server;
use YouSaidItCards\Modules\Customer\Models\Address;
use YouSaidItCards\Modules\Customer\Models\BaseAddress;
use YouSaidItCards\REST\ApiController;

class AddressController extends ApiController {
	use ApiCrudOperations;

	protected $rest_base = 'me/addresses';

	/**
	 * @return Address
	 */
	public function get_store(): Address {
		return new Address();
	}

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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = isset( $this->namespace ) ? $this->namespace : '';
		$rest_base = isset( $this->rest_base ) ? trim( $this->rest_base, '/' ) : '';

		register_rest_route( $namespace, $rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'args'                => $this->create_item_params(),
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );

		register_rest_route( $namespace, $rest_base . '/(?P<id>\d+)', [
			'args' => [
				'id' => [
					'description'       => __( 'Item unique id.' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'minimum'           => 1,
				]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => $this->update_item_params(),
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$permission = $this->get_items_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$request->set_param( 'user_id', get_current_user_id() );

		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$items      = $this->get_store()->find_multiple( $request->get_params() );
		$count      = $this->get_store()->count_records( $request->get_params() );
		$count      = is_numeric( $count ) ? $count : 0;
		$pagination = static::get_pagination_data( $count, $per_page, $page );

		$response = new \WP_REST_Response( [
			'items'      => $items,
			'pagination' => $pagination,
		] );

		return $this->respondOK( $this->prepare_response_for_collection( $response ) );
	}

	/**
	 * @return array[]
	 */
	public function create_item_params(): array {
		return BaseAddress::rest_create_item_params();
	}

	/**
	 * @return array
	 */
	public function update_item_params(): array {
		$params = [];
		foreach ( $this->create_item_params() as $key => $config ) {
			$config['required'] = false;
			$params[ $key ]     = $config;
		}

		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		$item = ( new Address )->find_single( (int) $request->get_param( 'id' ) );
		if ( ! $item instanceof Address ) {
			return $this->respondNotFound();
		}

		if ( $item->get( 'user_id' ) != get_current_user_id() ) {
			return $this->respondUnauthorized();
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function prepare_item_for_database( $request ) {
		$data            = $request->get_params();
		$data['user_id'] = get_current_user_id();

		return $data;
	}
}
