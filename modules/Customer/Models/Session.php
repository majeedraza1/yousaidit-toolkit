<?php

namespace YouSaidItCards\Modules\Customer\Models;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class Session extends DatabaseModel {

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'user_session';

	/**
	 * Create or update record
	 *
	 * @param int   $user_id
	 * @param array $data
	 *
	 * @return int
	 */
	public static function create_or_update( int $user_id, array $data ): int {
		$self = new static;
		global $wpdb;
		$table = $self->get_table_name();

		$sql  = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id );
		$item = $wpdb->get_row( $sql, ARRAY_A );

		$sanitize_data = [
			'user_id'       => $user_id,
			'session_value' => $data,
		];

		if ( is_array( $item ) && isset( $item['id'] ) ) {
			$sanitize_data['id'] = intval( $item['id'] );

			$self->update( $sanitize_data );

			return $sanitize_data['id'];
		}

		return $self->create( $sanitize_data );
	}

	public function find_by_user( int $user_id ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_single_item( $user_id );
		$item      = $this->get_cache( $cache_key );
		if ( false === $item ) {
			$sql  = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id );
			$item = $wpdb->get_row( $sql, ARRAY_A );

			// Set cache
			$this->set_cache( $cache_key, $item );
		}

		$data = $item ? new Data( $item ) : [];

		return isset( $data['session_value'] ) ? $data['session_value'] : new \ArrayObject();
	}

	public function delete_user_session( int $user_id ) {
		global $wpdb;
		$table = $this->get_table_name();

		return $wpdb->delete( $table, [ 'user_id' => $user_id ], '%d' );
	}

	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$self       = new static;
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) unsigned NOT NULL,
                `session_value` LONGTEXT NOT NULL,
                `expiry` bigint(20) unsigned NOT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( $table_name . '-version' );
		if ( false === $version ) {
			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}
