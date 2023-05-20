<?php

namespace YouSaidItCards\Admin;

use Stackonet\WP\Framework\SettingApi\DefaultSettingApi;
use YouSaidItCards\ShipStation\ShipStationApi;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class SettingPage {

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
			self::$instance = new self;

			add_action( 'init', [ self::$instance, 'add_settings_page' ] );
			add_action( 'admin_init', [ self::$instance, 'clear_cache' ] );
			add_action( 'admin_enqueue_scripts', [ self::$instance, 'load_script' ] );
		}

		return self::$instance;
	}

	public function load_script( $hook ) {
		if ( "settings_page_stackonet-toolkit" == $hook ) {
			wp_enqueue_script( 'stackonet-toolkit-admin' );
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_style( 'select2' );
		}
	}

	public function clear_cache() {
		if ( isset( $_POST['option_page'] ) && '_stackonet_toolkit' == $_POST['option_page'] ) {
			$names = [
				'_transient_timeout_ship_station_orders_',
				'_transient_ship_station_orders_',
				'_transient_timeout_order_items_by_card_sizes',
				'_transient_order_items_by_card_sizes',
				'_transient_timeout_other_products_tab_categories',
				'_transient_other_products_tab_categories',
			];
			global $wpdb;
			$sql = "DELETE FROM {$wpdb->options} WHERE 1 = 1 AND";
			$sql .= " (";
			foreach ( $names as $index => $name ) {
				if ( $index > 0 ) {
					$sql .= " OR";
				}
				$sql .= $wpdb->prepare( " option_name LIKE %s", '%' . $name . '%' );
			}
			$sql .= " )";

			$wpdb->query( $sql );
		}
	}

	public static function get_option( $key, $default = '' ) {
		$default_options = [
			'enable_adult_content_check'                 => '1',
			'enable_adult_content_check_for_video'       => '0',
			// ShipStation API key
			'ship_station_api_key'                       => '',
			'ship_station_api_secret'                    => '',
			// PayPal Config
			'paypal_sandbox_mode'                        => '',
			'paypal_client_id'                           => '',
			'paypal_client_secret'                       => '',
			// Google Cloud
			'google_api_secret_key'                      => '',
			// Google video intelligence
			'google_video_intelligence_key'              => '',
			'google_video_intelligence_project_id'       => '',
			// Trade Site
			'trade_site_url'                             => '',
			'trade_site_auth_token'                      => '',
			'trade_site_rest_namespace'                  => '',
			'trade_site_card_to_product_endpoint'        => '',
			// Postcard
			'postcard_product_id'                        => '',
			// Inner message
			'inner_message_price'                        => '',
			'video_inner_message_price'                  => '',
			'max_upload_limit_text'                      => 'Maximum upload file size: 2MB',
			'file_uploader_terms_and_condition'          => 'By uploading a video you are consenting to the You Said It’s Term of Use.',
			'video_message_qr_code_info_for_customer'    => 'Your video will play when they scan the QR code printed on the inside page.',
			'number_of_reminders_for_free_video_message' => 5,
			'inner_message_visible_on_cat'               => '',
			// Order Dispatcher
			'other_products_tab_categories'              => '',
		];
		$_options        = (array) get_option( '_stackonet_toolkit', [] );
		$options         = wp_parse_args( $_options, $default_options );

		return $options[ $key ] ?? $default;
	}

	/**
	 * Get order products ids
	 *
	 * @return array
	 */
	public static function get_other_products_ids(): array {
		$categories_ids = get_transient( 'other_products_tab_categories' );
		if ( is_array( $categories_ids ) ) {
			return $categories_ids;
		}
		$categories_ids = SettingPage::get_option( 'other_products_tab_categories' );
		$categories_ids = is_array( $categories_ids ) ? array_filter( array_map( 'intval', $categories_ids ) ) : [];

		$children = [];
		foreach ( $categories_ids as $categories_id ) {
			$children = array_merge( $children, get_term_children( $categories_id, 'product_cat' ) );
		}

		$terms_ids = array_merge( $categories_ids, $children );

		$terms        = get_terms(
			[
				'taxonomy' => 'product_cat',
				'include'  => $terms_ids,
			]
		);
		$slugs        = is_array( $terms ) ? wp_list_pluck( $terms, 'slug' ) : [];
		$products_ids = [];
		if ( count( $slugs ) ) {
			$products_ids = wc_get_products( [
				'category' => $slugs,
				'limit'    => - 1,
				'return'   => 'ids',
			] );

			set_transient( 'other_products_tab_categories', $products_ids, HOUR_IN_SECONDS );
		}

		return $products_ids;
	}

	/**
	 * Add setting page
	 */
	public function add_settings_page() {
		$setting = new DefaultSettingApi();
		$setting->set_option_name( '_stackonet_toolkit' );
		$setting->add_menu( [
			'parent_slug' => 'options-general.php',
			'menu_title'  => 'Stackonet Toolkit',
			'page_title'  => 'Stackonet Toolkit',
			'menu_slug'   => 'stackonet-toolkit',
		] );

		$setting->set_panel( [ 'id' => 'general', 'title' => 'General', 'priority' => 5, ] );
		$setting->set_panel( [ 'id' => 'integrations', 'title' => 'Integrations', 'priority' => 10, ] );
		$setting->set_panel( [ 'id' => 'trade_site', 'title' => 'Trade Site', 'priority' => 20 ] );
		$setting->set_panel( [ 'id' => 'market_place', 'title' => 'Market Places', 'priority' => 30 ] );
		$setting->set_panel( [ 'id' => 'help', 'title' => 'Help', 'priority' => 40 ] );

		$setting->set_panels( apply_filters( 'yousaidit_toolkit/settings/panels', [] ) );
		$setting->set_fields( apply_filters( 'yousaidit_toolkit/settings/fields', [] ) );
		$setting->set_sections( apply_filters( 'yousaidit_toolkit/settings/sections', [] ) );

		$sections = [
			[
				'id'       => 'section_general',
				'title'    => __( 'General Settings' ),
				'panel'    => 'general',
				'priority' => 2,
			],
			[
				'id'       => 'section_postcard_settings',
				'title'    => __( 'Postcard Settings' ),
				'panel'    => 'general',
				'priority' => 5,
			],
			[
				'id'       => 'section_order_dispatcher',
				'title'    => __( 'Order Dispatcher Settings' ),
				'panel'    => 'general',
				'priority' => 7,
			],
			[
				'id'          => 'section_ship_station_api',
				'title'       => __( 'ShipStation Api', 'dialog-contact-form' ),
				'description' => __( 'ShipStation Api settings', 'dialog-contact-form' ),
				'panel'       => 'integrations',
				'priority'    => 10,
			],
			[
				'id'          => 'section_paypal_api',
				'panel'       => 'integrations',
				'title'       => __( 'PayPal Api', 'dialog-contact-form' ),
				'description' => __( 'PayPal Api settings', 'dialog-contact-form' ),
				'priority'    => 20,
			],
			[
				'id'          => 'section_google_api',
				'panel'       => 'integrations',
				'title'       => __( 'Google Api', 'dialog-contact-form' ),
				'description' => __( 'Google Api settings', 'dialog-contact-form' ),
				'priority'    => 30,
			],
			[
				'id'          => 'section_trade_site_auth',
				'title'       => __( 'Auth', 'dialog-contact-form' ),
				'description' => __( 'Auth settings', 'dialog-contact-form' ),
				'panel'       => 'trade_site',
				'priority'    => 10,
			],
			[
				'id'          => 'section_marketplace',
				'title'       => __( 'Marketplace', 'dialog-contact-form' ),
				'description' => __( 'Marketplace settings', 'dialog-contact-form' ),
				'panel'       => 'market_place',
				'priority'    => 10,
			]
		];
		$setting->set_sections( $sections );

		$setting->set_field( [
			'id'                => 'enable_adult_content_check',
			'type'              => 'checkbox',
			'title'             => __( 'Enable adult content check' ),
			'description'       => __( 'Enable adult content check on media image' ),
			'priority'          => 10,
			'default'           => 'yes',
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_general',
		] );

		$setting->set_field( [
			'id'                => 'enable_adult_content_check_for_video',
			'type'              => 'checkbox',
			'title'             => __( 'Enable adult content check on video' ),
			'priority'          => 10,
			'default'           => 0,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_general',
		] );

		$setting->set_field( [
			'id'                => 'postcard_product_id',
			'type'              => 'text',
			'title'             => __( 'Postcard Product id' ),
			'description'       => __( 'Product ID for postcard.' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_postcard_settings',
		] );

		$setting->set_field( [
			'id'                => 'ship_station_api_key',
			'type'              => 'text',
			'title'             => __( 'Api key' ),
			'description'       => __( 'ShipStation api key' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_ship_station_api',
		] );

		$setting->set_field( [
			'id'                => 'ship_station_api_secret',
			'type'              => 'text',
			'title'             => __( 'Api secret' ),
			'description'       => __( 'ShipStation api secret' ),
			'default'           => '',
			'priority'          => 15,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_ship_station_api',
		] );

		$setting->set_field( [
			'id'                => 'trade_site_url',
			'type'              => 'url',
			'title'             => __( 'Trade site URL' ),
			'description'       => __( 'Enter trade site url' ),
			'default'           => '',
			'priority'          => 5,
			'sanitize_callback' => 'esc_url',
			'section'           => 'section_trade_site_auth',
		] );

		$setting->set_field( [
			'id'                => 'trade_site_auth_token',
			'type'              => 'textarea',
			'title'             => __( 'Auth Token' ),
			'description'       => __( 'Go to Trade site and navigate to Settings ==> JWT Auth. Click on "Generate Token" button. Set week at least 52 (one year).' ),
			'default'           => '',
			'priority'          => 6,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_trade_site_auth',
		] );

		$setting->set_field( [
			'id'                => 'trade_site_rest_namespace',
			'type'              => 'text',
			'title'             => __( 'REST namespace' ),
			'description'       => __( 'Enter REST namespace' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_trade_site_auth',
		] );

		$setting->set_field( [
			'id'                => 'trade_site_card_to_product_endpoint',
			'type'              => 'text',
			'title'             => __( 'Card to Product REST endpoint' ),
			'description'       => __( 'Enter endpoint to create product from card.' ),
			'default'           => '',
			'priority'          => 15,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_trade_site_auth',
		] );

		$setting->set_field( [
			'id'                => 'paypal_client_id',
			'type'              => 'text',
			'title'             => __( 'Client ID' ),
			'description'       => __( 'Enter PayPal Client id or define a new constant `PAYPAL_CLIENT_ID` in wp-config.php file.' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_paypal_api',
		] );

		$setting->set_field( [
			'id'                => 'paypal_client_secret',
			'type'              => 'text',
			'title'             => __( 'Client Secret' ),
			'description'       => __( 'Enter PayPal Secret or define a new constant `PAYPAL_CLIENT_SECRET` in wp-config.php file.' ),
			'default'           => '',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_paypal_api',
		] );

		$setting->set_field( [
			'id'                => 'paypal_sandbox_mode',
			'type'              => 'checkbox',
			'title'             => __( 'Sandbox Mode' ),
			'description'       => __( 'Check the checkbox to enable sandbox mode or define a new constant `PAYPAL_SANDBOX_MODE` with true value in wp-config.php file.' ),
			'default'           => '',
			'priority'          => 30,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_paypal_api',
		] );

		$setting->set_field( [
			'id'                => 'paypal_sandbox_client_id',
			'type'              => 'text',
			'title'             => __( 'Sandbox Client ID' ),
			'description'       => __( 'Enter PayPal Client id or define a new constant `PAYPAL_SANDBOX_CLIENT_ID` in wp-config.php file.' ),
			'default'           => '',
			'priority'          => 40,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_paypal_api',
		] );

		$setting->set_field( [
			'id'                => 'paypal_sandbox_client_secret',
			'type'              => 'text',
			'title'             => __( 'Sandbox Client Secret' ),
			'description'       => __( 'Enter PayPal Secret or define a new constant `PAYPAL_SANDBOX_CLIENT_SECRET` in wp-config.php file.' ),
			'default'           => '',
			'priority'          => 45,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_paypal_api',
		] );
		$setting->set_field( [
			'id'                => 'google_api_secret_key',
			'type'              => 'text',
			'title'             => __( 'Google API Secret' ),
			'default'           => '',
			'priority'          => 45,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_google_api',
		] );

		$setting->set_field( [
			'id'                => 'shipstation_yousaidit_store_id',
			'type'              => 'select',
			'title'             => __( 'Store 1: You Said It Cards' ),
			'description'       => __( 'ShipStation store for You Said It Cards' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_marketplace',
			'options'           => self::get_market_places(),
		] );

		$setting->set_field( [
			'id'                => 'shipstation_yousaidit_trade_store_id',
			'type'              => 'select',
			'title'             => __( 'Store 1: You Said It Cards - Trade' ),
			'description'       => __( 'ShipStation store for You Said It Cards - Trade' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_marketplace',
			'options'           => self::get_market_places(),
		] );

		$setting->set_field( [
			'id'                => 'shipstation_etsy_store_id',
			'type'              => 'select',
			'title'             => __( 'Store 1: Etsy' ),
			'description'       => __( 'ShipStation store for Etsy' ),
			'default'           => '',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_marketplace',
			'options'           => self::get_market_places(),
		] );

		$setting->set_field( [
			'id'                => 'shipstation_amazon_store_id',
			'type'              => 'select',
			'title'             => __( 'Store 1: Amazon' ),
			'description'       => __( 'ShipStation store for Amazon' ),
			'default'           => '',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_marketplace',
			'options'           => self::get_market_places(),
		] );

		$setting->set_field( [
			'id'                => 'shipstation_ebay_store_id',
			'type'              => 'select',
			'title'             => __( 'Store 1: eBay' ),
			'description'       => __( 'ShipStation store for eBay' ),
			'default'           => '',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_marketplace',
			'options'           => self::get_market_places(),
		] );

		$action_url = admin_url( 'admin-ajax.php?action=yousaidit_clear_background_task' );
		$setting->set_field( [
			'id'       => 'flash_background_task',
			'type'     => 'html',
			'title'    => __( 'Clear background task' ),
			'priority' => 10,
			'panel'    => 'help',
			'html'     => sprintf( '<a href="%s" target="_blank">Clear Now</a>', $action_url )
		] );

		$action_url = admin_url( 'admin-ajax.php?action=yousaidit_dompdf_install_font' );
		$setting->set_field( [
			'id'          => 'yousaidit_dompdf_install_font',
			'type'        => 'html',
			'title'       => __( 'Install Dompdf missing fonts' ),
			'description' => __( 'Dompdf is required to generate inner message PDF.' ),
			'priority'    => 20,
			'panel'       => 'help',
			'html'        => sprintf( '<a href="%s" target="_blank">Install Now</a>', $action_url )
		] );

		$action_url = admin_url( 'admin-ajax.php?action=yousaidit_tfpdf_install_font' );
		$setting->set_field( [
			'id'          => 'yousaidit_tfpdf_install_font',
			'type'        => 'html',
			'title'       => __( 'Install tFPDF missing fonts' ),
			'description' => __( 'tFPDF is required to generate dynamic card PDF.' ),
			'priority'    => 30,
			'panel'       => 'help',
			'html'        => sprintf( '<a href="%s" target="_blank">Install Now</a>', $action_url )
		] );

		$action_url = admin_url( 'admin-ajax.php?action=yousaidit_clear_tfpdf_fonts_cache' );
		$setting->set_field( [
			'id'       => 'flash_tfpdf_font_cache',
			'type'     => 'html',
			'title'    => __( 'Clear tFPDF fonts cache' ),
			'priority' => 40,
			'panel'    => 'help',
			'html'     => sprintf( '<a href="%s" target="_blank">Clear Now</a>', $action_url )
		] );

		$action_url = admin_url( 'admin-ajax.php?action=yousaidit_clear_transient_cache' );
		$setting->set_field( [
			'id'       => 'flash_transient_cache',
			'type'     => 'html',
			'title'    => __( 'Clear all transient cache' ),
			'priority' => 40,
			'panel'    => 'help',
			'html'     => sprintf( '<a href="%s" target="_blank">Clear Now</a>', $action_url )
		] );

		$setting->set_field( [
			'id'                => 'other_products_tab_categories',
			'type'              => 'select',
			'title'             => __( 'Print Cards categories for Other Products' ),
			'description'       => __( 'Set product categories for: Order Dispatcher ==> Print Cards ==> Other Products. Only choose parent categories.' ),
			'default'           => '',
			'priority'          => 20,
			'multiple'          => true,
			'sanitize_callback' => function ( $value ) {
				return $value ? array_map( 'intval', $value ) : '';
			},
			'section'           => 'section_order_dispatcher',
			'options'           => self::get_product_categories(),
		] );
	}

	/**
	 * Get market places list
	 *
	 * @return string[]
	 */
	public static function get_market_places(): array {
		$items  = [
			"" => "-- Select Store --"
		];
		$stores = ShipStationApi::init()->get_stores();
		if ( is_array( $stores ) ) {
			foreach ( $stores as $store ) {
				$items[ $store['storeId'] ] = $store['storeName'];
			}
		}

		return $items;
	}

	/**
	 * Woocommerce product categories
	 *
	 * @return string[]
	 */
	public static function get_product_categories(): array {
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );
		$items = [ "" => "-- Select Category --" ];
		foreach ( $terms as $term ) {
			$items[ $term->term_id ] = $term->name;
		}

		return $items;
	}
}
