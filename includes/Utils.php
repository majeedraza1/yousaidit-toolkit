<?php

namespace YouSaidItCards;

use Stackonet\WP\Framework\Supports\Validate;

class Utils {
	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, rest, cron or frontend.
	 *
	 * @return bool
	 */
	public static function is_request( string $type ): bool {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'rest' :
				return defined( 'REST_REQUEST' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}

		return false;
	}

	/**
	 * Generate 36 character length uuid
	 *
	 * @return string
	 * @link https://github.com/symfony/polyfill-uuid
	 */
	public static function generate_uuid(): string {
		try {
			$uuid = bin2hex( random_bytes( 16 ) );

			return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( $uuid, 4 ) );
		} catch ( \Exception $e ) {
			return wp_generate_uuid4();
		}
	}

	/**
	 * Generate random string
	 *
	 * @param int $length String length.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function str_rand( int $length = 64 ): string {
		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

		$password = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $password;
	}

	/**
	 * Get video message url
	 *
	 * @param int|string $video_id Video id or AWS MediaConvert job id.
	 *
	 * @return false|string
	 */
	public static function get_video_message_url( $video_id ) {
		if ( is_string( $video_id ) ) {
			$_video_id = AWSElementalMediaConvert::job_id_to_video_id( $video_id );
			if ( $_video_id ) {
				$video_id = $_video_id;
			}
		}
		$url  = wp_get_attachment_url( $video_id );
		$meta = get_post_meta( $video_id, '_video_message_filename', true );
		if ( strlen( $meta ) === 64 ) {
			return site_url( sprintf( '/video-message/%s', $meta ) );
		} elseif ( Validate::url( $url ) ) {
			return $url;
		}

		return false;
	}
}
