<?php

namespace YouSaidItCards\Modules\StabilityAi\Rest;

use YouSaidItCards\Modules\StabilityAi\Settings;
use YouSaidItCards\Modules\StabilityAi\StabilityAiClient;
use YouSaidItCards\REST\ApiController;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * AdminLogController class
 */
class AdminLogController extends ApiController {

	/**
	 * Rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'admin/stability-ai-logs';

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', [ self::$instance, '_register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function _register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get settings
	 *
	 * @param  WP_REST_Request  $request  The request details.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ) {
		return $this->respondOK(
			[
				'editable'      => false === Settings::is_in_config_file(),
				'message'       => $this->setting_page_message(),
				'settings'      => Settings::get_settings(),
				'engines'       => StabilityAiClient::get_engines_list(),
				'style_presets' => StabilityAiClient::get_style_presets(),
			]
		);
	}

	/**
	 * Get settings
	 *
	 * @param  WP_REST_Request  $request  The request details.
	 *
	 * @return WP_REST_Response
	 */
	public function update_settings( WP_REST_Request $request ) {
		if ( Settings::is_in_config_file() ) {
			return $this->respondForbidden(
				null,
				'Config defined on wp-config.php file and cannot edit via settings.'
			);
		}

		$params   = $request->get_params();
		$settings = Settings::update_settings( $params );

		return $this->respondOK(
			[
				'editable'      => false === Settings::is_in_config_file(),
				'message'       => $this->setting_page_message(),
				'settings'      => $settings,
				'engines'       => StabilityAiClient::get_engines_list(),
				'style_presets' => StabilityAiClient::get_style_presets(),
			]
		);
	}

	/**
	 * Setting page message
	 *
	 * @return string
	 */
	public function setting_page_message(): string {
		$message = "Settings are defined via 'wp-config.php' file and cannot edit here. ";
		$message .= sprintf(
			"To edit setting here, delete '%s' constant from 'wp-config.php' file.",
			Settings::OPTION_NAME
		);

		return $message;
	}
}
