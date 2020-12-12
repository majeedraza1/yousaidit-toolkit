<?php

namespace YouSaidItCards;

use YouSaidItCards\Admin\Admin;
use YouSaidItCards\Frontend\Frontend;
use YouSaidItCards\Modules\Auth\AuthManager;

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
		$this->container['module_auth'] = AuthManager::init();
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
		$this->container['frontend'] = Frontend::init();
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
