<?php

namespace YouSaidItCards\Modules\StabilityAi;

use YouSaidItCards\Modules\StabilityAi\Admin\Admin;
use YouSaidItCards\Modules\StabilityAi\Rest\AdminLogController;
use YouSaidItCards\Utils;

/**
 * StabilityAiManager class
 */
class StabilityAiManager {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			if ( Settings::is_module_enabled() ) {
				add_action( 'wp_ajax_stability_ai_api_test', [ self::$instance, 'api_test' ] );
				add_action( 'wp_ajax_yousaidit_ai_image_generator', [ self::$instance, 'image_generator' ] );
				add_action( 'wp_ajax_nopriv_yousaidit_ai_image_generator', [ self::$instance, 'image_generator' ] );
				Admin::init();
				AdminLogController::init();
			}
		}

		return self::$instance;
	}

	/**
	 * API test class
	 *
	 * @return void
	 */
	public function api_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Sorry. This link only for developer to do some testing.',
					'stackonet-news-receiver'
				)
			);
		}
		$image = StabilityAiClient::generate_image( 'birthday', 'wife', 'funny', 'animals' );
		if ( is_wp_error( $image ) ) {
			var_dump( $image );
			wp_die();
		}
		header( 'Content-type: image/png' );
		echo $image;
//		var_dump( $image );
		wp_die();
	}

	public function image_generator() {
		$user_id               = get_current_user_id();
		$date                  = date( 'Y-m-d', time() );
		$auth_user_cache_key   = sprintf( 'stability_ai_used_quota_%s_%s', $user_id, $date );
		$auth_user_used_quota  = (int) get_transient( $auth_user_cache_key );
		$guest_user_cache_key  = sprintf( 'stability_ai_used_quota_%s_%s', Utils::get_remote_ip(), $date );
		$guest_user_used_quota = (int) get_transient( $guest_user_cache_key );

		if ( $user_id ) {
			$auth_user_quota = Settings::get_max_allowed_images_for_auth_user();
			if ( $auth_user_used_quota >= $auth_user_quota ) {
				wp_send_json_error( [ 'message' => 'Max quota reached for today.' ], 400 );
			}
		} else {
			$guest_user_quota = Settings::get_max_allowed_images_for_guest_user();
			if ( $guest_user_used_quota >= $guest_user_quota ) {
				wp_send_json_error( [ 'message' => 'Max quota reached for today.' ], 400 );
			}
		}
		$style_preset = $_REQUEST['style_preset'] ?? '';
		$occasion     = $_REQUEST['occasion'] ?? '';
		$recipient    = $_REQUEST['recipient'] ?? '';
		$mode         = $_REQUEST['mode'] ?? '';
		$topic        = $_REQUEST['topic'] ?? '';
		$custom_topic = $_REQUEST['custom_topic'] ?? '';
		if ( '__custom' === $topic ) {
			$topic = $custom_topic;
		}
		$image_id = StabilityAiClient::generate_image( $occasion, $recipient, $mode, $topic, $style_preset );
		if ( is_wp_error( $image_id ) ) {
			wp_send_json_error( [ 'message' => $image_id->get_error_message() ], 400 );
		}
		if ( is_numeric( $image_id ) ) {
			if ( $user_id ) {
				set_transient( $auth_user_cache_key, $auth_user_used_quota + 1, DAY_IN_SECONDS );
			} else {
				set_transient( $guest_user_cache_key, $guest_user_used_quota + 1, DAY_IN_SECONDS );
			}
			wp_send_json_success( $this->_prepare_item_for_response( $image_id ) );
		}
		wp_send_json_error( [ 'message' => 'Something went wrong.' ], 500 );
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param  int  $attachment_id
	 *
	 * @return array
	 */
	public function _prepare_item_for_response( int $attachment_id ): array {
		$title          = get_the_title( $attachment_id );
		$token          = get_post_meta( $attachment_id, '_delete_token', true );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$image          = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
		$full_image     = wp_get_attachment_image_src( $attachment_id, 'full' );

		$data = [
			'id'             => $attachment_id,
			'title'          => $title,
			'token'          => $token,
			'attachment_url' => $attachment_url,
			'thumbnail'      => [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ],
			'full'           => [ 'src' => '', 'width' => '', 'height' => '', ],
		];
		if ( is_array( $full_image ) ) {
			$data['full'] = [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2], ];
		}

		return $data;
	}
}