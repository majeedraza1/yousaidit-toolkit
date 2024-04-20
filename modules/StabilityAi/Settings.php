<?php

namespace YouSaidItCards\Modules\StabilityAi;

use Stackonet\WP\Framework\Supports\Sanitize;

/**
 * Settings class
 */
class Settings {
	/**
	 * Option name for the setting
	 */
	const OPTION_NAME = 'STABILITY_AI_SETTINGS';

	const OCCASIONS = [
		"birthday"            => "Birthday",
		"christmas"           => "Christmas",
		"valentines_day"      => "Valentines",
		"wedding_anniversary" => "Wedding anniversary",
		"mothers_day"         => "Mother's day",
		"fathers_day"         => "Father's day",
		"new_baby"            => "New baby",
		"get_well"            => "Get well",
		"thank_you"           => "Thank you",
		"congratulations"     => "Congratulations",
		"break_up"            => "Break up",
	];

	const RECIPIENTS = [
		"friend"        => "Friend",
		"husband"       => "Husband",
		"wife"          => "Wife",
		"mother"        => "Mother",
		"father"        => "Father",
		"daughter"      => "Daughter",
		"son"           => "Son",
		"grandmother"   => "Grandmother",
		"grandfather"   => "Grandfather",
		"granddaughter" => "Granddaughter",
		"grandson"      => "Grandson",
		"sister"        => "Sister",
		"brother"       => "Brother",
		"aunt"          => "Aunt",
		"uncle"         => "Uncle",
		"cousin"        => "Cousin",
		"nephew"        => "Nephew",
		"niece"         => "Niece",
		"colleague"     => "Colleague",
		"boss"          => "Boss",
		"teacher"       => "Teacher",
	];

	const MOODS = [
		'funny' => 'Funny',
		'sexy'  => 'Sexy',
	];

