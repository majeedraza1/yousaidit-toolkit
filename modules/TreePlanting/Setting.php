<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\Supports\Validate;

/**
 * Setting
 */
class Setting {
	/**
	 * Default value
	 * @var array
	 */
	protected static $default = [
		'ecologi_api_key'                          => '680fefd3-fc30-0baa-483b-aecd33d6e0e5',
		'ecologi_purchase_tree_after_total_orders' => 20,
		'ecologi_funded_by'                        => '',
		'ecologi_is_test_mode'                     => 'yes',
	];

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		$options = (array) get_option( '_stackonet_toolkit', [] );

		return wp_parse_args( $options, static::$default );
	}

	/**
	 * Get setting by key
	 *
	 * @param  string  $key
	 * @param  mixed  $default
	 *
	 * @return mixed|null
	 */
	public static function get( string $key, $default = null ) {
		$settings = static::get_settings();
		if ( ! isset( $settings[ $key ] ) ) {
			return $default;
		}
		if ( '' === $settings[ $key ] ) {
			return $default;
		}

		return $settings[ $key ];
	}

	/**
	 * Get api key
	 *
	 * @return string
	 */
	public static function api_key(): string {
		return static::get( 'ecologi_api_key', '' );
	}

	/**
	 * Is test mode
	 *
	 * @return bool
	 */
	public static function is_test_mode(): bool {
		$env_type = wp_get_environment_type();
		if ( in_array( $env_type, [ 'local', 'development' ], true ) ) {
			return true;
		}

		return Validate::checked( static::get( 'ecologi_is_test_mode' ) );
	}

	/**
	 * Get funded by
	 *
	 * @return string
	 */
	public static function funded_by(): string {
		return static::get( 'ecologi_funded_by', get_option( 'blogname' ) );
	}

	public static function purchase_tree_after_total_orders(): int {
		$number = (int) static::get( 'ecologi_purchase_tree_after_total_orders' );

		return max( 1, $number );
	}

	/**
	 * Get cumulative orders ids
	 *
	 * @return array
	 */
	public static function get_cumulative_orders_ids(): array {
		$orders_ids = get_transient( 'cumulative_orders_ids' );

		return is_array( $orders_ids ) ? $orders_ids : [];
	}

	public static function update_cumulative_orders_ids( array $orders_ids ): array {
		$orders_ids = array_map( 'intval', $orders_ids );
		set_transient( 'cumulative_orders_ids', $orders_ids );

		return $orders_ids;
	}
}