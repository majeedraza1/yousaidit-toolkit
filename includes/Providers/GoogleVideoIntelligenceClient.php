<?php

namespace YouSaidItCards\Providers;

use Stackonet\WP\Framework\Supports\RestClient;
use YouSaidItCards\Admin\SettingPage;

/**
 * GoogleVideoIntelligenceClient class
 */
class GoogleVideoIntelligenceClient extends RestClient {
	public function __construct() {
		$key = SettingPage::get_option( 'google_video_intelligence_key' );
		if ( ! empty( $key ) ) {
			$this->add_headers( 'x-goog-api-key', $key );
		}
		$project_number = SettingPage::get_option( 'google_video_intelligence_project_id' );
		if ( ! empty( $project_number ) ) {
			$this->add_headers( 'x-goog-user-project', $project_number );
		}
		$this->add_headers( 'Content-Type', 'application/json; charset=utf-8' );
		$this->add_headers( 'Referer', 'https://yousaidit.co.uk' );
		parent::__construct( 'https://videointelligence.googleapis.com/v1' );
	}

	public function safe_search( string $video_url ) {
		$cache_key = 'safe_search_' . md5( $video_url );
		$response  = get_transient( $cache_key );
		if ( is_array( $response ) ) {
			$response['source'] = 'cache';

			return $response;
		}

		$data = [
			"features" => [ "EXPLICIT_CONTENT_DETECTION" ],
			"inputUri" => $video_url,
		];

		$response = $this->post( 'videos:annotate', wp_json_encode( $data ) );
		if ( ! is_wp_error( $response ) ) {
			$response = $response['responses'][0] ?? [];
			set_transient( $cache_key, $response, HOUR_IN_SECONDS );
			$response['source'] = 'api';
		}

		return $response;
	}
}
