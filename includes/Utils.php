<?php

namespace YouSaidItCards;

use Stackonet\WP\Framework\Supports\Validate;
use YouSaidItCards\Providers\AWSElementalMediaConvert;

class Utils {
	/**
	 * What type of request is this?
	 *
	 * @param  string  $type  admin, ajax, rest, cron or frontend.
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
	 * @param  int  $length  String length.
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
	 * @param  float  $millimeters  Centimeter to calculate.
	 * @param  int  $dpi  dots/pixels per inch.
	 *
	 * @return int
	 */
	public static function millimeter_to_pixels( float $millimeters, int $dpi = 300 ): int {
		$dpi = max( 72, min( 300, $dpi ) );

		// 1 inch is equal to 25.4 millimeters.
		return ceil( $millimeters * ( $dpi / 25.4 ) );
	}


	/**
	 * Prepares the item for the REST response.
	 *
	 * @param  int  $image_id  Media image id.
	 *
	 * @return array
	 */
	public static function prepare_media_item_for_response( int $image_id ): array {
		$title          = get_the_title( $image_id );
		$token          = get_post_meta( $image_id, '_delete_token', true );
		$attachment_url = wp_get_attachment_url( $image_id );

		$is_image = wp_attachment_is_image( $image_id );

		$response = [
			'id'             => $image_id,
			'title'          => $title,
			'attachment_url' => $attachment_url,
			'token'          => $token,
			'thumbnail'      => new \ArrayObject(),
			'full'           => new \ArrayObject(),
		];

		if ( $is_image ) {
			$image      = wp_get_attachment_image_src( $image_id, 'thumbnail' );
			$full_image = wp_get_attachment_image_src( $image_id, 'full' );

			$response['thumbnail'] = [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ];

			$response['full'] = [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2] ];
		}

