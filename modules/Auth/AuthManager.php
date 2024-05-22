<?php

namespace YouSaidItCards\Modules\Auth;

use YouSaidItCards\Modules\Auth\REST\AuthController;
use YouSaidItCards\Modules\Auth\REST\UserRegistrationController;

class AuthManager {
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

			Auth::init();
			AuthController::init();
			UserRegistrationController::init();
		}

		return self::$instance;
	}

	public static function activation() {
		Migration::create_table();
	}
}
