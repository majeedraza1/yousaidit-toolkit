<?php

namespace YouSaidItCards\Modules\StabilityAi;

use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

/**
 * StabilityAiClient
 * @link https://platform.stability.ai/docs/api-reference
 */
class StabilityAiClient extends RestClient {

	/**
	 * Get available engines
	 *
	 * @return string[]
	 */
	public static function get_available_engines(): array {
		return [
			[
				'id'          => 'esrgan-v1-x2plus',
				'name'        => 'Real-ESRGAN x2',
				'description' => 'Real-ESRGAN_x2plus upscaler model',
			],
			[
				'id'          => 'stable-diffusion-xl-1024-v0-9',
				'name'        => 'Stable Diffusion XL v0.9',
				'description' => 'Stability-AI Stable Diffusion XL v0.9',
			],
			[
				'id'          => 'stable-diffusion-xl-1024-v1-0',
				'name'        => 'Stable Diffusion XL v1.0',
				'description' => 'Stability-AI Stable Diffusion XL v1.0',
			],
			[
				'id'          => 'stable-diffusion-v1-6',
				'name'        => 'Stable Diffusion v1.6',
				'description' => 'Stability-AI Stable Diffusion v1.6',
			],
			[
				'id'          => 'stable-diffusion-512-v2-1',
				'name'        => 'Stable Diffusion v2.1',
				'description' => 'Stability-AI Stable Diffusion v2.1',
			],
			[
				'id'          => 'stable-diffusion-xl-beta-v2-2-2',
				'name'        => 'Stable Diffusion v2.2.2-XL Beta',
				'description' => 'Stability-AI Stable Diffusion XL Beta v2.2.2',
			],
		];
	}

	/**
	 * Get style presets
	 * Pass in a style preset to guide the image model towards a particular style.
	 * This list of style presets is subject to change.
	 *
	 * @return string[]
	 */
	public static function get_style_presets_slug(): array {
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
		];
	}

	public static function get_images_sizes(): array {
		return [
			'stable-diffusion-xl-1024-v1-0'   => [
				'enum' => [
					[ 'width' => 1024, 'height' => 1024 ],
					[ 'width' => 1152, 'height' => 896 ],
					[ 'width' => 896, 'height' => 1152 ],
					[ 'width' => 1216, 'height' => 832 ],
					[ 'width' => 1344, 'height' => 768 ],
					[ 'width' => 768, 'height' => 1344 ],
					[ 'width' => 1536, 'height' => 640 ],
					[ 'width' => 640, 'height' => 1536 ],
				]
			],
			'stable-diffusion-v1-6'           => [
				'min' => 320,
				'max' => 1536,
			],
			'stable-diffusion-xl-beta-v2-2-2' => [
				'min' => 128,
				'max' => 896,
			]
		];
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->add_auth_header( Settings::get_api_key(), 'Bearer' );
		parent::__construct( 'https://api.stability.ai' );
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
			$response = ( new static() )->get( 'v1/engines/list' );
			if ( is_wp_error( $response ) ) {
				Logger::log( $response->get_error_message() );
			}
			if ( is_array( $response ) ) {
				set_transient( 'stability_ai_engines_list', $response, DAY_IN_SECONDS );
				$list = $response;
			}
		}

		if ( empty( $list ) ) {
			$list = static::get_available_engines();
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
		if ( in_array( $style_preset, static::get_style_presets_slug(), true ) ) {
			$data['style_preset'] = $style_preset;
		}

		$response = $self->post(
			sprintf( 'v1/generation/%s/text-to-image', Settings::get_engine_id() ),
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

	public static function generate_stable_image_core(
		string $prompt,
		bool $save = true,
		string $return_type = 'image_string'
	) {
		if ( mb_strlen( $prompt ) > 2000 ) {
			return new WP_Error(
				'max_characters_length_exists',
				'Prompts characters length cannot be more than 2000.'
			);
		}
		$return_type = 'image_id' === $return_type ? 'image_id' : 'image_string';
		$filename    = md5( $prompt ) . '-ai-image.webp';
		$boundary    = wp_generate_password( 24, false, false );

		$self = new static();
		$self->add_headers( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		$self->add_headers( 'Accept', 'application/json' );

		$data         = [
			'prompt'        => $prompt,
			'aspect_ratio'  => '1:1',
			'output_format' => 'webp'
		];
		$style_preset = Settings::get_style_preset();
		if ( in_array( $style_preset, static::get_style_presets_slug(), true ) ) {
			$data['style_preset'] = $style_preset;
		}

		$payload = '';
		// First, add the standard POST fields:
		foreach ( $data as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= PHP_EOL;
			$payload .= 'Content-Disposition: form-data; name="' . $name . '"';
			$payload .= PHP_EOL . PHP_EOL;
			$payload .= $value;
			$payload .= PHP_EOL;
		}
		$payload .= '--' . $boundary . '--';

		$response = $self->post( 'v2beta/stable-image/generate/core', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! ( isset( $response['image'] ) && is_string( $response['image'] ) ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}

		$image_string = base64_decode( $response['image'] );
		if ( $save ) {
			$image_id = BackgroundGenerateThumbnail::create_image_from_string( $image_string, $filename );
			if ( is_numeric( $image_id ) && 'image_id' === $return_type ) {
				return $image_id;
			}
		}

		return $image_string;
	}

	public static function generate_image( string $occasion, string $recipient, string $mode, string $topic ) {
		$prompt = Settings::get_prompt( [
			'occasion'  => $occasion,
			'recipient' => $recipient,
			'mode'      => $mode,
			'topic'     => $topic,
		] );

		return static::generate_stable_image_core( $prompt, true, 'image_id' );
	}
}