	const TOPICS = [
		"sun_moon_and_stars" => "Sun, moon and stars",
		"animals"            => "Animals",
		"flowers"            => "Flowers",
		"food"               => "Food",
		"nature"             => "Nature",
		"travel"             => "Travel",
		"music"              => "Music",
		"sports"             => "Sports",
		"star_wars"          => "Star Wars",
		"marvel"             => "Marvel",
		"pokemon"            => "Pokemon",
	];

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return [
			'api_key'                           => '',
			'engine_id'                         => 'stable-diffusion-v1-6',
			'style_preset'                      => '',
			'max_allowed_images_for_guest_user' => 5,
			'max_allowed_images_for_auth_user'  => 20,
			'remove_images_after_days'          => 30,
			'image_width'                       => 1024,
			'image_height'                      => 1024,
			'default_prompt'                    => 'Generate a {{mood}} image for {{occasion}} of my {{recipient}} who likes {{topic}}.',
			'file_naming_method'                => 'uuid',
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
	 * Is module enabled
	 *
	 * @return bool
	 */
	public static function is_module_enabled(): bool {
		return defined( 'STABILITY_AI_ENABLED' ) && STABILITY_AI_ENABLED;
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
	 * @param  mixed  $value  Raw value to be updated.
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
	 * @param  string  $key  The setting key.
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
		return (string) static::get_setting( 'api_key' );
	}

	/**
	 * Get default prompt
	 *
	 * @return string
	 */
	public static function get_default_prompt(): string {
		return (string) static::get_setting( 'default_prompt' );
	}

	/**
	 * Get default prompt
	 *
	 * @return string
	 */
	public static function get_prompt( array $options ): string {
		$prompt = static::get_default_prompt();
		$prompt = str_replace(
			[ '{{occasion}}', '{{recipient}}', '{{mood}}', '{{topic}}' ],
			[
				static::get_label_for( 'occasions', $options['occasion'] ?? '' ),
				static::get_label_for( 'recipients', $options['recipient'] ?? '' ),
				static::get_label_for( 'moods', $options['mood'] ?? '' ),
				static::get_label_for( 'topics', $options['topic'] ?? '' ),
			],
			$prompt
		);

		return trim( $prompt );
	}

	/**
	 * Get file naming method
	 *
	 * @return string
	 */
	public static function get_file_naming_method(): string {
		$naming_method = static::get_setting( 'file_naming_method' );
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
		$width = (int) static::get_setting( 'image_width' );
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
		$height = (int) static::get_setting( 'image_height' );
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

	/**
	 * Get occasions
	 *
	 * @return array
	 */
	public static function get_occasions(): array {
		$occasions = get_option( 'ai_image_generator_occasions' );
		$occasions = is_array( $occasions ) ? $occasions : [];
		if ( empty( $occasions ) ) {
			foreach ( static::OCCASIONS as $slug => $label ) {
				$occasions[] = [ 'slug' => $slug, 'label' => $label, 'menu_order' => 0 ];
			}
		}

		return $occasions;
	}

	/**
	 * Update occasions
	 *
	 * @param  array  $occasions  Array of occasions.
	 *
	 * @return void
	 */
	public static function update_occasions( array $occasions, bool $sanitize = false ) {
		if ( $sanitize ) {
			$occasions = static::sanitize_setting( $occasions );
		}
		update_option( 'ai_image_generator_occasions', $occasions );
	}

	/**
	 * Get occasions
	 *
	 * @return array
	 */
	public static function get_recipients(): array {
		$recipients = get_option( 'ai_image_generator_recipients' );
		$recipients = is_array( $recipients ) ? $recipients : [];
		if ( empty( $recipients ) ) {
			foreach ( static::RECIPIENTS as $slug => $label ) {
				$recipients[] = [ 'slug' => $slug, 'label' => $label, 'menu_order' => 0 ];
			}
		}

		return $recipients;
	}

	/**
	 * Update occasions
	 *
	 * @param  array  $recipients  Array of occasions.
	 *
	 * @return void
	 */
	public static function update_recipients( array $recipients, bool $sanitize = false ) {
		if ( $sanitize ) {
			$recipients = static::sanitize_setting( $recipients );
		}
		update_option( 'ai_image_generator_recipients', $recipients );
	}

	/**
	 * Get moods
	 *
	 * @return array
	 */
	public static function get_moods(): array {
		$moods = get_option( 'ai_image_generator_moods' );
		$moods = is_array( $moods ) ? $moods : [];
		if ( empty( $moods ) ) {
			foreach ( static::MOODS as $slug => $label ) {
				$moods[] = [ 'slug' => $slug, 'label' => $label, 'menu_order' => 0 ];
			}
		}

		return $moods;
	}

	/**
	 * Update occasions
	 *
	 * @param  array  $moods  Array of occasions.
	 *
	 * @return void
	 */
	public static function update_moods( array $moods, bool $sanitize = false ) {
		if ( $sanitize ) {
			$moods = static::sanitize_setting( $moods );
		}
		update_option( 'ai_image_generator_moods', $moods );
	}

	/**
	 * Get topics
	 *
	 * @return array
	 */
	public static function get_topics(): array {
		$topics = get_option( 'ai_image_generator_topics' );
		$topics = is_array( $topics ) ? $topics : [];
		if ( empty( $topics ) ) {
			foreach ( static::TOPICS as $slug => $label ) {
				$topics[] = [ 'slug' => $slug, 'label' => $label, 'menu_order' => 0 ];
			}
		}

		return $topics;
	}

	/**
	 * Update topics
	 *
	 * @param  array  $topics  Array of occasions.
	 *
	 * @return void
	 */
	public static function update_topics( array $topics, bool $sanitize = false ) {
		if ( $sanitize ) {
			$topics = static::sanitize_setting( $topics );
		}
		update_option( 'ai_image_generator_topics', $topics );
	}

	/**
	 * Sanitize setting option
	 *
	 * @param  array|mixed  $raw_values  The value to be sanitized.
	 *
	 * @return array
	 */
	public static function sanitize_setting( $raw_values ): array {
		$sanitized_value = [];
		if ( ! is_array( $raw_values ) ) {
			return $sanitized_value;
		}

		foreach ( $raw_values as $value ) {
			if ( ! isset( $value['slug'], $value['label'] ) ) {
				continue;
			}
			$sanitized_value[] = [
				'slug'       => str_replace( '-', '_', sanitize_title_with_dashes( $value['slug'] ) ),
				'label'      => sanitize_text_field( $value['label'] ),
				'menu_order' => isset( $value['menu_order'] ) ? intval( $value['menu_order'] ) : 0,
			];
		}

		return $sanitized_value;
	}

	public static function get_label_for( string $group, string $slug ) {
		if ( 'occasions' === $group ) {
			$options = static::get_occasions();
		} elseif ( 'recipients' === $group ) {
			$options = static::get_recipients();
		} elseif ( 'moods' === $group ) {
			$options = static::get_moods();
		} elseif ( 'topics' === $group ) {
			$options = static::get_topics();
		}

		$label = $slug;
		if ( isset( $options ) ) {
			foreach ( $options as $option ) {
				if ( $option['slug'] === $slug ) {
					$label = $option['label'];
				}
			}
		}

		return $label;
	}
}
