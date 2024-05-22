<?php

namespace YouSaidItCards\Modules\StabilityAi;

use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

/**
 * StabilityAiClient
 * @link https://platform.stability.ai/docs/api-reference
 */
class StabilityAiClient extends RestClient {

	public static function get_api_versions(): array {
		return [
			[
				'id'          => 'stable-diffusion-v1-6',
				'name'        => 'Stable Diffusion v1.6',
				'description' => '1536 pixels image will be generated.',
			],
			[
				'id'          => 'stable-diffusion-v2',
				'name'        => 'Stable Image Core',
				'description' => '1536 pixels image will be generated. 3 credits per successful generation.'
			],
			[
				'id'          => 'sd3',
				'name'        => 'Stable Diffusion 3.0 (SD3)',
				'description' => '1024 pixels image will be generated. 6.5 credits per successful generation.'
			],
			[
				'id'          => 'sd3-turbo',
				'name'        => 'Stable Diffusion 3.0 Turbo (SD3 Turbo)',
				'description' => '1024 pixels image will be generated. 4 credits per successful generation.'
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

		return $list;
	}

	/**
	 * Generate text to image
	 *
	 * @param  string  $prompt  The prompt text.
	 *
	 * @return string|WP_Error
	 */
	protected static function generate_text_to_image( string $prompt, string $style_preset = '' ) {
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

		if ( in_array( $style_preset, static::get_style_presets_slug(), true ) ) {
			$data['style_preset'] = $style_preset;
		}

		$response = $self->post(
			'v1/generation/stable-diffusion-v1-6/text-to-image',
			wp_json_encode( $data )
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! ( isset( $response['artifacts'] ) && is_array( $response['artifacts'] ) ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}
		$image_base64_string = $response['artifacts'][0]['base64'];

		$filename     = md5( $prompt ) . '-ai-image.webp';
		$image_string = base64_decode( $image_base64_string );
		$image_id     = static::create_image_from_string( $image_string, $filename );
		if ( ! is_numeric( $image_id ) ) {
			return new WP_Error( 'unexpected_internal_server_error', 'Fail to save image string.' );
		}

		return $image_id;
	}

	protected static function generate_stable_image_core( string $prompt, string $style_preset = '' ) {
		if ( mb_strlen( $prompt ) > 2000 ) {
			return new WP_Error(
				'max_characters_length_exists',
				'Prompts characters length cannot be more than 2000.'
			);
		}
		$filename = md5( $prompt ) . '-ai-image.webp';
		$boundary = wp_generate_password( 24, false, false );

		$self = new static();
		$self->add_headers( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		$self->add_headers( 'Accept', 'application/json' );

		$data = [
			'prompt'        => $prompt,
			'aspect_ratio'  => '1:1',
			'output_format' => 'webp'
		];
		if ( in_array( $style_preset, static::get_style_presets_slug(), true ) ) {
			$data['style_preset'] = $style_preset;
		}

		$response = $self->post( 'v2beta/stable-image/generate/core', static::form_data_payload( $data, $boundary ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! ( isset( $response['image'] ) && is_string( $response['image'] ) ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}

		$image_string = base64_decode( $response['image'] );
		$image_id     = static::create_image_from_string( $image_string, $filename );
		if ( ! is_numeric( $image_id ) ) {
			return new WP_Error( 'unexpected_internal_server_error', 'Fail to save image string.' );
		}

		return $image_id;
	}

	protected static function generate_stable_diffusion_image( string $prompt, string $model = 'sd3' ) {
		$model = in_array( $model, [ 'sd3', 'sd3-turbo' ], true ) ? $model : '';

		$data     = [
			'prompt'        => $prompt,
			'model'         => $model,
			'aspect_ratio'  => '1:1',
			'output_format' => 'png',
			'mode'          => 'text-to-image',
		];
		$filename = md5( $prompt ) . '-ai-image.webp';
		$boundary = wp_generate_password( 24, false, false );

		$self = new static();
		$self->add_headers( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		$self->add_headers( 'Accept', 'application/json' );

		$response = $self->post( 'v2beta/stable-image/generate/core', static::form_data_payload( $data, $boundary ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! ( isset( $response['image'] ) && is_string( $response['image'] ) ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}

		$image_string = base64_decode( $response['image'] );
		$image_id     = static::create_image_from_string( $image_string, $filename );
		if ( ! is_numeric( $image_id ) ) {
			return new WP_Error( 'unexpected_internal_server_error', 'Fail to save image string.' );
		}

		return $image_id;
	}

	public static function generate_image(
		string $occasion,
		string $recipient,
		string $mode,
		string $topic,
		string $style_preset = 'photographic'
	) {
		$prompt = Settings::get_prompt( [
			'occasion'     => $occasion,
			'recipient'    => $recipient,
			'mode'         => $mode,
			'topic'        => $topic,
			'style_preset' => $style_preset,
		] );

		$version = Settings::get_api_version();
		if ( in_array( $version, [ 'sd3', 'sd3-turbo' ], true ) ) {
			$preset = Settings::get_label_for( 'style_presets', $style_preset );
			$prompt .= sprintf( ' And the image style should be %s.', $preset );

			return static::generate_stable_diffusion_image( $prompt, $version );
		}
		if ( 'stable-diffusion-v2' === $version ) {
			return static::generate_stable_image_core( $prompt, $style_preset );
		}

		return static::generate_text_to_image( $prompt, $style_preset );
	}

	/**
	 * @param  array  $data
	 * @param  string  $boundary
	 *
	 * @return string
	 */
	protected static function form_data_payload( array $data, string $boundary ): string {
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

		return $payload;
	}

	/**
	 * @param  string  $image_string
	 * @param  string|null  $filename
	 *
	 * @return false|int|\WP_Error
	 */
	public static function create_image_from_string( string $image_string, ?string $filename = null ) {
		if ( empty( $filename ) ) {
			$filename = wp_generate_uuid4() . '.webp';
		}
		$directory     = rtrim( Uploader::get_upload_dir(), DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		try {
			$imagick = new \Imagick();
			$imagick->readImageBlob( $image_string );
			$imagick->setImageFormat( 'webp' );
			$imagick->setImageCompressionQuality( 83 );
			$imagick->writeImage( $new_file_path );

			$imagick->destroy();

			// Set correct file permissions.
			$stat  = stat( dirname( $new_file_path ) );
			$perms = $stat['mode'] & 0000666;
			chmod( $new_file_path, $perms );
		} catch ( \ImagickException $e ) {
			Logger::log( $e->getMessage() );

			return false;
		}

		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $new_file_path ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => 'image/webp',
			'post_author'    => get_current_user_id(),
		];

		$attachment_id = wp_insert_attachment( $data, $new_file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_read_video_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/media.php';
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );

			update_post_meta( $attachment_id, '_create_via', 'stability.ai' );
			update_post_meta( $attachment_id, '_should_delete_after_time', time() + MONTH_IN_SECONDS );

			return $attachment_id;
		}

		return false;
	}
}
