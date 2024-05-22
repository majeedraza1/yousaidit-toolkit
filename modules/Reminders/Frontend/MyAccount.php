<?php

namespace YouSaidItCards\Modules\Reminders\Frontend;

use Stackonet\WP\Framework\Supports\ArrayHelper;
use YouSaidItCards\Assets;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;

/**
 * My Account page.
 */
class MyAccount {

	/**
	 * Endpoint
	 */
	const ENDPOINT = 'reminders';

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * The instance of the class
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Hook to add rewrite endpoint
			add_action( 'init', [ self::$instance, 'custom_endpoints' ] );

			// Make sure to flash rewrite endpoint on plugin activation
			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'custom_endpoints' ] );

			// Add our endpoint to publicly allowed query vars
			add_filter( 'query_vars', [ self::$instance, 'query_vars' ] );
			// Add our endpoint to WooCommerce my-account menu list
			add_filter( 'woocommerce_account_menu_items', [ self::$instance, 'menu_items' ] );
			// Change default title
			add_filter( 'the_title', [ self::$instance, 'endpoint_title' ] );
			// Display endpoint content
			add_action( 'woocommerce_account_' . static::ENDPOINT . '_endpoint', [ self::$instance, 'content' ] );
		}

		return self::$instance;
	}

	/**
	 * Get endpoint url
	 *
	 * @return string
	 */
	public static function get_url() {
		return wc_get_account_endpoint_url( static::ENDPOINT );
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function custom_endpoints() {
		add_rewrite_endpoint( static::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add new query var.
	 *
	 * @param  array  $vars
	 *
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = static::ENDPOINT;

		return $vars;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param  array  $items
	 *
	 * @return array
	 */
	public function menu_items( $items ) {
		return ArrayHelper::insert_after( $items, 'orders', [
			static::ENDPOINT => __( 'Reminders', 'yousaidit-toolkit' ),
		] );
	}

	/**
	 * Change endpoint title.
	 *
	 * @param  string  $title
	 *
	 * @return string
	 */
	public function endpoint_title( $title ) {
		global $wp_query;

		$is_endpoint = isset( $wp_query->query_vars[ static::ENDPOINT ] );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'Reminders', 'yousaidit-toolkit' );

			remove_filter( 'the_title', [ $this, 'endpoint_title' ] );
		}

		return $title;
	}

	/**
	 * Endpoint HTML content.
	 */
	public function content() {
		echo '<div id="yousaidit_my_account_reminders"></div>';
		add_action( 'wp_footer', [ $this, 'my_account_reminders' ] );
		$file_url  = Assets::get_assets_url( 'js/reminders-frontend.js' );
		$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file_url );
		if ( file_exists( $file_path ) ) {
			wp_enqueue_script( 'yousaidit-reminders-frontend',
				$file_url, [],
				gmdate( 'Y.m.d.Gi', filemtime( $file_path ) ),
				true
			);
		}
	}

	/**
	 * Print dynamic data for my-account reminders
	 *
	 * @return void
	 */
	public function my_account_reminders() {
		$data = [
			'reminders' => ( new Reminder() )->find_by_user( get_current_user_id() ),
			'groups'    => ( new ReminderGroup() )->find_multiple( [ 'order_by' => [ 'menu_order ASC', 'id DESC' ] ] ),
			'countries' => WC()->countries->get_countries(),
			'states'    => WC()->countries->get_states(),
		];
		echo '<script>window.YousaiditMyAccountReminders = ' . wp_json_encode( $data ) . '</script>' . PHP_EOL;
	}
}
