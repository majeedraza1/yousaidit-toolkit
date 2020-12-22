<?php

namespace YouSaidItCards\ShipStation;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class ShipStationOrder extends DatabaseModel {

	/**
	 * @inhericDoc
	 */
	protected $table = 'ship_station_orders';

	/**
	 * Get orders
	 *
	 * @param array $orders_ids
	 *
	 * @return array
	 */
	public static function get_orders( array $orders_ids ) {
		global $wpdb;
		$self       = new static;
		$table      = $self->get_table_name();
		$orders_ids = array_map( 'intval', $orders_ids );

		$sql   = "SELECT * FROM {$table} WHERE `orderId` IN(" . implode( ',', $orders_ids ) . ")";
		$items = $wpdb->get_results( $sql, ARRAY_A );

		$addresses    = ShipStationOrderAddress::get_addresses( $orders_ids );
		$orders_items = ShipStationOrderItem::get_items( $orders_ids );

		$orders = [];
		foreach ( $items as $item ) {
			$order = new static( $item );

			foreach ( $addresses as $address ) {
				if ( $address->get( 'orderId' ) == $order->get( 'orderId' ) ) {
					$address->remove( 'id' );
					$address->remove( 'orderId' );
					if ( $address->get( 'address_type' ) == 'shipping' ) {
						$address->remove( 'address_type' );
						$order->set( 'shipTo', $address->to_array() );
					} else {
						$address->remove( 'address_type' );
						$order->set( 'billTo', $address->to_array() );
					}
				}
			}

			$items = [];
			foreach ( $orders_items as $order_item ) {
				if ( $order_item->get( 'orderId' ) == $order->get( 'orderId' ) ) {
					$order_item->remove( 'id' );
					$order_item->remove( 'orderId' );
					$items[] = $order_item->to_array();
				}
			}

			$order->set( 'items', $items );

			$orders[] = $order->to_array();
		}

		return $orders;
	}

	/**
	 * Get single order
	 *
	 * @param int $order_id
	 *
	 * @return \ArrayObject|static
	 */
	public static function get_order( $order_id ) {
		$orders = static::get_orders( [ $order_id ] );

		return is_array( $orders ) && count( $orders ) ? $orders[0] : new \ArrayObject();
	}

