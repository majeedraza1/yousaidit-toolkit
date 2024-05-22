<?php

namespace YouSaidItCards\OpenAI;

use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

/**
 * OpenAI Rest Client
 */
class Client extends RestClient {
	const DEFAULT_MODEL = 'gpt-3.5-turbo';
	const MAX_TOKEN = 4096;

	/**
	 * Class constructor
	 */
	public function __construct() {
		try {
			$setting = Setting::get_settings();
			$this->add_auth_header( $setting['api_key'], 'Bearer' );
			$this->add_headers( 'OpenAI-Organization', $setting['organization'] );
		} catch ( Exception $e ) {
			Logger::log( $e );
		}
		$this->add_headers( 'Content-Type', 'application/json' );
		parent::__construct( 'https://api.openai.com/v1' );
	}

	/**
	 * @param  CardOption  $option
	 * @param  bool  $force
	 * @param  string  $group
	 *
	 * @return string|WP_Error
	 */
	public static function recreate_article( CardOption $option, bool $force = false, string $group = 'unknown' ) {
		$content = $option->get_instruction();
		if ( empty( $content ) ) {
			return new WP_Error( 'no_instruction', 'No instruction to handle it.' );
		}
		$cache_key = 'openai_recreate_article_' . md5( $content );
		$result    = get_transient( $cache_key );
		if ( empty( $result ) || $force ) {
			$api_response = ( new static() )->chat_completions(
				[
					'model'    => static::DEFAULT_MODEL,
					'messages' => [
						[ 'role' => 'user', 'content' => $content ]
					],
				]
			);
			if ( is_wp_error( $api_response ) ) {
				return $api_response;
			}

			$result = '';
			if ( is_array( $api_response ) && isset( $api_response['choices'][0]['message']['content'] ) ) {
				$result = wp_filter_post_kses( trim( $api_response['choices'][0]['message']['content'] ) );
				if ( empty( $result ) ) {
					return new WP_Error( 'empty_response_from_api', 'Empty response from api: ' . $group );
				}
				set_transient( $cache_key, $result, DAY_IN_SECONDS );
			}
		}

		return $result;
	}

	/**
	 * Use completions api
	 *
	 * @param  array  $args  Arguments.
	 *
	 * @return array|WP_Error
	 */
	public function chat_completions( array $args ) {
		if ( ! isset( $args['model'] ) ) {
			return new WP_Error( 'missing_required_argument_model', 'Required argument model missing.' );
		}
		if ( ! isset( $args['messages'] ) ) {
			return new WP_Error( 'missing_required_argument_messages', 'Required argument messages missing.' );
		}

//		$str_to_token = ceil( str_word_count( $args['prompt'] ) * 1.3 );
//		if ( $str_to_token > static::MAX_TOKEN ) {
//			return new WP_Error( 'exceeded_max_token', 'It is going to exceed max token.' );
//		}
//
//		$args['max_tokens'] = min( $str_to_token, static::MAX_TOKEN );

		return $this->post( 'chat/completions', wp_json_encode( $args ) );
	}
}
