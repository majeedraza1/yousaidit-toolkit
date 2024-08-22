<?php

namespace YouSaidItCards\Modules\FontManager\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;

class DesignerFont extends DatabaseModel {
	protected $table = 'designer_fonts';
	protected $created_by = 'designer_id';

	public function to_array(): array {
		$data                = $this->get_font_info()->to_array();
		$data['id']          = $this->get_id();
		$data['designer_id'] = $this->get_designer_id();

		return $data;
	}

	/**
	 * Get font info object
	 *
	 * @return FontInfo
	 */
	public function get_font_info(): FontInfo {
		return new FontInfo( [
			'slug'         => $this->get_prop( 'slug' ),
			'font_family'  => $this->get_prop( 'font_family' ),
			'font_file'    => $this->get_prop( 'font_file' ),
			'group'        => $this->get_prop( 'group' ),
			'for_public'   => $this->for_public(),
			'for_designer' => $this->for_designer(),
		] );
	}

	/**
	 * Get font file
	 *
	 * @return string
	 */
	public function get_font_file(): string {
		return (string) $this->get_prop( 'font_file' );
	}

	/**
	 * Get designer id
	 *
	 * @return int
	 */
	public function get_designer_id(): int {
		return (int) $this->get_prop( 'designer_id' );
	}

	/**
	 * Is this for public
	 *
	 * @return bool
	 */
	public function for_public(): bool {
		return Validate::checked( $this->get_prop( 'for_public', 0 ) );
	}

	/**
	 * Is this for designer
	 *
	 * @return bool
	 */
	public function for_designer(): bool {
		return Validate::checked( $this->get_prop( 'for_designer', 1 ) );
	}

	/**
	 * Get fonts for designer
	 *
	 * @param  int  $designer_id  Designer id.
	 *
	 * @return FontInfo[]
	 */
	public static function get_fonts( int $designer_id, int $per_page = 20, int $page = 1 ): array {
		global $wpdb;
		$table   = static::get_table_name();
		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE designer_id = %d", $designer_id );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$fonts   = [];
		foreach ( $results as $result ) {
			$fonts[] = new static( $result );
		}

		return $fonts;
	}

	public static function get_total_fonts_count( int $designer_id ): int {
		global $wpdb;
		$table = static::get_table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total_records FROM {$table} WHERE designer_id = %d",
				$designer_id
			),
			ARRAY_A
		);

		return isset( $row['total_records'] ) ? intval( $row['total_records'] ) : 0;
	}

	/**
	 * Create table
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = static::get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `designer_id` BIGINT(20) unsigned NOT NULL,
                `slug` VARCHAR(255) NULL DEFAULT NULL,
                `font_family` VARCHAR(255) NULL DEFAULT NULL,
                `font_file` VARCHAR(255) NULL DEFAULT NULL,
                `group` VARCHAR(50) NULL DEFAULT NULL,
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

		if ( version_compare( $version, '1.0.1', '<' ) ) {
			$sql = "ALTER TABLE {$table_name} ADD `for_public` TINYINT NOT NULL DEFAULT 0 AFTER `group`;";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE {$table_name} ADD `for_designer` TINYINT NOT NULL DEFAULT 1 AFTER `group`;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.1' );
		}
	}
}
