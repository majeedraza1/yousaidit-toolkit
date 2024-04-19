<?php

namespace YouSaidItCards\Modules\StabilityAi;

use YouSaidItCards\Modules\StabilityAi\Admin\Admin;
use YouSaidItCards\Modules\StabilityAi\Rest\AdminLogController;

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

			if ( defined( 'STABILITY_AI_ENABLED' ) && STABILITY_AI_ENABLED ) {
				add_action( 'wp_ajax_stability_ai_api_test', [ self::$instance, 'api_test' ] );
				Admin::init();
				AdminLogController::init();
				BackgroundGenerateThumbnail::init();
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
		$image    = StabilityAiClient::generate_stable_image_core( 'lovely bird' );
		if ( is_wp_error( $image ) ) {
			var_dump( $image );
			wp_die();
		}
		header( 'Content-type: image/png' );
		echo $image;
//		var_dump( $image );
		wp_die();
	}
}