		return $response;
	}

	/**
	 * Get video message url
	 *
	 * @param  int|string  $video_id  Video id or AWS MediaConvert job id.
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

	/**
	 * Sanitize inner message text
	 *
	 * @param  mixed|string  $message
	 *
	 * @return mixed|string
	 */
	public static function sanitize_inner_message_text( $message ) {
		if ( ! empty( $message ) ) {
			// Remove all HTML attributes from all tags while keeping the tag names
			$message = preg_replace( '/<([^\s>]+)([^>]*)>/si', '<$1>', $message );
			// Remove all tags except div, p, br
			$message = strip_tags( $message, '<div><p><br>' );
			// Replace p tag with div tag
			$message = str_replace( '<p>', '<div>', $message );
			$message = str_replace( '</p>', '</div>', $message );
			// Fix br issue
			// $message = str_replace( '<div><br></div>', '<br>', $message );

			// Add div tag when there is no tag
			// $message = static::add_div_tag_when_there_is_no_tag( $message );
		}

		return $message;
	}

	public static function add_div_tag_when_there_is_no_tag( string $message ): string {
		$message = str_replace( '<div>', PHP_EOL . '<div>', $message );
		$message = str_replace( '</div>', '</div>' . PHP_EOL, $message );
		$lines   = [];
		foreach ( explode( PHP_EOL, $message ) as $message ) {
			if ( empty( $message ) ) {
				$lines[] = "";
			} elseif ( strip_tags( trim( $message ) ) == trim( $message ) ) {
				$lines[] = '<div>' . $message . '</div>';
			} else {
				if ( false !== strpos( $message, '<br>' ) ) {
					if ( false === strpos( $message, '<div><br></div>' ) ) {
						$lines[] = str_replace( '<br>', '', $message );
						$lines[] = '<div><br></div>';
						continue;
					}
				}
				$lines[] = $message;
			}
		}
		$message = implode( PHP_EOL, $lines );

		return trim( $message );
	}

	public static function build_ajax_url( array $args ): string {
		return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	}

	public static function get_formatted_meta_for_right_text_message( \WC_Order_Item_Product $order_item ): array {
		$data           = $order_item->get_meta( '_inner_message', true );
		$formatted_meta = [];
		if ( ! ( is_array( $data ) && ! empty( $data['content'] ) ) ) {
			return $formatted_meta;
		}

		$message = Utils::sanitize_inner_message_text( $data['content'] );

		if ( is_admin() ) {
			$args = [
				'action'   => 'yousaidit_single_im_card',
				'order_id' => $order_item->get_order_id(),
				'item_id'  => $order_item->get_id(),
				'mode'     => 'pdf'
			];

			$url1    = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
			$display = "<a class='button' target='_blank' href='" . esc_url( $url1 ) . "'>View Inner Message PDF</a>";

			if ( current_user_can( 'manage_options' ) ) {
				$args['meta_key'] = '_inner_message';
				$args['im']       = rawurlencode( wp_json_encode( $data ) );
				$url2             = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );

				$display .= " <a class='button edit-im edit-im-right' href='" . esc_url( $url2 ) . "'>Edit</a>";
			}

			$message = '<div>' . $message . '</div>' . $display;
		}

		$formatted_meta[] = (object) array(
			'display_key'   => 'Inner Message (Right)',
			'display_value' => $message,
		);

		return $formatted_meta;
	}

	public static function get_formatted_meta_for_video_message( \WC_Order_Item_Product $order_item ): array {
		$meta           = $order_item->get_meta( '_video_inner_message', true );
		$formatted_meta = [];
		if ( ! $meta ) {
			return $formatted_meta;
		}
		$is_text = 'text' === $meta['type'] && ! empty( $meta['content'] );
		if ( $is_text ) {
			$message = Utils::sanitize_inner_message_text( $meta['content'] );

			if ( is_admin() ) {
				$args = [
					'action'   => 'yousaidit_single_im_card',
					'order_id' => $order_item->get_order_id(),
					'item_id'  => $order_item->get_id(),
					'mode'     => 'pdf'
				];

				$url1    = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
				$display = "<a target='_blank' class='button' href='" . esc_url( $url1 ) . "'>View Inner Message PDF</a>";

				if ( current_user_can( 'manage_options' ) ) {
					$args['meta_key'] = '_video_inner_message';
					$args['im']       = rawurlencode( wp_json_encode( $meta ) );
					$url2             = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
					$display          .= " <a class='button edit-im' href='" . esc_url( $url2 ) . "'>Edit</a>";
				}

				$message = '<div>' . $message . '</div>' . $display;
			}

			$formatted_meta[] = (object) array(
				'display_key'   => 'Inner Message (Left)',
				'display_value' => $message,
			);

			return $formatted_meta;
		}
		$video_url = Utils::get_video_message_url( $meta['video_id'] );
		if ( false === $video_url ) {
			if ( is_admin() && ! empty( $meta['video_id'] ) ) {
				$copy_to_server_url = static::build_ajax_url( [
					'action'   => 'video_message_copy_to_server',
					'order_id' => $order_item->get_order_id(),
					'item_id'  => $order_item->get_id(),
					'job_id'   => $meta['video_id']
				] );
				$formatted_meta[]   = (object) [
					'display_key'   => 'Inner Message (Left)',
					'display_value' => "<a target='_blank' href='" . esc_url( $copy_to_server_url ) . "'>Sync</a>",
				];
			}

			return $formatted_meta;
		}

		if ( is_admin() ) {
			$im_pdf_url = static::build_ajax_url( [
				'order_id' => $order_item->get_order_id(),
				'item_id'  => $order_item->get_id(),
				'mode'     => 'pdf',
				'action'   => 'yousaidit_single_im_card'
			] );

			$qr_code_url      = static::build_ajax_url( [
				'action'   => 'video_message_qr_code',
				'order_id' => $order_item->get_order_id(),
				'item_id'  => $order_item->get_id()
			] );
			$formatted_meta[] = (object) [
				'display_key'   => 'Inner Message (Left)',
				'display_value' => "<a target='_blank' href='" . esc_url( $video_url ) . "'>View Video</a>" .
				                   " | <a target='_blank' href='" . esc_url( $qr_code_url ) . "'>QR Code</a>" .
				                   " | <a target='_blank' href='" . esc_url( $im_pdf_url ) . "'>View Inner Message PDF</a>",
			];
		} else {
			$formatted_meta[] = (object) [
				'display_key'   => 'Inner Message (Left)',
				'display_value' => "<a target='_blank' href='" . esc_url( $video_url ) . "'>View</a>",
			];
		}

		return $formatted_meta;
	}
}
