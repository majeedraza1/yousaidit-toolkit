<?php

namespace YouSaidItCards\ShipStation;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class ShipStationOrderAddress extends DatabaseModel {

	/**
	 * @inhericDoc
	 */
	protected $table = 'ship_station_orders_addresses';

	/**
	 * Get orders
	 *
	 * @param array $orders_ids
	 *
	 * @return array|static[]
	 */
	public static function get_addresses( array $orders_ids ) {
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
			address_type varchar(20) NULL DEFAULT NULL COMMENT 'billing or shipping',
			name varchar(100) NULL DEFAULT NULL,
			company varchar(100) NULL DEFAULT NULL,
			street1 varchar(100) NULL DEFAULT NULL,
			street2 varchar(100) NULL DEFAULT NULL,
			street3 varchar(100) NULL DEFAULT NULL,
			city varchar(100) NULL DEFAULT NULL,
			state varchar(100) NULL DEFAULT NULL,
			postalCode varchar(100) NULL DEFAULT NULL,
			country varchar(2) NULL DEFAULT NULL,
			phone varchar(100) NULL DEFAULT NULL,
			residential tinyint(1) NULL DEFAULT NULL,
			addressVerified varchar(100) NULL DEFAULT NULL,
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
