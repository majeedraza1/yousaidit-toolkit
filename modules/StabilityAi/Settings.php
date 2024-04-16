<?php

namespace YouSaidItCards\Modules\StabilityAi;

use Stackonet\WP\Framework\Supports\Sanitize;
use Stackonet\WP\Framework\Supports\Validate;

/**
 * Settings class
 */
class Settings {
	/**
	 * Option name for the setting
	 */
	const OPTION_NAME = 'STABILITY_AI_SETTINGS';

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return [
			'apiKey'                => '',
			'engine_id'             => 'stable-diffusion-v1-6',
			'style_preset'          => '',
			'imageWidth'            => 1024,
			'imageHeight'           => 1024,
			'defaultPrompt'         => 'White background with text "{{title}}". Make font size bigger so the text cover 75% of image.',
			'fileNamingMethod'      => 'uuid',
		];
	}

	/**
	 * If the setting is defined from config file
	 *
	 * @return bool
	 */
	public static function is_in_config_file(): bool {
		return defined( self::OPTION_NAME );
	}

	/**
	 * Get settings
	 *
	 * @return array The settings.
	 */
	public static function get_settings(): array {
		$default = static::defaults();
		if ( static::is_in_config_file() ) {
			$settings = constant( self::OPTION_NAME );
			if ( is_string( $settings ) ) {
				$settings = json_decode( $settings, true );
			}
		} else {
			$settings = get_option( self::OPTION_NAME );
		}

		if ( is_array( $settings ) ) {
			return array_merge( $default, $settings );
		}

		return $default;
	}

	/**
	 * Update settings
	 *
	 * @param  mixed $value  Raw value to be updated.
	 *
	 * @return array
	 */
	public static function update_settings( $value ): array {
		$current = static::get_settings();
		if ( ! is_array( $value ) ) {
			return $current;
		}
		$sanitized = [];
		foreach ( static::defaults() as $key => $default ) {
			if ( isset( $value[ $key ] ) ) {
				if ( 'style_preset' === $key ) {
					$sanitized[ $key ] = in_array(
						$value[ $key ],
						StabilityAiClient::get_style_presets(),
						true
					) ? $value[ $key ] : '';
				} elseif ( 'autoGenerateThumbnail' === $key ) {
					$sanitized[ $key ] = Validate::checked( $value[ $key ] );
				} else {
					$sanitized[ $key ] = Sanitize::deep( $value[ $key ] );
				}
			} elseif ( isset( $current[ $key ] ) ) {
				$sanitized[ $key ] = $current[ $key ];
			} else {
				$sanitized[ $key ] = $default;
			}
		}

		update_option( self::OPTION_NAME, $sanitized, true );

		return $sanitized;
	}

	/**
	 * Get setting
	 *
	 * @param  string $key  The setting key.
	 * @param  mixed  $default  The default value.
	 *
	 * @return false|mixed
	 */
	public static function get_setting( string $key, $default = false ) {
		$settings = static::get_settings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Get api key
	 *
	 * @return string
	 */
	public static function get_api_key(): string {
		return (string) static::get_setting( 'apiKey' );
	}

	/**
	 * Get default prompt
	 *
	 * @return string
	 */
	public static function get_default_prompt(): string {
		return (string) static::get_setting( 'defaultPrompt' );
	}

	/**
	 * Get file naming method
	 *
	 * @return string
	 */
	public static function get_file_naming_method(): string {
		$naming_method = static::get_setting( 'fileNamingMethod' );
		$valid         = [ 'uuid', 'post_title' ];

		return in_array( $naming_method, $valid, true ) ? $naming_method : 'post_title';
	}

	/**
	 * Get engine id
	 *
	 * @return string
	 */
	public static function get_engine_id(): string {
		return (string) static::get_setting( 'engine_id' );
	}

	/**
	 * Get engine id
	 *
	 * @return string
	 */
	public static function get_style_preset(): string {
		return (string) static::get_setting( 'style_preset' );
	}

	/**
	 * Get image width
	 *
	 * @return int
	 */
	public static function get_image_width(): int {
		$width = (int) static::get_setting( 'imageWidth' );
		if ( 'stable-diffusion-v1-6' === static::get_engine_id() ) {
			$width = min( 1536, max( 320, $width ) );
		}

		// an increment divisible by 64.
		$reminder = $width % 64;
		if ( $reminder > 0 ) {
			$width = ( $width - $reminder );
		}

		return $width;
	}

	/**
	 * Get image height
	 *
	 * @return int
	 */
	public static function get_image_height(): int {
		$height = (int) static::get_setting( 'imageHeight' );
		if ( 'stable-diffusion-v1-6' === static::get_engine_id() ) {
			$height = min( 1536, max( 320, $height ) );
		}

		// an increment divisible by 64.
		$reminder = $height % 64;
		if ( $reminder > 0 ) {
			$height = ( $height - $reminder );
		}

		return $height;
	}
}
