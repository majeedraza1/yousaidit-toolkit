<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use YouSaidItCards\ShipStation\ShipStationApi;

class ShipStationOrder extends DatabaseModel {
	protected $table = 'tree_planting_orders';

	/**
	 * Find items for tree planting
	 *
	 * @return array|static[]
	 */
	public static function find_for_tree_planting(): array {
		$items = static::get_query_builder()->where( 'tree_planting_id', 0 )->get();
		$data  = [];
		foreach ( $items as $item ) {
			$data[] = new static( $item );
		}

		return $data;
	}

	/**
	 * Create if not exists
	 *
	 * @param  array  $items  ShipStation order collection response data.
	 *
	 * @return array
	 */
	public static function create_if_not_exists( array $items ): array {
		$sanitized_items = [];
		foreach ( $items as $item ) {
			$sanitized_items[] = static::sanitize_ship_station_order_for_database( $item );
		}
		$shipstation_order_ids = wp_list_pluck( $sanitized_items, 'shipstation_order_id' );
		$existing              = static::find_by_ship_station_order_ids( $shipstation_order_ids );

		$new_items = [];
		foreach ( $sanitized_items as $item ) {
			if ( array_key_exists( $item['shipstation_order_id'], $existing ) ) {
				continue;
			}
			$new_items[] = $item;
		}

		$ids = [];
		if ( count( $new_items ) ) {
			$ids = static::create_multiple( $new_items );
		}

		return $ids;
	}

	/**
	 * Find by ShipStation order ids.
	 *
	 * @param  array  $ids  ShipStation order ids.
	 *
	 * @return static[]|array
	 */
	public static function find_by_ship_station_order_ids( array $ids ): array {
		$ids   = array_map( 'intval', $ids );
		$items = static::get_query_builder()->where( 'shipstation_order_id', $ids, 'IN' )->get();
		$data  = [];
		foreach ( $items as $item ) {
			$data[ $item['shipstation_order_id'] ] = new static( $item );
		}

		return $data;
	}

	/**
	 * Sanitize ShipStation order response for database
	 *
	 * @param  array  $data
	 *
	 * @return array
	 */
	public static function sanitize_ship_station_order_for_database( array $data ): array {
		$advanced_options = $data['advancedOptions'] ?? [];
		$store_id         = intval( $advanced_options['storeId'] ?? 0 );

		return [
			'shipstation_order_id' => intval( $data['orderId'] ),
			'order_total_amount'   => floatval( $data['orderTotal'] ),
			'store_id'             => $store_id,
			'store_name'           => ShipStationApi::get_store_name( $store_id ),
		];
	}

	/**
	 * Create database table
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		$self       = new static;
		$table_name = static::get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `shipstation_order_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'ShipStation order id',
                `tree_planting_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'ShipStation order id',
                `order_total_amount` float(20,4) unsigned NOT NULL DEFAULT 0 COMMENT 'Order total amount',
                `store_id` varchar(20) NOT NULL DEFAULT '0' COMMENT 'Store id',
                `store_name` varchar(100) NULL DEFAULT NULL COMMENT 'Store name',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
    			UNIQUE(`shipstation_order_id`)
            ) $collate;";

		$version = get_option( $table_name . '-version' );
		if ( false === $version ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $table_schema );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}