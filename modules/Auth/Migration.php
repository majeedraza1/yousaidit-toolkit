<?php

namespace YouSaidItCards\Modules\Auth;

use YouSaidItCards\Modules\Auth\Models\SocialAuthProvider;

class Migration {
	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$self       = new SocialAuthProvider;
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) unsigned NOT NULL,
                `provider` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Social provider name (apple, google, facebook, etc)',
                `provider_id` CHAR(40) NULL DEFAULT NULL COMMENT 'Social provider sha1 hash value',
                `email_address` VARCHAR(100) NULL DEFAULT NULL,
                `phone_number` VARCHAR(20) NULL DEFAULT NULL,
                `first_name` VARCHAR(100) NULL DEFAULT NULL,
                `last_name` VARCHAR(50) NULL DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( $table_name . '-version', '0.1.0' );
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `provider` (`provider`);" );
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `provider_id` (`provider_id`);" );

			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}
