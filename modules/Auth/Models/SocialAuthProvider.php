<?php

namespace YouSaidItCards\Modules\Auth\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;
use WP_User;

class SocialAuthProvider extends DatabaseModel {

	/**
	 * @var string
	 */
	protected $table = 'user_social_auth_provider';

	/**
	 * @var string[]
	 */
	protected static array $providers = [ 'apple', 'google', 'facebook', 'twitter' ];

	/**
	 * @return string[]
	 */
	public static function get_providers(): array {
		return self::$providers;
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return [
			'email_address' => $this->get_prop( 'email_address', '' ),
			'phone_number'  => $this->get_prop( 'phone_number', '' ),
			'display_name'  => $this->get_display_name(),
			'is_active'     => $this->is_active(),
		];
	}

	/**
	 * Get user id
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return absint( $this->get_prop( 'user_id' ) );
	}

	/**
	 * Is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return Validate::checked( $this->get_prop( 'is_active' ) );
	}

	/**
	 * Get display name
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		$first_name = $this->get_prop( 'first_name', '' );
		$last_name  = $this->get_prop( 'last_name', '' );

		if ( ! empty( $first_name ) ) {
			return "{$first_name} {$last_name}";
		}

		return $last_name;
	}

	/**
	 * @param  string  $provider
	 * @param  string  $provider_id
	 * @param  int  $user_id
	 *
	 * @return false|static
	 */
	public static function find_for( string $provider, string $provider_id, int $user_id = 0 ) {
		global $wpdb;
		$table = ( new static )->get_table_name();
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE provider = %s AND provider_id = %s",
			$provider, sha1( $provider_id ) );

		if ( $user_id ) {
			$sql .= $wpdb->prepare( " AND user_id = %d", intval( $user_id ) );
		}

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? new static( $result ) : false;
	}

	/**
	 * @param  int  $user_id
	 * @param  string|null  $provider
	 *
	 * @return static[]|array
	 */
	public static function find_for_user( int $user_id, ?string $provider = null ): array {
		global $wpdb;
		$table = ( new static )->get_table_name();
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id );

		if ( ! empty( $provider ) ) {
			$sql .= $wpdb->prepare( " AND provider = %s", $provider );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );
		$items   = [];
		foreach ( $results as $result ) {
			$items[] = new static( $result );
		}

		return $items;
	}

	/**
	 * @param  array  $data
	 *
	 * @return int
	 */
	public static function create_or_update( array $data ): int {
		$item                = static::find_for( $data['provider'], $data['provider_id'] );
		$data['provider_id'] = sha1( $data['provider_id'] );
		if ( $item instanceof static ) {
			$data['id'] = $item->get_id();
			( new static )->update( $data );

			return $item->get_id();
		}

		return ( new static )->create( $data );
	}

	/**
	 * Unlink a provider
	 *
	 * @param  array  $data
	 *
	 * @return bool
	 */
	public static function unlink( array $data ) {
		$item = static::find_for( $data['provider'], $data['provider_id'] );
		if ( $item instanceof self ) {
			return $item->delete( (int) $item->get_prop( 'id' ) );
		}

		return false;
	}

	/**
	 * @param  string|mixed  $provider
	 * @param  string|mixed  $provider_unique_id
	 *
	 * @return WP_Error|WP_User
	 */
	public static function authenticate( $provider, $provider_unique_id ) {
		if ( empty( $provider_unique_id ) ) {
			return new WP_Error( 'missing_required_parameter', 'provider_id is required.' );
		}

		if ( ! in_array( $provider, static::get_providers() ) ) {
			return new WP_Error( 'unsupported_provider', 'Provider does not support.', array( 'status' => 403, ) );
		}

		$social_auth_provider = static::find_for( $provider, $provider_unique_id );
		if ( $social_auth_provider instanceof self ) {
			$user = get_user_by( 'id', $social_auth_provider->get_prop( 'user_id' ) );
		}

		if ( ! ( isset( $user ) && $user instanceof WP_User ) ) {
			return new WP_Error( 'user_not_found', 'No user found.', array( 'status' => 404, ) );
		}

		return $user;
	}

	public static function find_by_email( string $email ) {
		global $wpdb;
		$table = ( new static )->get_table_name();
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE email_address = %s", $email );

		$result = $wpdb->get_row( $sql, ARRAY_A );
		if ( $result ) {
			return new static( $result );
		}

		return false;
	}

	/**
	 * @param  string|mixed  $email
	 *
	 * @return bool
	 */
	public static function email_exists( $email ): bool {
		global $wpdb;
		$table = ( new static )->get_table_name();
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE email_address = %s", $email );

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return is_array( $result ) && ( isset( $result['email_address'] ) && $result['email_address'] == $email );
	}

	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = static::get_table_name();
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

		$version = get_option( $table_name . '-version', '0.1.0' );
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $table_schema );

			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `provider` (`provider`);" );
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `provider_id` (`provider_id`);" );

			$constant_name = static::get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}
}
