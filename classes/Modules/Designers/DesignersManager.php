<?php

namespace Yousaidit\Modules\Designers;

use Yousaidit\Modules\Designers\Admin\Admin;
use Yousaidit\Modules\Designers\Admin\Settings;
use Yousaidit\Modules\Designers\Frontend\DesignerCustomerProfile;
use Yousaidit\Modules\Designers\Frontend\DesignerProfile;
use Yousaidit\Modules\Designers\Models\CardDesigner;
use Yousaidit\Modules\Designers\Models\DesignerCard;
use Yousaidit\Modules\Designers\Models\DesignerCommission;
use Yousaidit\Modules\Designers\Models\Payment;
use Yousaidit\Modules\Designers\Models\PaymentItem;
use Yousaidit\Modules\Designers\REST\DesignerCardAdminController;
use Yousaidit\Modules\Designers\REST\DesignerCardAttachmentController;
use Yousaidit\Modules\Designers\REST\DesignerCardController;
use Yousaidit\Modules\Designers\REST\DesignerCommissionAdminController;
use Yousaidit\Modules\Designers\REST\DesignerCommissionController;
use Yousaidit\Modules\Designers\REST\DesignerContactController;
use Yousaidit\Modules\Designers\REST\DesignerController;
use Yousaidit\Modules\Designers\REST\DesignerPaymentController;
use Yousaidit\Modules\Designers\REST\FaqController;
use Yousaidit\Modules\Designers\REST\PayPalPayoutController;
use Yousaidit\Modules\Designers\REST\SettingController;
use Yousaidit\Modules\Designers\REST\UserRegistrationController;
use Yousaidit\Modules\Designers\REST\WebLoginController;

defined( 'ABSPATH' ) || exit;

class DesignersManager {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'stackonet_yousaidit_card/activation', [ self::$instance, 'activation' ] );
			add_action( 'init', [ self::$instance, 'register_post_type' ] );

			CommissionCalculator::init();
			DesignerCustomerProfile::init();

			if ( yousaidit_toolkit()->is_request( 'frontend' ) ) {
				// Frontend
				DesignerProfile::init();

				// REST
				WebLoginController::init();
				UserRegistrationController::init();
				DesignerController::init();
				DesignerCardController::init();
				DesignerCardAttachmentController::init();
				DesignerCardAdminController::init();
				SettingController::init();
				DesignerCommissionController::init();
				Settings::init();
				FaqController::init();
				DesignerContactController::init();
				DesignerCommissionAdminController::init();
				PayPalPayoutController::init();
				DesignerPaymentController::init();
			}

			if ( yousaidit_toolkit()->is_request( 'admin' ) ) {
				Admin::init();
			}
		}

		return self::$instance;
	}

	/**
	 * Initial plugin activation functionality
	 */
	public function activation() {
		( new DesignerCard() )->create_table();
		( new DesignerCommission() )->create_table();
		( new Payment() )->create_table();
		( new PaymentItem() )->create_table();
		CardDesigner::add_role_if_not_exists();
	}

	/**
	 * Register post type
	 */
	public function register_post_type() {
		register_post_type( FAQ::POST_TYPE, FAQ::get_post_type_args() );
	}
}
