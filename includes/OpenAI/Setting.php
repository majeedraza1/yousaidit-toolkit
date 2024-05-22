<?php

namespace YouSaidItCards\OpenAI;

use Exception;

/**
 * Setting class
 */
class Setting {

	/**
	 * Get defaults settings
	 *
	 * @return array
	 */
	private static function get_defaults(): array {
		return [
			'api_key'      => '',
			'organization' => '',
		];
	}

	/**
	 * Get settings
	 *
	 * @return array {
	 * Return OpenAI api settings.
	 *
	 * @type string $api_key OpenAI api key.
	 * @type string $organization OpenAI organization.
	 * }
	 * @throws Exception
	 */
	public static function get_settings(): array {
		if ( ! defined( 'OPENAI_API_SETTINGS' ) ) {
			throw new Exception( 'OpenAI api setting is not available.' );
		}

		return unserialize( OPENAI_API_SETTINGS );
	}

	/**
	 * Get occasions
	 *
	 * @return array
	 */
	public static function get_occasions(): array {
		$occasions = get_option( 'openai_content_writer_occasions' );
		$occasions = is_array( $occasions ) ? $occasions : [];
		if ( empty( $occasions ) ) {
			foreach ( CardOption::OCCASIONS as $slug => $label ) {
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
		update_option( 'openai_content_writer_occasions', $occasions );
	}

	/**
	 * Get occasions
	 *
	 * @return array
	 */
	public static function get_recipients(): array {
		$recipients = get_option( 'openai_content_writer_recipients' );
		$recipients = is_array( $recipients ) ? $recipients : [];
		if ( empty( $recipients ) ) {
			foreach ( CardOption::RECIPIENTS as $slug => $label ) {
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
		update_option( 'openai_content_writer_recipients', $recipients );
	}

	/**
	 * Get topics
	 *
	 * @return array
	 */
	public static function get_topics(): array {
		$topics = get_option( 'openai_content_writer_topics' );
		$topics = is_array( $topics ) ? $topics : [];
		if ( empty( $topics ) ) {
			foreach ( CardOption::TOPICS as $slug => $label ) {
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
		update_option( 'openai_content_writer_topics', $topics );
	}

	public static function get_label_for( string $group, string $slug ) {
		if ( 'occasions' === $group ) {
			$options = static::get_occasions();
		} elseif ( 'recipients' === $group ) {
			$options = static::get_recipients();
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
}
