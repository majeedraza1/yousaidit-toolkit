<?php

namespace YouSaidItCards\Modules\DynamicCard;

class EnvelopeColours {
	private static function get_base_path(): string {
		$upload_dir = wp_get_upload_dir();
		$base_path  = $upload_dir['basedir'] . '/' . 'envelope-colours';

		if ( file_exists( $base_path ) ) {
			return $base_path;
		}

		return '';
	}

	private static function get_colors(): array {
		$default_data = [
			'width'  => 614,
			'height' => 614,
			'card'   => [ 'width' => 421, 'height' => 430, 'x' => 129, 'y' => 120 ]
		];

		return [
			"Black"  => array_merge( [ 'name' => 'Black.jpg' ], $default_data ),
			"Blue"   => array_merge( [ 'name' => 'Blue.jpg' ], $default_data ),
			"Brown"  => array_merge( [ 'name' => 'Brown.jpg' ], $default_data ),
			"Cream"  => array_merge( [ 'name' => 'Cream.jpg' ], $default_data ),
			"Orange" => array_merge( [ 'name' => 'Orange.jpg' ], $default_data ),
			"Red"    => array_merge( [ 'name' => 'Red.jpg' ], $default_data ),
			"Silver" => array_merge( [ 'name' => 'Silver.jpg' ], $default_data ),
		];
	}

	/**
	 * Get random color
	 *
	 * @return false|string[] False value if file is not exists.
	 * Array containing key: name, label and path on success
	 */
	public static function get_random_color() {
		$colors     = self::get_colors();
		$color_key  = array_rand( $colors, 1 );
		$color_info = $colors[ $color_key ];
		$path       = join( '/', [ self::get_base_path(), $color_info['name'] ] );
		if ( ! file_exists( $path ) ) {
			return false;
		}
		$color_info['label'] = $color_key;
		$color_info['path']  = $path;

		return $color_info;
	}
}
