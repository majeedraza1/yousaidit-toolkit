<?php

namespace YouSaidItCards\Modules\SocialAuth\Providers;

use YouSaidItCards\Modules\SocialAuth\Interfaces\ServiceProviderInterface;

/**
 * AppleServiceProvider class
 *
 * @link https://developer.apple.com/documentation/sign_in_with_apple/generate_and_validate_tokens
 */
class AppleServiceProvider extends BaseServiceProvider implements ServiceProviderInterface {
	const PROVIDER = 'apple.com';

	/**
	 * @inheritDoc
	 */
	public static function get_consent_url(): string {
		$params = [
			'client_id'     => ( new static() )->get_client_id(),
			'redirect_uri'  => rawurlencode( static::get_redirect_uri() ),
			'state'         => static::create_nonce(),
			'response_type' => 'code',
			'response_mode' => 'form_post',
			'scope'         => 'name email'
		];

		return add_query_arg( $params, 'https://appleid.apple.com/auth/authorize' );
	}

	/**
	 * @inheritDoc
	 */
	public static function exchange_code_for_token( string $code ) {
		$self   = new static();
		$params = [
			'client_id'     => $self->get_client_id(),
			'client_secret' => $self->get_client_secret(),
			'redirect_uri'  => rawurlencode( static::get_redirect_uri() ),
			'code'          => $code,
			'grant_type'    => 'authorization_code',
		];

		$api_url  = 'https://appleid.apple.com/auth/token';
		$response = wp_remote_post( $api_url, [ 'body' => $params ] );

		return static::filter_remote_response( $api_url, $params, $response );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_userinfo( string $access_token ) {
		// TODO: Implement get_userinfo() method.
	}
}