	/**
	 * Create order
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public static function create_or_update_order( array $data ) {
		$ids = static::create_or_update_orders( [ $data ] );

		return is_array( $ids ) && count( $ids ) ? $ids[0] : 0;
	}

	/**
	 * Create multiple orders
	 *
	 * @param array $items
	 *
	 * @return array|int[]
	 */
	public static function create_or_update_orders( array $items ) {
		$orderIds            = [];
		$orders_to_create    = [];
		$items_to_create     = [];
		$addresses_to_create = [];

		foreach ( $items as $item ) {
			$orderId = isset( $item['orderId'] ) ? intval( $item['orderId'] ) : 0;
			if ( ! $orderId ) {
				continue;
			}

			$billTo      = isset( $item['billTo'] ) ? $item['billTo'] : [];
			$shipTo      = isset( $item['shipTo'] ) ? $item['shipTo'] : [];
			$order_items = isset( $item['items'] ) ? $item['items'] : [];

			foreach ( $order_items as $_item ) {
				$items_to_create[ $orderId ][] = array_merge( [ 'orderId' => $orderId ], $_item );
			}
			$addresses_to_create[ $orderId ] = [
				array_merge( [ 'orderId' => $orderId, 'address_type' => 'billing' ], $billTo ),
				array_merge( [ 'orderId' => $orderId, 'address_type' => 'shipping' ], $shipTo ),
			];

			unset( $item['billTo'] );
			unset( $item['shipTo'] );
			unset( $item['items'] );

			$orderIds[]                   = $orderId;
			$orders_to_create[ $orderId ] = $item;
		}

		$orders_to_update = [];
		$orders           = static::get_orders( $orderIds );
		if ( $orders ) {
			foreach ( $orders as $order ) {
				$orders_to_update[] = array_merge( [ 'id' => $order['id'] ], $orders_to_create[ $order['orderId'] ] );

				unset( $orders_to_create[ $order['orderId'] ] );
				unset( $items_to_create[ $order['orderId'] ] );
				unset( $addresses_to_create[ $order['orderId'] ] );
			}
		}

		$to_create_items = [];
		foreach ( $items_to_create as $items_list ) {
			foreach ( $items_list as $item ) {
				$to_create_items[] = $item;
			}
		}

		$to_create_addresses = [];
		foreach ( $addresses_to_create as $items_list ) {
			foreach ( $items_list as $item ) {
				$to_create_addresses[] = $item;
			}
		}

		if ( ! $orders_to_create ) {
			return [];
		}

		$ids = ( new static )->create_multiple( $orders_to_create );
		( new ShipStationOrderAddress )->create_multiple( $to_create_addresses );
		( new ShipStationOrderItem )->create_multiple( $to_create_items );

		if ( $orders_to_update ) {
			$ids = array_merge( $ids, wp_list_pluck( $orders_to_update, 'id' ) );
			( new static )->update_multiple( $orders_to_update );
		}

		return $ids;
	}

	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$self       = new static;
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			orderId bigint(20) UNSIGNED NOT NULL,
			orderNumber varchar(100) NULL DEFAULT NULL,
			orderKey varchar(100) NULL DEFAULT NULL,
			orderDate DATETIME NULL DEFAULT NULL,
			createDate DATETIME NULL DEFAULT NULL,
			modifyDate DATETIME NULL DEFAULT NULL,
			paymentDate DATETIME NULL DEFAULT NULL,
			shipByDate DATETIME NULL DEFAULT NULL,
			orderStatus varchar(50) NULL DEFAULT NULL,
			customerId bigint(20) UNSIGNED NOT NULL,
			customerUsername varchar(100) NULL DEFAULT NULL,
			customerEmail varchar(100) NULL DEFAULT NULL,
			orderTotal FLOAT(50) NOT NULL DEFAULT 0,
			amountPaid FLOAT(50) NOT NULL DEFAULT 0,
			taxAmount FLOAT(50) NOT NULL DEFAULT 0,
			shippingAmount FLOAT(50) NOT NULL DEFAULT 0,
			customerNotes TEXT NULL DEFAULT NULL,
			internalNotes TEXT NULL DEFAULT NULL,
			gift TINYINT(1) NOT NULL DEFAULT 0,
			giftMessage TEXT NULL DEFAULT NULL,
			paymentMethod varchar(100) NULL DEFAULT NULL,
			requestedShippingService varchar(255) NULL DEFAULT NULL,
			carrierCode varchar(255) NULL DEFAULT NULL,
			serviceCode varchar(255) NULL DEFAULT NULL,
			packageCode varchar(255) NULL DEFAULT NULL,
			confirmation varchar(255) NULL DEFAULT NULL,
			shipDate DATETIME NULL DEFAULT NULL,
			holdUntilDate DATETIME NULL DEFAULT NULL,
			weight TEXT NULL DEFAULT NULL,
			dimensions TEXT NULL DEFAULT NULL,
			insuranceOptions TEXT NULL DEFAULT NULL,
			internationalOptions TEXT NULL DEFAULT NULL,
			advancedOptions TEXT NULL DEFAULT NULL,
			tagIds TEXT NULL DEFAULT NULL,
			userId TEXT NULL DEFAULT NULL,
			externallyFulfilled TINYINT(1) NOT NULL DEFAULT 0,
			externallyFulfilledBy TEXT NULL DEFAULT NULL,
			labelMessages TEXT NULL DEFAULT NULL,
			PRIMARY KEY (id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );

		$version = get_option( $table_name . '-version', '0.1.0' );;
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE `orderId` (`orderId`);" );

			update_option( $table_name . '-version', '1.0.0' );
		}
	}
}
