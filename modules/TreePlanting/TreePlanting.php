<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

/**
 * PlantingTrees class
 */
class TreePlanting extends DatabaseModel {
	protected $table = 'tree_planting_log';

	public function to_array(): array {
		$data            = parent::to_array();
		$data['message'] = sprintf( 'Planting tree for orders: %s', implode( ', ', $data['orders_ids'] ) );

		return $data;
	}

	public static function create_tables() {
		global $wpdb;
		$self       = new static;
		$table_name = static::get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `orders_ids` TEXT NULL DEFAULT NULL COMMENT 'order ids',
                `status` VARCHAR(20) NOT NULL DEFAULT 'processing',
                `error_message` TEXT NULL DEFAULT NULL,
                `amount` VARCHAR(20) NOT NULL DEFAULT '0',
                `currency` VARCHAR(3) NULL DEFAULT NULL,
                `tree_url` TEXT NULL DEFAULT NULL,
                `name` VARCHAR(100) NULL DEFAULT NULL,
                `project_details` TEXT NULL DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";

		$version = get_option( $table_name . '-version' );
		if ( false === $version ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $table_schema );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}