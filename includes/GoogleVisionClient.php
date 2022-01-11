<?php

namespace YouSaidItCards;

use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;
use YouSaidItCards\Admin\SettingPage;

class GoogleVisionClient extends RestClient {
	public function __construct() {
		$key = SettingPage::get_option( 'google_api_secret_key' );
		if ( ! empty( $key ) ) {
			$this->set_global_parameter( 'key', $key );
		}
		$this->add_headers( 'Content-Type', 'application/json; charset=utf-8' );
		$this->add_headers( 'Referer', site_url() );
		parent::__construct( 'https://vision.googleapis.com/v1' );
	}

	public function safe_search( string $imageBase64 ) {
		$cache_key = 'safe_search_' . md5( $imageBase64 );
		$response  = get_transient( $cache_key );
		if ( is_array( $response ) ) {
			$response['source'] = 'cache';

			return $response;
		}
		$data = [
			"requests" => [
				[
					"features" => [ [ "type" => "SAFE_SEARCH_DETECTION" ] ],
					"image"    => [ "content" => $imageBase64 ],
				]
			]
		];

		$response = $this->post( 'images:annotate', wp_json_encode( $data ) );
		if ( ! is_wp_error( $response ) ) {
			$response = $response['responses'][0] ?? [];
			set_transient( $cache_key, $response, HOUR_IN_SECONDS );
			$response['source'] = 'api';
		}

		return $response;
	}

	public function is_adult( $data ): bool {
		if ( is_array( $data ) && isset( $data['adult'] ) && is_string( $data['adult'] ) ) {
			$true_enum = [ 'POSSIBLE', 'LIKELY', 'VERY_LIKELY' ];

			return $data['adult'] && in_array( $data['adult'], $true_enum, true );
		}

		return false;
	}

	/**
	 * @param string $image_path
	 *
	 * @return bool|WP_Error
	 */
	public static function is_adult_image( string $image_path ) {
		$self     = new self;
		$content  = base64_encode( file_get_contents( $image_path ) );
		$response = $self->safe_search( $content );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $self->is_adult( $response['safeSearchAnnotation'] ?? [] );
	}
}
