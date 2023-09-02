<?php

namespace YouSaidItCards;

use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Server;
use YouSaidItCards\Admin\Admin;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\Cli\Command;
use YouSaidItCards\Frontend\Frontend;
use YouSaidItCards\Modules\Auth\AuthManager;
use YouSaidItCards\Modules\CardMerger\CardMergerManager;
use YouSaidItCards\Modules\CardPopup\CardPopupManager;
use YouSaidItCards\Modules\ColorTheme\ColorThemeManager;
use YouSaidItCards\Modules\Customer\CustomerManager;
use YouSaidItCards\Modules\Designers\DesignersManager;
use YouSaidItCards\Modules\DispatchTimer\DispatchTimerManager;
use YouSaidItCards\Modules\DynamicCard\DynamicCardManager;
use YouSaidItCards\Modules\EvaTheme\EvaThemeManager;
use YouSaidItCards\Modules\Faq\FaqManager;
use YouSaidItCards\Modules\FeaturedProductsFirst\FeaturedProductsFirst;
use YouSaidItCards\Modules\HideProductsFromShop\HideProductsFromShop;
use YouSaidItCards\Modules\InnerMessage\InnerMessageManager;
use YouSaidItCards\Modules\MultistepCheckout\MultistepCheckout;
use YouSaidItCards\Modules\OrderDispatcher\OrderDispatcherManager;
use YouSaidItCards\Modules\PackingSlip\PackingSlipManager;
use YouSaidItCards\Modules\Reminders\RemindersManager;
use YouSaidItCards\Modules\RudeProduct\RudeProductManager;
use YouSaidItCards\Modules\TradeSite\TradeSiteManager;
use YouSaidItCards\Modules\WooCommerce\WooCommerceManager;
use YouSaidItCards\OpenAI\CardContentController;
use YouSaidItCards\REST\ContactController;
use YouSaidItCards\REST\OrderController;
use YouSaidItCards\REST\ProductController;
use YouSaidItCards\Session\SessionManager;
use YouSaidItCards\ShipStation\OrderItemPdf;
use YouSaidItCards\ShipStation\ShipStationApi;
use YouSaidItCards\ShipStation\ShipStationOrder;
use YouSaidItCards\ShipStation\ShipStationOrderAddress;
use YouSaidItCards\ShipStation\ShipStationOrderItem;
use YouSaidItCards\ShipStation\SyncShipStationOrder;
use YouSaidItCards\Utilities\PdfSizeCalculator;

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
	 * @var ShipStationApi|null
	 */
	protected $ship_station_api;

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

			self::$instance->container['session'] = SessionManager::init();
		}

		return self::$instance;
	}

	/**
	 * Instantiate the required classes
	 *
	 * @return void
	 */
	public function includes() {
		$this->ship_station_api                    = ShipStationApi::init();
		$this->container['i18n']                   = i18n::init();
		$this->container['assets']                 = Assets::init();
		$this->container['settings']               = SettingPage::init();
		$this->container['bt_pdf_size_calculator'] = PdfSizeCalculator::init();
		$this->container['sync_orders']            = SyncShipStationOrder::init();

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

		/*
		* WP-CLI Commands
		* wp make_font_metrics generate
		*/
		if ( class_exists( 'WP_CLI' ) && class_exists( 'WP_CLI_Command' ) ) {
			\WP_CLI::add_command( 'yousaidit-cli', Command::class );
		}
	}

	/**
	 * Include modules main classes
	 *
	 * @return void
	 */
	public function modules_includes() {
		$this->container['module_auth']        = AuthManager::init();
		$this->container['module_customer']    = CustomerManager::init();
		$this->container['module_wc']          = WooCommerceManager::init();
		$this->container['module_faq']         = FaqManager::init();
		$this->container['module_color_theme'] = ColorThemeManager::init();

		$this->container['module_checkout']         = MultistepCheckout::init();
		$this->container['module_dynamic_card']     = DynamicCardManager::init();
		$this->container['module_designers']        = DesignersManager::init();
		$this->container['module_rude_product']     = RudeProductManager::init();
		$this->container['module_order_dispatcher'] = OrderDispatcherManager::init();
		$this->container['module_packing_slip']     = PackingSlipManager::init();
		$this->container['module_card_merger']      = CardMergerManager::init();
		$this->container['module_hide_product']     = HideProductsFromShop::init();
		$this->container['module_inner_message']    = InnerMessageManager::init();
		$this->container['module_trade_site']       = TradeSiteManager::init();
		$this->container['module_eva_theme']        = EvaThemeManager::init();
		$this->container['module_featured_product'] = FeaturedProductsFirst::init();
		$this->container['module_reminder']         = RemindersManager::init();
		$this->container['module_dispatch_timer']   = DispatchTimerManager::init();
		$this->container['module_card_popup']       = CardPopupManager::init();
	}

	/**
	 * Include admin classes
	 *
	 * @return void
	 */
	public function admin_includes() {
		$this->container['admin'] = Admin::init();

		OrderItemPdf::create_table();
	}

	/**
	 * Include frontend classes
	 *
	 * @return void
	 */
	public function frontend_includes() {
		$this->container['frontend']        = Frontend::init();
		$this->container['rest_contact_us'] = ContactController::init();

		$this->container['rest-product']         = ProductController::init();
		$this->container['rest-order']           = OrderController::init();
		$this->container['rest-ai_card_content'] = CardContentController::init();
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
		ShipStationOrder::create_table();
		ShipStationOrderAddress::create_table();
		ShipStationOrderItem::create_table();
		DesignersManager::activation();
		RemindersManager::activation();
		InnerMessageManager::activation();
//		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation
	 *
	 * @return void
	 */
	public function deactivation_includes() {
//		flush_rewrite_rules();
	}

	/**
	 * Modify error response for our endpoint
	 *
	 * @param  WP_HTTP_Response  $result  Result to send to the client. Usually a WP_REST_Response.
	 * @param  WP_REST_Server  $server  Server instance.
	 * @param  WP_REST_Request  $request  Request used to generate the response.
	 *
	 * @return WP_HTTP_Response
	 */
	public function rest_post_dispatch( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( strpos( $request->get_route(), 'yousaidit/v1' ) ) {
			$data = $result->get_data();
			if ( isset( $data['code'], $data['message'], $data['data']['status'] ) ) {
				$response_data = [
					'success' => (int) $result->get_status() >= 200 && (int) $result->get_status() <= 299,
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
	 * @param  string  $type  admin, ajax, rest, cron or frontend.
	 *
	 * @return bool
	 */
	public function is_request( string $type ): bool {
		return Utils::is_request( $type );
	}
}
