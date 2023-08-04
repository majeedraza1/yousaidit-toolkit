<?php

namespace YouSaidItCards\ShipStation;

use Stackonet\WP\Framework\Supports\RestClient;
use Stackonet\WP\Framework\Traits\Cacheable;
use YouSaidItCards\Admin\SettingPage;

class ShipStationApi extends RestClient {

	use Cacheable;

	const STATUSES = [
		"awaiting_payment",
		"awaiting_shipment",
		"pending_fulfillment",
		"shipped",
		"on_hold",
		"cancelled",
		"rejected_fulfillment",
	];

	/**
	 * @var string
	 */
	protected $api_base_url = 'https://ssapi.shipstation.com/';

	/**
	 * The instance of the class
	 *
	 * @var static
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return static
	 */
	public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static(
				SettingPage::get_option( 'ship_station_api_key' ),
				SettingPage::get_option( 'ship_station_api_secret' )
			);
		}

		return static::$instance;
	}

	/**
	 * ShipStationApi constructor.
	 *
	 * @param null $api_key
	 * @param null $api_secret
	 */
	public function __construct( $api_key = null, $api_secret = null ) {
		$this->add_auth_header( base64_encode( $api_key . ':' . $api_secret ) );

		parent::__construct();
	}

	/**
	 * @param int $wc_order_id
	 * @param bool $force
	 *
	 * @return array|\WP_Error|Order
	 */
	public function get_order_by_wc_order( int $wc_order_id, bool $force = false ) {
		$store_id = SettingPage::get_shipstation_yousaidit_store_id();
		if ( empty( $store_id ) ) {
			return new \WP_Error( 'store_not_set', 'Store id is not set properly.' );
		}
		$transient_name = 'ship_station_order_for_' . $wc_order_id;
		$order          = get_transient( $transient_name );
		if ( false === $order || $force ) {
			$orders = $this->get( 'orders', [
				'pageSize'    => 1,
				'orderNumber' => $wc_order_id,
				'storeId'     => $store_id,
			] );
			if ( is_wp_error( $orders ) ) {
				return $orders;
			}
			$orders = $orders['orders'] ?? [];
			if ( empty( $orders ) ) {
				return new \WP_Error( 'not_found', sprintf( 'No ShipStation order found for id #%s', $wc_order_id ) );
			}
			$order = $orders[0];
			set_transient( $transient_name, $order, HOUR_IN_SECONDS );
		}

		return new Order( $order );
	}

	/**
	 * Get orders from ShipStation API
	 *
	 * @param array $args
	 *
	 * @return array|mixed|object
	 */
	public function get_orders( array $args = [] ) {
		$args = wp_parse_args( $args, array(
			'pageSize'    => 100,
			'page'        => 1,
			'sortBy'      => 'OrderDate',
			'sortDir'     => 'DESC',
			'orderStatus' => 'awaiting_shipment',
		) );

		$transient_name = 'ship_station_orders_' . md5( json_encode( $args ) );

		$force = isset( $args['force'] ) && $args['force'] == true;
		if ( $force ) {
			delete_transient( $transient_name );
		}

		$orders = get_transient( $transient_name );
		if ( false === $orders ) {
			$orders = $this->_get_orders( $args );
			set_transient( $transient_name, $orders, MINUTE_IN_SECONDS * 60 );
		}

		return $orders;
	}

	/**
	 * Get orders from ShipStation API
	 *
	 * @param array $args
	 *
	 * @return array|mixed|object
	 */
	public function _get_orders( array $args = [] ) {
		$default    = [
			'pageSize'    => 100,
			'page'        => 1,
			'sortBy'      => 'OrderDate',
			'sortDir'     => 'DESC',
			'orderStatus' => 'awaiting_shipment',
		];
		$args       = wp_parse_args( $args, $default );
		$valid_args = array_merge( array_keys( $default ), [ 'storeId', 'orderNumber' ] );
		$parameters = [];
		foreach ( $args as $key => $value ) {
			if ( in_array( $key, $valid_args, true ) ) {
				$parameters[ $key ] = $value;
			}
		}

		$orders = $this->get( 'orders', $parameters );
		if ( is_wp_error( $orders ) ) {
			return [];
		}

		return $orders;
	}

	/**
	 * Get order from ShipStation API by order id
	 *
	 * @param int $order_id ShipStation order id.
	 *
	 * @return array|object
	 */
	public function get_order( $order_id ) {
		$cache_key = $this->get_cache_key_for_single_item( $order_id );
		$order     = $this->get_cache( $cache_key );
		if ( false == $order ) {
			$order = $this->get( 'orders/' . $order_id );
			$this->set_cache( $cache_key, $order );
		}

		return $order;
	}

	/**
	 * Update an existing order
	 *
	 * @param array $data
	 *
	 * @return array|\WP_Error
	 */
	public function mark_as_shipped( array $data ) {
		$data = wp_parse_args( $data, [
			"carrierCode"        => "royal_mail",
			"notifyCustomer"     => false,
			"notifySalesChannel" => true,
			"orderId"            => 0,
			"shipDate"           => current_time( 'Y-m-d' ),
			"trackingNumber"     => "",
		] );

		$final_data = [];
		foreach ( $data as $key => $value ) {
			if ( is_bool( $value ) ) {
				$final_data[ $key ] = $value ? 'true' : 'false';
			} else {
				$final_data[ $key ] = (string) $value;
			}
		}

		return $this->post( 'orders/markasshipped', $final_data );
	}

	/**
	 * List all shipping providers connected to this account.
	 */
	public function get_carriers() {
		return $this->get( 'carriers' );
	}

	/**
	 * Get stores
	 * @return array|\WP_Error
	 */
	public function get_stores() {
		$stores = get_transient( 'get_shipstation_stores' );
		if ( ! is_array( $stores ) ) {
			$stores = $this->get( 'stores' );
			if ( ! is_wp_error( $stores ) ) {
				set_transient( 'get_shipstation_stores', $stores, WEEK_IN_SECONDS );
			}
		}

		return $stores;
	}

	public static function get_store_name( int $store_id ): string {
		$store_name = '';
		$stores     = self::init()->get_stores();
		if ( is_array( $stores ) ) {
			foreach ( $stores as $store ) {
				if ( $store['storeId'] == $store_id ) {
					$store_name = $store['storeName'];
				}
			}
		}

		return $store_name;
	}
}
