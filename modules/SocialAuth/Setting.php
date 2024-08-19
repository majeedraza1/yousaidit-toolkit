<?php

namespace YouSaidItCards\Modules\SocialAuth;

class Setting {
	protected static array $enabled_providers = [];

	/**
	 * Get available providers
	 *
	 * @return array
	 */
	public static function get_available_providers(): array {
		return [
			'facebook' => __( 'Facebook Auth', 'yousaidit-toolkit' ),
			'google'   => __( 'Google Auth', 'yousaidit-toolkit' ),
		];
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		return (array) get_option( '_stackonet_toolkit', [] );
	}

	/**
	 * Get social auth providers
	 *
	 * @return array
	 */
	public static function get_social_auth_providers(): array {
		if ( empty( static::$enabled_providers ) ) {
			$settings            = self::get_settings();
			$available_providers = array_keys( static::get_available_providers() );
			if ( isset( $settings['social_auth_providers'] ) && is_array( $settings['social_auth_providers'] ) ) {
				foreach ( $settings['social_auth_providers'] as $provider ) {
					if ( in_array( $provider, $available_providers ) ) {
						static::$enabled_providers[] = $provider;
					}
				}
			}
		}

		return static::$enabled_providers;
	}

	public static function is_provider_enabled( string $provider ): bool {
		$provider          = str_replace( '.com', '', $provider );
		$enabled_providers = static::get_social_auth_providers();

		return in_array( $provider, $enabled_providers, true );
	}
}
