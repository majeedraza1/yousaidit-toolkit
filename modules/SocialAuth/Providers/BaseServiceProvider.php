<?php

namespace YouSaidItCards\Modules\SocialAuth\Providers;

use WP_Error;

class BaseServiceProvider {
	const PROVIDER = 'example.com';

	/**
	 * Service Provider Settings
	 *
	 * @var array
	 */
	protected array $settings = [
		'clientId'     => '',
		'clientSecret' => '',
		'redirectUri'  => '',
	];

	/**
	 * Get Settings
	 *
	 * @param  string  $key  Setting key.
	 * @param  mixed  $default  The default value.
	 *
	 * @return false|mixed
	 */
	public function get_setting( string $key, $default = false ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}

		return $default;
	}

	/**
	 * Set setting
	 *
	 * @param  string  $key  Setting key.
	 * @param  mixed  $value  Setting value.
	 *
	 * @return void
	 */
	public function set_setting( string $key, $value ): void {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Get client id
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		return (string) $this->get_setting( 'clientId' );
	}

	/**
	 * Get client secret
	 *
	 * @return string
	 */
	public function get_client_secret(): string {
		return (string) $this->get_setting( 'clientSecret' );
	}

	/**
	 * If provider has required settings
	 *
	 * @return bool
	 */
	public function has_required_settings(): bool {
		return ! ! ( $this->get_client_id() && $this->get_client_secret() );
	}

	/**
	 * Get redirect URI
	 *
	 * @return string
	 */
	public static function get_redirect_uri(): string {
		return static::get_default_redirect_uri();
	}

	/**
	 * Get all available direct uris
	 *
	 * @return string[]
	 */
	public static function get_redirect_uris(): array {
		$urls = [ static::get_redirect_uri() ];
		if ( function_exists( 'wc_get_checkout_url' ) ) {
			$urls[] = static::build_redirect_url( wc_get_checkout_url() );
			$urls[] = static::build_redirect_url( wc_get_account_endpoint_url( 'dashboard' ) );
		}

		return $urls;
	}

	/**
	 * Get default redirect uri
	 *
	 * @return string
	 */
	public static function get_default_redirect_uri(): string {
		return static::build_redirect_url( site_url( 'wp-login.php', 'login' ) );
	}

	/**
	 * Build redirect url
	 *
	 * @param  string  $base_uri  Base URI.
	 * @param  array  $arguments  Additional arguments.
	 *
	 * @return string
	 */
	public static function build_redirect_url( string $base_uri, array $arguments = [] ): string {
		$arguments['action']   = 'social-login';
		$arguments['provider'] = static::PROVIDER;

		return add_query_arg( $arguments, $base_uri );
	}

	/**
	 * Filter remote response
	 *
	 * @param  string  $url  The request URL.
	 * @param  array  $args  The request arguments.
	 * @param  array|WP_Error  $response  The remote response or WP_Error object.
	 *
	 * @return array|WP_Error
	 */
	protected static function filter_remote_response( string $url, array $args, $response ) {
		if ( is_wp_error( $response ) ) {
			$response->add_data( $url, 'debug_request_url' );
			$response->add_data( $args, 'debug_request_args' );

			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'Content-Type' );
		if ( false !== strpos( $content_type, 'application/json' ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		} elseif ( false !== strpos( $content_type, 'text/html' ) ) {
			$response_body = (array) wp_remote_retrieve_body( $response );
		} else {
			$response_body = 'Unsupported content type: ' . $content_type;
		}

		if ( ! ( $response_code >= 200 && $response_code < 300 ) ) {
			$response_message = wp_remote_retrieve_response_message( $response );

			$response = new WP_Error( 'rest_error', $response_message, $response_body );
			$response->add_data( $url, 'debug_request_url' );
			$response->add_data( $args, 'debug_request_args' );

			return $response;
		}

		if ( ! is_array( $response_body ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}

		return $response_body;
	}

	/**
	 * Get nonce action name
	 *
	 * @return string
	 */
	public static function get_nonce_action(): string {
		return static::PROVIDER . '_oauth_nonce';
	}

	/**
	 * Create nonce
	 *
	 * @return string
	 */
	public static function create_nonce(): string {
		return wp_create_nonce( static::get_nonce_action() );
	}

	/**
	 * Validate nonce value
	 *
	 * @param  string|null  $value  The nonce value.
	 *
	 * @return bool
	 */
	public static function validate_nonce( ?string $value = '' ): bool {
		if ( empty( $value ) ) {
			$value = $_GET['state'] ?? '';
		}

		return wp_verify_nonce( $value, static::get_nonce_action() ) === 1;
	}
}
