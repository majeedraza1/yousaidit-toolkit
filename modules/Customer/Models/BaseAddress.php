<?php

namespace YouSaidItCards\Modules\Customer\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class BaseAddress extends DatabaseModel {

	/**
	 * @var string
	 */
	protected $table = 'user_address';

	/**
	 * Column name for holding author id
	 *
	 * @var string
	 */
	protected $created_by = 'user_id';

	/**
	 * User/Customer full name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->get( 'name' );
	}

	/**
	 * The entire phone number without the country code
	 *
	 * @return string
	 */
	public function get_phone_number(): string {
		return $this->get( 'phone_number' );
	}

	/**
	 * A street address and can be multiple line of text
	 *
	 * @return string
	 */
	public function get_street_address(): string {

	}

	/**
	 * Each individual line of the street address
	 * (Flat / House no. / Building / Company / Apartment)
	 *
	 * @return string
	 */
	public function get_address_line1(): string {
		return $this->get( 'address_line1' );
	}

	/**
	 * Each individual line of the street address
	 * (Area / Colony / Street / Sector / Village)
	 *
	 * @return string
	 */
	public function get_address_line2(): string {
		return $this->get( 'address_line2' );
	}

	/**
	 * The second administrative level in the address.
	 * (City / Town)
	 *
	 * @return string
	 */
	public function get_address_level2(): string {
		return $this->get( 'address_level2' );
	}

	/**
	 * The first administrative level in the address.
	 * (State / Province / Region)
	 *
	 * @return string
	 */
	public function get_address_level1(): string {
		return $this->get( 'address_level1' );
	}

	/**
	 * A two character ISO country code.
	 *
	 * @return string
	 */
	public function get_country_code(): string {
		return $this->get( 'country_code' );
	}

	/**
	 * A country name.
	 *
	 * @return string
	 */
	public function get_country_name(): string {
		return $this->get( 'country_name' );
	}

	/**
	 * Get postal code
	 *
	 * @return string
	 */
	public function get_postal_code(): string {
		return $this->get( 'postal_code' );
	}

	/**
	 * A name of nearby famous place
	 *
	 * @return string
	 */
	public function get_landmark(): string {
		return $this->get( 'landmark' );
	}

	/**
	 * The type of address. (Home / Office)
	 *
	 * @return string
	 */
	public function get_address_type(): string {
		return $this->get( 'address_type' );
	}

	/**
	 * User given custom label for tha address
	 *
	 * @return string
	 */
	public function get_address_label(): string {
		return $this->get( 'address_label' );
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
                `name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Full Name',
                `phone_number` VARCHAR(20) NULL DEFAULT NULL COMMENT 'The entire phone number without the country code',
                `address_line1` VARCHAR(255) NULL DEFAULT NULL COMMENT 'The first line of the street address',
                `address_line2` VARCHAR(255) NULL DEFAULT NULL COMMENT 'The second line of the street address',
                `address_level2` VARCHAR(100) NULL DEFAULT NULL COMMENT 'The second administrative level in the address. (City / Town)',
                `address_level1` VARCHAR(100) NULL DEFAULT NULL COMMENT 'The first administrative level in the address. (State / Province / Region)',
                `country_code` CHAR(2) NULL DEFAULT NULL COMMENT 'A two character ISO country code',
                `country_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'A country name',
                `postal_code` VARCHAR(20) NULL DEFAULT NULL COMMENT 'A postal code or a ZIP code.',
                `landmark` VARCHAR(255) NULL DEFAULT NULL COMMENT 'A name of nearby famous place',
                `address_type` VARCHAR(50) NULL DEFAULT NULL COMMENT 'The type of address. (Home / Office)',
                `address_label` VARCHAR(50) NULL DEFAULT NULL COMMENT 'User given address label',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );

		$version = get_option( $table_name . '-version' );
		if ( false === $version ) {
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `country_code` (`country_code`);" );
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `postal_code` (`postal_code`);" );
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `address_level1` (`address_level1`);" );
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX `address_level2` (`address_level2`);" );

			$constant_name = $self->get_foreign_key_constant_name( $table_name, $wpdb->users );
			$sql           = "ALTER TABLE `{$table_name}` ADD CONSTRAINT `{$constant_name}` FOREIGN KEY (`user_id`)";
			$sql           .= " REFERENCES `{$wpdb->users}`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.0', false );
		}
	}

	/**
	 * @return array[]
	 */
	public static function rest_create_item_params(): array {
		return [
			'name'           => [
				'description'       => __( 'full name' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'country_code'   => [
				'description'       => __( 'A two character ISO country code.' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'postal_code'    => [
				'description'       => __( 'A postal code or a ZIP code.' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_level1' => [
				'description'       => __( 'The first administrative level in the address. (State / Province / Region)' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_level2' => [
				'description'       => __( 'The second administrative level in the address. (City / Town)' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_line1'  => [
				'description'       => __( 'First line of the street address (Flat / House no. / Building / Company / Apartment)' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_line2'  => [
				'description'       => __( 'Second line of the street address (Area / Colony / Street / Sector / Village)' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'phone_number'   => [
				'description'       => __( 'The entire phone number without the country code' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'landmark'       => [
				'description'       => __( 'A name of nearby famous place' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_type'   => [
				'description'       => __( 'The type of address. (Home / Office)' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'address_label'  => [
				'description'       => __( 'User given address label' ),
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
