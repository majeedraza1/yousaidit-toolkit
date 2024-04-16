<?php

namespace YouSaidItCards\Modules\StabilityAi;

use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

/**
 * StabilityAiClient
 */
class StabilityAiClient extends RestClient {

	/**
	 * Get available engines
	 *
	 * @return string[]
	 */
	public static function get_available_engines(): array {
		return [
			'esrgan-v1-x2plus',
			'stable-diffusion-xl-1024-v0-9',
			'stable-diffusion-xl-1024-v1-0',
			'stable-diffusion-v1-6',
			'stable-diffusion-512-v2-1',
			'stable-diffusion-xl-beta-v2-2-2',
		];
	}

	/**
	 * Get style presets
	 * Pass in a style preset to guide the image model towards a particular style.
	 * This list of style presets is subject to change.
	 *
	 * @return string[]
	 */
	public static function get_style_presets(): array {
		return [
			'3d-model',
			'analog-film',
			'anime',
			'cinematic',
			'comic-book',
			'digital-art',
			'enhance',
			'fantasy-art',
			'isometric',
			'line-art',
			'low-poly',
			'modeling-compound',
			'neon-punk',
			'origami',
			'photographic',
			'pixel-art',
			'tile-texture',
		];
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->add_auth_header( Settings::get_api_key(), 'Bearer' );
		parent::__construct( 'https://api.stability.ai/v1' );
	}

	/**
	 * Get engines list
	 *
	 * @return array
	 */
	public static function get_engines_list(): array {
		$list = get_transient( 'stability_ai_engines_list' );
		if ( ! is_array( $list ) ) {
			$list     = [];
			$response = ( new static() )->get( 'engines/list' );
			if ( is_wp_error( $response ) ) {
				Logger::log( $response->get_error_message() );
			}
			if ( is_array( $response ) ) {
				set_transient( 'stability_ai_engines_list', $response, DAY_IN_SECONDS );
				$list = $response;
			}
		}

		return $list;
	}

	/**
	 * Generate text to image
	 *
	 * @param  string  $prompt  The prompt text.
	 *
	 * @return string|WP_Error
	 */
	public static function generate_text_to_image( string $prompt ) {
		if ( mb_strlen( $prompt ) > 2000 ) {
			return new WP_Error(
				'max_characters_length_exists',
				'Prompts characters length cannot be more than 2000.'
			);
		}
		$self = new static();
		$self->add_headers( 'Content-Type', 'application/json' );
		$self->add_headers( 'Accept', 'application/json' );

		$data = array(
			'width'        => Settings::get_image_width(),
			'height'       => Settings::get_image_height(),
			'text_prompts' => [
				[ 'text' => $prompt ],
			],
		);

		$style_preset = Settings::get_style_preset();
		if ( in_array( $style_preset, static::get_style_presets(), true ) ) {
			$data['style_preset'] = $style_preset;
		}

		$response = $self->post(
			sprintf( 'generation/%s/text-to-image', Settings::get_engine_id() ),
			wp_json_encode( $data )
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! ( isset( $response['artifacts'] ) && is_array( $response['artifacts'] ) ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}
		$image_base64_string = $response['artifacts'][0]['base64'];

		return base64_decode( $image_base64_string );
	}
}
