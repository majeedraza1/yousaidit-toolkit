<?php

namespace YouSaidItCards\Modules\SocialAuth\Interfaces;

use WP_Error;

interface ServiceProviderInterface {
	/**
	 * If provider has required settings
	 *
	 * @return bool
	 */
	public static function has_required_settings(): bool;

	/**
	 * Get default redirect uri
	 *
	 * @return string
	 */
	public static function get_redirect_uri(): string;

	/**
	 * Get all redirect uris
	 *
	 * @return array
	 */
	public static function get_redirect_uris(): array;

	/**
	 * Get consent url
	 *
	 * @return string
	 */
	public static function get_consent_url(): string;

	/**
	 * Exchange consent code with access token.
	 *
	 * @param  string  $code  The consent code.
	 *
	 * @return array|WP_Error
	 */
	public static function exchange_code_for_token( string $code );

	/**
	 * Get user Info
	 *
	 * @param  string  $access_token  User access token.
	 *
	 * @return array|WP_Error
	 */
	public static function get_userinfo( string $access_token );
}