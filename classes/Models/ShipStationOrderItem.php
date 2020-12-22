<?php

namespace Yousaidit\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class ShipStationOrderItem extends DatabaseModel {

	/**
	 * @inhericDoc
	 */
	protected $table = 'ship_station_orders_items';

	/**
	 * Get orders
	 *
	 * @param array $orders_ids
	 *
	 * @return array|static[]
	 */
	public static function get_items( array $orders_ids ) {
		global $wpdb;
		$self       = new static;
		$table      = $self->get_table_name();
		$orders_ids = array_map( 'intval', $orders_ids );

		$sql   = "SELECT * FROM {$table} WHERE `orderId` IN(" . implode( ',', $orders_ids ) . ")";
		$items = $wpdb->get_results( $sql, ARRAY_A );

		$data = [];
		foreach ( $items as $item ) {
			$data[] = new static( $item );
		}

		return $data;
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
			orderItemId bigint(20) UNSIGNED NOT NULL,
			lineItemKey varchar(100) NULL DEFAULT NULL,
			sku varchar(50) NULL DEFAULT NULL,
			name varchar(255) NULL DEFAULT NULL,
			imageUrl text NULL DEFAULT NULL,
			weight text NULL DEFAULT NULL,
			quantity int(4) NOT NULL DEFAULT 0,
			unitPrice float(50) NOT NULL DEFAULT 0,
			taxAmount float(50) NULL DEFAULT NULL,
			shippingAmount float(50) NULL DEFAULT NULL,
			warehouseLocation text NULL DEFAULT NULL,
			options text NULL DEFAULT NULL,
			productId bigint(20) UNSIGNED NOT NULL,
			fulfillmentSku varchar(255) NULL DEFAULT NULL,
			adjustment tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
			upc varchar(255) NULL DEFAULT NULL,
			createDate DATETIME NULL DEFAULT NULL,
			modifyDate DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );

		$f_table       = $self->get_table_name( 'ship_station_orders' );
		$constant_name = $self->get_foreign_key_constant_name( $table_name, $f_table );

		$version = get_option( $table_name . '-version', '0.1.0' );;
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			$sql = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`orderId`)";
			$sql .= " REFERENCES `{$f_table}`(`orderId`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0' );
		}
	}
}
