<?php

namespace YouSaidItCards;

use Stackonet\WP\Framework\Supports\Validate;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Server;
use YouSaidItCards\Admin\Admin;
use YouSaidItCards\Frontend\Frontend;
use YouSaidItCards\Modules\Auth\AuthManager;
use YouSaidItCards\Modules\Customer\CustomerManager;
use YouSaidItCards\Modules\WooCommerce\WooCommerceManager;
use YouSaidItCards\REST\ContactController;

defined( 'ABSPATH' ) || exit;

/**
 * The main plugin handler class is responsible for initializing plugin. The
 * class registers and all the components required to run the plugin.
 */
class Plugin {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Holds various class instances
	 *
	 * @var array
	 */
	private $container = [];

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'plugins_loaded', [ self::$instance, 'includes' ] );
			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'activation_includes' ] );
			add_action( 'yousaidit_toolkit/deactivation', [ self::$instance, 'deactivation_includes' ] );
			add_filter( 'rest_post_dispatch', [ self::$instance, 'rest_post_dispatch' ], 10, 3 );
		}

		return self::$instance;
	}

	/**
	 * Instantiate the required classes
	 *
	 * @return void
	 */
	public function includes() {
		$this->container['i18n']   = i18n::init();
		$this->container['assets'] = Assets::init();

		// Load classes for admin area
		if ( $this->is_request( 'admin' ) ) {
			$this->admin_includes();
		}

		// Load classes for frontend area
		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		// Load classes for ajax functionality
		if ( $this->is_request( 'ajax' ) ) {
			$this->ajax_includes();
		}

		$this->modules_includes();
	}

	/**
	 * Include modules main classes
	 *
	 * @return void
	 */
	public function modules_includes() {
		$this->container['module_auth']     = AuthManager::init();
		$this->container['module_customer'] = CustomerManager::init();
		$this->container['module_wc']       = WooCommerceManager::init();
	}

	/**
	 * Include admin classes
	 *
	 * @return void
	 */
	public function admin_includes() {
		$this->container['admin'] = Admin::init();
	}

	/**
	 * Include frontend classes
	 *
	 * @return void
	 */
	public function frontend_includes() {
		$this->container['frontend']        = Frontend::init();
		$this->container['rest_contact_us'] = ContactController::init();
	}

	/**
	 * Include frontend classes
	 *
	 * @return void
	 */
	public function ajax_includes() {
		$this->container['ajax'] = Ajax::init();
	}

	/**
	 * Run on plugin activation
	 *
	 * @return void
	 */
	public function activation_includes() {
		AuthManager::activation();
		CustomerManager::activation();
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation
	 *
	 * @return void
	 */
	public function deactivation_includes() {
		flush_rewrite_rules();
	}

	/**
	 * Modify error response for our endpoint
	 *
	 * @param WP_HTTP_Response $result  Result to send to the client. Usually a WP_REST_Response.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 *
	 * @return WP_HTTP_Response
	 */
	public function rest_post_dispatch( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( strpos( $request->get_route(), 'yousaidit/v1' ) ) {
			$data = $result->get_data();
			if ( isset( $data['code'], $data['message'], $data['data']['status'] ) ) {
				$response_data = [
					'success' => Validate::between( (int) $result->get_status(), 200, 299 ),
					'code'    => $data['code'],
					'message' => $data['message'],
				];

				$result->set_data( $response_data );;
			}
		}

		return $result;
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, rest, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( string $type ): bool {
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
}
