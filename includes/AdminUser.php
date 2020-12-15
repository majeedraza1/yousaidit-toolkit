<?php

namespace YouSaidItCards;

use YouSaidItCards\Modules\Auth\Auth;

class AdminUser {
	public static function get_admin_user() {
		$users = get_users( [
			'role' => 'administrator',
		] );
		if ( is_array( $users ) && $users[0] instanceof \WP_User ) {
			return $users[0];
		}

		return false;
	}

	/**
	 * Get admin auth token
	 *
	 * @return string
	 */
	public static function get_admin_auth_token() {
		$token = get_transient( '__jwt_auth_token' );
		if ( false === $token ) {
			$user = static::get_admin_user();
			if ( $user instanceof \WP_User ) {
				$token = Auth::get_token_for_user( $user, 1 );
				if ( is_string( $token ) ) {
					set_transient( '__jwt_auth_token', $token, ( WEEK_IN_SECONDS * 1 ) );
				}
			}
		}

		return $token;
	}
}
