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
}
