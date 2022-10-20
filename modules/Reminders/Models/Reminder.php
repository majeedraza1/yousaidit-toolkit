<?php

namespace YouSaidItCards\Modules\Reminders\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use WP_User;

class Reminder extends DatabaseModel {
	protected $table = 'reminders';
	protected $created_by = 'user_id';
	protected $reminder_group = null;
	protected $user = null;

	public function to_array(): array {
		$data                       = parent::to_array();
		$data['is_recurring']       = $this->is_recurring();
		$data['has_custom_address'] = $this->has_custom_address();

		return $data;
	}

	/**
	 * Check if it is recurring reminder
	 *
	 * @return bool
	 */
	public function is_recurring(): bool {
		return $this->get( 'is_recurring' ) == 1;
	}

	/**
	 * Check if it has custom address
	 *
	 * @return bool
	 */
	public function has_custom_address(): bool {
		return (int) $this->get( 'has_custom_address' ) === 1;
	}

	/**
	 * Get the user
	 *
	 * @return false|WP_User
	 */
	public function get_user() {
		if ( is_null( $this->user ) ) {
			$this->user = get_user_by( 'id', (int) $this->get( 'user_id' ) );
		}

		return $this->user;
	}

	/**
	 * Get the reminder group
	 *
	 * @return ReminderGroup
	 */
	public function get_reminder_group(): ReminderGroup {
		if ( is_null( $this->reminder_group ) ) {
			$this->reminder_group = new ReminderGroup( $this->get( 'reminder_group_id' ) );
		}

		return $this->reminder_group;
	}

	public function find_by_user( int $user_id, array $args = [] ): array {
		return $this->find_multiple( wp_parse_args( $args, [
			'user_id'  => $user_id,
			'per_page' => 100,
			'order_by' => [
				'occasion_date ASC',
				'name ASC',
				'id DESC'
			],
		] ) );
	}

	/**
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$sql   = "SELECT COUNT(*) AS total_records FROM {$table} WHERE 1 = 1";
		if ( isset( $args['user_id'] ) ) {
			$sql .= $wpdb->prepare( " AND user_id = %d", intval( $args['user_id'] ) );
		}
		if ( isset( $args['reminder_group_id'] ) ) {
			$sql .= $wpdb->prepare( " AND reminder_group_id = %d", intval( $args['reminder_group_id'] ) );
		}
		$row = $wpdb->get_row( $sql, ARRAY_A );

		return isset( $row['total_records'] ) ? intval( $row['total_records'] ) : 0;
	}

	/**
	 * Get due reminders
	 *
	 * @return int[]
	 */
	public static function get_due_reminders_ids(): array {
		global $wpdb;
		$self  = new static();
		$table = $self->get_table_name();

		$date = gmdate( 'Y-m-d', time() );

		$reminders = $wpdb->get_results(
			$wpdb->prepare( "SELECT id FROM $table WHERE is_in_queue = 0 AND remind_date <= %s", $date ),
			ARRAY_A
		);

		$data = [];
		if ( $reminders ) {
			$data = array_map( 'intval', wp_list_pluck( $reminders, 'id' ) );
		}

		return $data;
	}

	/**
	 * Get expired reminders
	 *
	 * @return int[]
	 */
	public static function get_expired_recurring_reminders_ids(): array {
		global $wpdb;
		$self  = new static();
		$table = $self->get_table_name();

		$date = gmdate( 'Y-m-d', time() );

		$reminders = $wpdb->get_results(
			$wpdb->prepare( "SELECT id FROM $table WHERE is_recurring = 1 AND occasion_date < %s", $date ),
			ARRAY_A
		);

		$data = [];
		if ( $reminders ) {
			$data = array_map( 'intval', wp_list_pluck( $reminders, 'id' ) );
		}

		return $data;
	}

	/**
	 * Create table
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;
		$self        = new static();
		$table_name  = $self->get_table_name();
		$table1_name = $self->get_table_name( 'reminder_groups' );
		$collate     = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) unsigned NOT NULL,
                `reminder_group_id` bigint(20) unsigned NULL DEFAULT NULL,
                `name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Reminder name',
                `occasion_date` date DEFAULT NULL,
                `remind_days_count` tinyint(2) DEFAULT NULL,
                `remind_date` date DEFAULT NULL,
    			`address_line1` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Address line 1',
    			`address_line2` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Address line 2',
    			`postal_code` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Postal code',
    			`city` VARCHAR(100) NULL DEFAULT NULL COMMENT 'City name',
    			`state` VARCHAR(50) NULL DEFAULT NULL COMMENT 'State name',
    			`country_code` CHAR(2) NULL DEFAULT NULL COMMENT 'ISO Alpha-2 Country code',
                `is_recurring` tinyint(1) DEFAULT 0,
                `has_custom_address` tinyint(1) DEFAULT 0,
                `is_in_queue` tinyint(1) DEFAULT 0,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( $table_name . '-version', '0.1.0' );
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			$constant_name = $self->get_foreign_key_constant_name( $table_name, $table1_name );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`reminder_group_id`)";
			$sql           .= " REFERENCES `{$table1_name}`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}

		if ( version_compare( $version, '1.1.0', '<' ) ) {
			$sql = "ALTER TABLE {$table_name} ADD `last_name` VARCHAR(50) NULL DEFAULT NULL AFTER `remind_date`;";
			$wpdb->query( $sql );
			$sql = "ALTER TABLE {$table_name} ADD `first_name` VARCHAR(100) NULL DEFAULT NULL AFTER `remind_date`;";
			$wpdb->query( $sql );
			$sql = "ALTER TABLE {$table_name} ADD `state` VARCHAR(50) NULL DEFAULT NULL AFTER `city`;";
			$wpdb->query( $sql );
			$sql = "ALTER TABLE {$table_name} ADD `has_custom_address` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_recurring`;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.1.0', false );
		}
	}
}
