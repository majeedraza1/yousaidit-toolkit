<?php

namespace YouSaidItCards\Modules\SocialAuth\Providers;

use WP_Error;
use YouSaidItCards\Modules\SocialAuth\Interfaces\ServiceProviderInterface;
use YouSaidItCards\Modules\SocialAuth\Model\UserInfo;

/**
 * GoogleService provider class
 */
class GoogleServiceProvider extends BaseServiceProvider implements ServiceProviderInterface {
	const PROVIDER = 'google.com';

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	protected function __construct() {
		$options = (array) get_option( '_stackonet_toolkit', [] );
		if ( ! empty( $options['google_auth_client_id'] ) ) {
			$this->set_setting( 'clientId', $options['google_auth_client_id'] );
		}
		if ( ! empty( $options['google_auth_client_secret'] ) ) {
			$this->set_setting( 'clientSecret', $options['google_auth_client_secret'] );
		}
	}

	/**
	 * Get api consent uri
	 *
	 * @return string
	 *
	 * @link https://developers.google.com/identity/protocols/oauth2/web-server#httprest_1
	 */
	public static function get_consent_url(): string {
		return add_query_arg(
			[
				// Required parameters
				'client_id'     => ( new static() )->get_client_id(),
				'redirect_uri'  => rawurlencode( static::get_redirect_uri() ),
				'response_type' => 'code',
				'scope'         => rawurlencode( 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile' ),
				// Recommended parameters
				'access_type'   => 'offline',
				'state'         => static::create_nonce(),
				'prompt'        => 'consent',
			],
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	/**
	 * Get authorization code
	 *
	 * @param  string  $code  Code from Google prompt consent.
	 *
	 * @return array|WP_Error
	 *
	 * @link https://developers.google.com/identity/protocols/oauth2/web-server#httprest_3
	 */
	public static function exchange_code_for_token( string $code ) {
		$self     = new static();
		$api_url  = 'https://oauth2.googleapis.com/token';
		$params   = [
			'code'          => $code,
			'client_id'     => $self->get_client_id(),
			'client_secret' => $self->get_client_secret(),
			'redirect_uri'  => rawurlencode( static::get_redirect_uri() ),
			'grant_type'    => 'authorization_code',
		];
		$response = wp_remote_request( $api_url, [
			'method' => 'POST',
			'body'   => $params,
		] );

		return static::filter_remote_response( $api_url, $params, $response );
	}

	/**
	 * Get user Info
	 *
	 * @param  string  $access_token  User access token.
	 *
	 * @return UserInfo|WP_Error
	 */
	public static function get_userinfo( string $access_token ) {
		$api_url = 'https://www.googleapis.com/oauth2/v3/userinfo';

		$response = wp_remote_request(
			$api_url,
			[
				'method'  => 'GET',
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $access_token ),
				],
			]
		);

		$info = static::filter_remote_response( $api_url, [], $response );
		if ( is_array( $info ) && isset( $info['email'] ) ) {
			return new UserInfo( [
				'provider'    => static::PROVIDER,
				'uuid'        => $info['sub'],
				'name'        => $info['name'],
				'email'       => $info['email'],
				'picture_url' => $info['picture'] ?? '',
			] );
		}

		return $info;
	}
}
