<?php

namespace YouSaidItCards\Modules\Customer;

use YouSaidItCards\Modules\Customer\Models\BaseAddress;
use YouSaidItCards\Modules\Customer\Models\Session;
use YouSaidItCards\Modules\Customer\REST\AddressController;
use YouSaidItCards\Modules\Customer\REST\MyAccountController;
use YouSaidItCards\Modules\Customer\REST\SessionController;
use YouSaidItCards\Modules\Customer\REST\UserProfileController;

class CustomerManager {

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

			AddressController::init();
			UserProfileController::init();
			MyAccountController::init();
			SessionController::init();
		}

		return self::$instance;
	}

	public static function activation() {
		BaseAddress::create_table();
		Session::create_table();
	}
}
