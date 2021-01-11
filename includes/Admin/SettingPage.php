<?php

namespace YouSaidItCards\Admin;

use Stackonet\WP\Framework\SettingApi\DefaultSettingApi;

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

			self::$instance->add_settings_page();

			add_action( 'admin_init', [ self::$instance, 'clear_cache' ] );
		}

		return self::$instance;
	}

	public function clear_cache() {
		if ( isset( $_POST['option_page'] ) && '_stackonet_toolkit' == $_POST['option_page'] ) {
			$names = [
				'_transient_timeout_ship_station_orders_',
				'_transient_ship_station_orders_',
				'_transient_timeout_order_items_by_card_sizes',
				'_transient_order_items_by_card_sizes',
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
			'ship_station_api_key'                => '26abc5f26e6848daaf9eb0b68c64ddc0',
			'ship_station_api_secret'             => 'c4501ea5a74d489aa91568a82b3fd420',
			// Trade Site
			'postcard_product_id'                 => '',
			'trade_site_url'                      => '',
			'trade_site_auth_token'               => '',
			'trade_site_rest_namespace'           => '',
			'trade_site_card_to_product_endpoint' => '',
		];
		$_options        = (array) get_option( '_stackonet_toolkit', [] );
		$options         = wp_parse_args( $_options, $default_options );

		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
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

		$setting->set_section( [
			'id'       => 'section_postcard_settings',
			'title'    => __( 'Postcard Settings' ),
			'panel'    => 'general',
			'priority' => 5,
		] );

		$setting->set_section( [
			'id'          => 'section_ship_station_api',
			'title'       => __( 'ShipStation Api', 'dialog-contact-form' ),
			'description' => __( 'ShipStation Api settings', 'dialog-contact-form' ),
			'panel'       => 'integrations',
			'priority'    => 10,
		] );

		$setting->set_section( [
			'id'          => 'section_trade_site_auth',
			'title'       => __( 'Auth', 'dialog-contact-form' ),
			'description' => __( 'Auth settings', 'dialog-contact-form' ),
			'panel'       => 'trade_site',
			'priority'    => 10,
		] );

		$setting->set_field( [
			'id'                => 'postcard_product_id',
			'type'              => 'text',
			'title'             => __( 'Postcard Product id' ),
			'description'       => __( 'Product ID for postcard.' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'panel'             => 'general',
		] );

		$setting->set_field( [
			'id'                => 'ship_station_api_key',
			'type'              => 'text',
			'title'             => __( 'Api key' ),
			'description'       => __( 'ShipStation api key' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'panel'             => 'integrations',
		] );

		$setting->set_field( [
			'id'                => 'ship_station_api_secret',
			'type'              => 'text',
			'title'             => __( 'Api secret' ),
			'description'       => __( 'ShipStation api secret' ),
			'default'           => '',
			'priority'          => 15,
			'sanitize_callback' => 'sanitize_text_field',
			'panel'             => 'integrations',
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
	}
}
