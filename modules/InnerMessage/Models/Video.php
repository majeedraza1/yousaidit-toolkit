<?php

namespace YouSaidItCards\Modules\InnerMessage\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

/**
 * Video class
 */
class Video extends DatabaseModel {
	protected $table = 'inner_message_video';

	public static function create_table() {
		$self  = new self();
		$table = $self->get_table_name();

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS $table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_id bigint(20) DEFAULT NULL,
			order_id bigint(20) DEFAULT NULL,
			uploaded_date datetime NULL DEFAULT NULL,
			deleted_date datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
    		INDEX `customer_id` (`customer_id`)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
