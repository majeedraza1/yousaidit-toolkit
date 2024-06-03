<?php

namespace YouSaidItCards\Modules\FontManager\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class DesignerFont extends DatabaseModel {
	protected $table = 'designer_fonts';

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
			'for_public'   => true,
			'for_designer' => true,
		] );
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
	 * Get fonts for designer
	 *
	 * @param  int  $designer_id  Designer id.
	 *
	 * @return FontInfo[]
	 */
	public static function get_fonts( int $designer_id ): array {
		global $wpdb;
		$table   = static::get_table_name();
		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE designer_id = %d", $designer_id );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$fonts   = [];
		foreach ( $results as $result ) {
			$fonts[] = new FontInfo( array_merge( $result, [
				'for_public'   => true,
				'for_designer' => true,
			] ) );
		}

		return $fonts;
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
	}
}
