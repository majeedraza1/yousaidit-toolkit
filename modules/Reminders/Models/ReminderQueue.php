<?php

namespace YouSaidItCards\Modules\Reminders\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class ReminderQueue extends DatabaseModel {
	const STATUS_SENT = 'sent';
	const STATUS_PENDING = 'pending';
	const STATUS_FAILED = 'failed';
	const STATUSES = [ 'pending', 'sent', 'failed' ];
	protected $table = 'reminder_queues';
	protected $status = 'status';

	/**
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();

		$counts = array_fill_keys( self::STATUSES, 0 );

		$sql = "SELECT COUNT(*) AS total_records FROM {$table}";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row           = $wpdb->get_row( $sql, ARRAY_A );
		$counts['all'] = isset( $row['total_records'] ) ? intval( $row['total_records'] ) : 0;

		$query_status = "SELECT status, COUNT( * ) AS num_rows FROM {$table} GROUP BY status";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = (array) $wpdb->get_results( $query_status, ARRAY_A );

		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = intval( $row['num_rows'] );
		}

		return $counts;
	}

	/**
	 * @return int[]
	 */
	public static function get_pending_queue_ids(): array {
		global $wpdb;
		$self    = new self();
		$table   = $self->get_table_name();
		$results = $wpdb->get_results( "SELECT id FROM {$table} WHERE status = 'pending'", ARRAY_A );

		$data = [];
		if ( $results ) {
			$data = array_map( 'intval', wp_list_pluck( $results, 'id' ) );
		}

		return $data;
	}

	/**
	 * Get list for admin
	 *
	 * @param int $page Page number.
	 * @param int $per_page Number of items per page.
	 * @param string $status Status of the queue.
	 *
	 * @return array
	 */
	public static function list( int $page = 1, int $per_page = 50, $status = 'any' ): array {
		$queues = ( new static() )->find_multiple( [
			'page'     => $page,
			'per_page' => $per_page,
			'status'   => $status,
		] );

		$reminder_ids = wp_list_pluck( $queues, 'reminder_id' );
		$reminders    = ( new Reminder() )->find_multiple( [
			'id__in' => $reminder_ids,
		] );

		$data = [];
		foreach ( $queues as $queue ) {
			$reminder = wp_list_filter( $reminders, [
				'id' => $queue->get( 'reminder_id' ),
			] );
			if ( ! empty( $reminder ) ) {
				$reminder = array_shift( $reminder );
				$data[]   = array_merge( $queue->to_array(), [
					'title'   => $reminder->get( 'name' ),
					'user_id' => $reminder->get( 'user_id' ),
				] );
			} else {
				$data[] = array_merge( $queue->to_array(), [
					'title'   => 'Deleted',
					'user_id' => 0,
				] );;
			}
		}

		return $data;
	}

	public static function add_to_queue( Reminder $reminder ): int {
		$queue_id = ( new static )->create( [
			'reminder_id'       => $reminder->get( 'id' ),
			'reminder_group_id' => $reminder->get( 'reminder_group_id' ),
			'remind_date'       => $reminder->get( 'remind_date' ),
			'occasion_date'     => $reminder->get( 'occasion_date' ),
			'reminder_title'    => $reminder->get( 'name' ),
			'user_id'           => $reminder->get( 'user_id' ),
			'status'            => 'pending',
		] );

		if ( $queue_id ) {
			$reminder->update( [
				'id'          => $reminder->get( 'id' ),
				'is_in_queue' => 1,
			] );
		}

		return $queue_id;
	}

	/**
	 * Find multiple records from database
	 *
	 * @param array $args
	 *
	 * @return array|static[]
	 */
	public function find_multiple( $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();

		$cache_key = $this->get_cache_key_for_collection( $args );
		$items     = $this->get_cache( $cache_key );
		if ( false === $items ) {
			list( $per_page, $offset ) = $this->get_pagination_and_order_data( $args );
			$order_by = $this->get_order_by( $args );
			$status   = isset( $args['status'] ) ? $args['status'] : null;

			$query = "SELECT * FROM {$table} WHERE 1=1";

			if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
				$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
			}

			if ( isset( $args[ $this->primaryKey . '__in' ] ) && is_array( $args[ $this->primaryKey . '__in' ] ) ) {
				if ( $this->primaryKeyType == '%d' ) {
					$ids__in = array_map( 'intval', $args[ $this->primaryKey . '__in' ] );
					$query   .= " AND {$this->primaryKey} IN(" . implode( ",", $ids__in ) . ")";
				} else {
					$ids__in = array_map( 'esc_sql', $args[ $this->primaryKey . '__in' ] );
					$query   .= " AND {$this->primaryKey} IN('" . implode( "', '", $ids__in ) . "')";
				}
			}

			if ( in_array( $this->deleted_at, static::get_columns_names( $table ) ) ) {
				if ( 'trash' == $status ) {
					$query .= " AND {$this->deleted_at} IS NOT NULL";
				} else {
					$query .= " AND {$this->deleted_at} IS NULL";
				}
			}

			if ( in_array( $this->status, static::get_columns_names( $table ) ) ) {
				if ( $status && ! in_array( $status, [ 'any', 'all', 'trash' ] ) ) {
					$query .= $wpdb->prepare( " AND {$this->status} = %s", $status );
				}
			}

			$query .= " ORDER BY {$order_by}";
			if ( $per_page > 0 ) {
				$query .= $wpdb->prepare( " LIMIT %d", $per_page );
			}
			if ( $offset >= 0 ) {
				$query .= $wpdb->prepare( " OFFSET %d", $offset );
			}
			$items = $wpdb->get_results( $query, ARRAY_A );

			// Set cache for one day
			$this->set_cache( $cache_key, $items, DAY_IN_SECONDS );
		}

		$data = [];
		foreach ( $items as $item ) {
			$data[] = new static( $item );
		}

		return $data;
	}

	public static function create_table() {
		global $wpdb;
		$self       = new static();
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `reminder_id` bigint(20) unsigned NULL DEFAULT NULL,
                `reminder_group_id` bigint(20) unsigned NULL DEFAULT NULL,
                `remind_date` date DEFAULT NULL,
                `occasion_date` date DEFAULT NULL,
    			`status` VARCHAR(50) NULL DEFAULT 'pending' COMMENT 'Queue status',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( $table_name . '-version', '0.1.0' );
		if ( version_compare( $version, '1.1.0', '<' ) ) {
			$sql = "ALTER TABLE {$table_name} ADD `reminder_title` VARCHAR(100) NULL DEFAULT NULL AFTER `reminder_id`;";
			$wpdb->query( $sql );
			$sql = "ALTER TABLE {$table_name} ADD `user_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `reminder_group_id`;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.1.0', false );
		}
	}
}
