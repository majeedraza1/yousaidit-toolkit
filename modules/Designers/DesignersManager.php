<?php

namespace YouSaidItCards\Modules\Designers;

use YouSaidItCards\Modules\Designers\Admin\Admin;
use YouSaidItCards\Modules\Designers\Admin\Settings;
use YouSaidItCards\Modules\Designers\Frontend\DesignerCustomerProfile;
use YouSaidItCards\Modules\Designers\Frontend\DesignerProfile;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;
use YouSaidItCards\Modules\Designers\Models\Payment;
use YouSaidItCards\Modules\Designers\Models\PaymentItem;
use YouSaidItCards\Modules\Designers\REST\DesignerAdminController;
use YouSaidItCards\Modules\Designers\REST\DesignerCardAdminController;
use YouSaidItCards\Modules\Designers\REST\DesignerCardAttachmentController;
use YouSaidItCards\Modules\Designers\REST\DesignerCardController;
use YouSaidItCards\Modules\Designers\REST\DesignerCommissionAdminController;
use YouSaidItCards\Modules\Designers\REST\DesignerCommissionController;
use YouSaidItCards\Modules\Designers\REST\DesignerContactController;
use YouSaidItCards\Modules\Designers\REST\DesignerController;
use YouSaidItCards\Modules\Designers\REST\DesignerPaymentController;
use YouSaidItCards\Modules\Designers\REST\FaqController;
use YouSaidItCards\Modules\Designers\REST\PayPalPayoutController;
use YouSaidItCards\Modules\Designers\REST\SettingController;
use YouSaidItCards\Modules\Designers\REST\UserRegistrationController;
use YouSaidItCards\Modules\Designers\REST\WebLoginController;
use YouSaidItCards\ShipStation\Order;
use YouSaidItCards\ShipStation\ShipStationApi;
use YouSaidItCards\Utils;

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

			add_action( 'init', [ self::$instance, 'register_post_type' ] );
			add_action( 'wp', [ self::$instance, 'schedule_cron_event' ] );
			add_action( 'sync_commissions_from_shipstation', [ self::$instance, 'sync_commission' ] );
			add_action( 'wp_ajax_sync_orders_commissions', [ self::$instance, 'sync_commission' ] );
			add_action( 'wp_ajax_remove_orders_commissions', [ self::$instance, 'remove_commissions' ] );

			add_filter( 'woocommerce_order_actions', [ self::$instance, 'add_custom_order_action' ], 99 );
			add_action( 'woocommerce_order_action_update_designer_commission',
				[ self::$instance, 'update_designer_commission' ] );

			CommissionCalculator::init();
			DesignerCustomerProfile::init();
			BackgroundCommissionSync::init();

			if ( Utils::is_request( 'frontend' ) ) {
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
				DesignerAdminController::init();
			}

			if ( Utils::is_request( 'admin' ) ) {
				Admin::init();
			}
		}

		return self::$instance;
	}

	/**
	 * Initial plugin activation functionality
	 */
	public static function activation() {
		( new DesignerCard() )->create_table();
		( new DesignerCommission() )->create_table();
		( new Payment() )->create_table();
		( new PaymentItem() )->create_table();
		DesignerCustomerProfile::custom_rewrite_rule();
		CardDesigner::add_role_if_not_exists();
		self::schedule_cron_event();
	}

	/**
	 * Register post type
	 */
	public function register_post_type() {
		register_post_type( FAQ::POST_TYPE, FAQ::get_post_type_args() );
	}

	/**
	 * Schedule cron event
	 */
	public static function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'sync_commissions_from_shipstation' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'sync_commissions_from_shipstation' );
		}
	}

	/**
	 * Sync commission
	 */
	public function sync_commission() {
		BackgroundCommissionSync::sync_orders();
		die();
	}

	/**
	 * Remove commissions that do not have marketplace info
	 *
	 * @return void
	 */
	public function remove_commissions() {
		DesignerCommission::remove_commission_without_marketplace();
		die;
	}

	public function add_custom_order_action( $actions ) {
		$actions['update_designer_commission'] = 'Update Designer Commission (if any)';

		return $actions;
	}

	public function update_designer_commission( \WC_Order $wc_order ) {
		$order = ShipStationApi::init()->get_order_by_wc_order( $wc_order->get_id() );
		if ( $order instanceof Order ) {
			foreach ( $order->get_order_items() as $order_item ) {
				$order_item->update_designer_commission();
			}
		}
	}
}
