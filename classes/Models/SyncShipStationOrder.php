<?php

namespace Yousaidit\Models;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Yousaidit\Integration\ShipStation\ShipStationApi;

class SyncShipStationOrder extends BackgroundProcess {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	public static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'sync_ship_station_order';

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'dispatch_data' ] );
		}

		return self::$instance;
	}

	/**
	 * Save and run background on shutdown of all code
	 */
	public function dispatch_data() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$page        = isset( $item['page'] ) ? intval( $item['page'] ) : 0;
		$orderStatus = isset( $item['orderStatus'] ) ? $item['orderStatus'] : null;

		if ( $page && in_array( $orderStatus, [ 'shipped' ] ) ) {
			static::create_or_update_orders( $page, $orderStatus );
		}

		return false;
	}

	/**
	 * Init sync for shipped orders
	 */
	public static function init_sync_for_shipped_orders() {
		$option = get_option( 'sync_for_shipped_orders', 'no' );
		if ( $option !== 'yes' ) {
			$result = static::get_ship_station_orders( 1, 'shipped' );
			$pages  = isset( $result['pages'] ) ? intval( $result['pages'] ) : 0;
			if ( $pages ) {
				foreach ( range( 1, $pages ) as $page ) {
					static::init()->push_to_queue( [ 'page' => $page, 'orderStatus' => 'shipped' ] );
				}
			}
			update_option( 'sync_for_shipped_orders', 'yes', false );

			return $pages;
		}

		return 0;
	}

	/**
	 * @param int $page
	 * @param string $status
	 *
	 * @return bool
	 */
	public static function create_or_update_orders( $page = 1, $status = 'shipped' ) {
		$orders = static::get_ship_station_orders( $page, $status );
		if ( isset( $orders['orders'] ) && is_array( $orders['orders'] ) ) {
			ShipStationOrder::create_or_update_orders( $orders['orders'] );

			return true;
		}

		return false;
	}

	/**
	 * @param int $page
	 * @param string $status
	 *
	 * @return array
	 */
	public static function get_ship_station_orders( $page = 1, $status = 'shipped' ) {
		return ShipStationApi::init()->get_orders( [
			'pageSize'    => 100,
			'page'        => $page,
			'sortBy'      => 'OrderDate',
			'sortDir'     => 'ASC',
			'orderStatus' => $status,
			'force'       => true,
		] );
	}
}
