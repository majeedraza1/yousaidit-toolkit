<?php

namespace YouSaidItCards\Modules\SocialAuth\Providers;

use YouSaidItCards\Modules\SocialAuth\Interfaces\ServiceProviderInterface;
use YouSaidItCards\Modules\SocialAuth\Model\UserInfo;

/**
 * FacebookServiceProvider class
 *
 * @link https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
 */
class FacebookServiceProvider extends BaseServiceProvider implements ServiceProviderInterface {
	const PROVIDER = 'facebook.com';

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
		if ( ! empty( $options['facebook_app_id'] ) ) {
			$this->set_setting( 'clientId', $options['facebook_app_id'] );
		}
		if ( ! empty( $options['facebook_auth_client_secret'] ) ) {
			$this->set_setting( 'clientSecret', $options['facebook_auth_client_secret'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function get_consent_url(): string {
		$params = [
			'client_id'     => ( new static() )->get_client_id(),
			'redirect_uri'  => rawurlencode( static::get_redirect_uri() ),
			'state'         => static::create_nonce(),
			'response_type' => 'code',
			'scope'         => 'email'
		];

		return add_query_arg( $params, 'https://www.facebook.com/v20.0/dialog/oauth' );
	}

	/**
	 * @inheritDoc
	 */
	public static function exchange_code_for_token( string $code ) {
		$self     = new static();
		$params   = [
			'client_id'     => $self->get_client_id(),
			'client_secret' => $self->get_client_secret(),
			'redirect_uri'  => static::get_redirect_uri(),
			'code'          => $code
		];
		$api_url  = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query( $params );
		$response = wp_remote_get( $api_url );

		return static::filter_remote_response( $api_url, $params, $response );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_userinfo( string $access_token ) {
		$api_url  = 'https://graph.facebook.com/v20.0/me?fields=name,email,picture';
		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
			]
		] );

		$info = static::filter_remote_response( $api_url, [], $response );
		if ( is_array( $info ) && isset( $info['email'] ) ) {
			return new UserInfo( [
				'provider'    => static::PROVIDER,
				'uuid'        => $info['id'],
				'name'        => $info['name'],
				'email'       => $info['email'],
				'picture_url' => $info['picture']['data']['url'] ?? '',
			] );
		}

		return $info;
	}
}