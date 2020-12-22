<?php

namespace Yousaidit\Modules\Designers\Admin;

use Yousaidit\Modules\Designers\Supports\SettingHandler;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class Settings {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return Settings
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_loaded', [ self::$instance, 'settings' ] );
		}

		return self::$instance;
	}

	/**
	 * Plugin settings
	 */
	public static function settings() {
		$option_page = SettingHandler::init();

		$option_page->set_option_name( 'yousaiditcard_designers_settings' );

		$panels = array(
			array(
				'id'       => 'general_settings_panel',
				'title'    => __( 'General', 'stackonet-yousaidit-toolkit' ),
				'priority' => 10,
			)
		);

		// Add settings page tab
		$option_page->add_panels( $panels );

		$sections = array(
			array(
				'id'          => 'general_settings_section',
				'title'       => __( 'General', 'stackonet-yousaidit-toolkit' ),
				'description' => __( 'Plugin general options.', 'stackonet-yousaidit-toolkit' ),
				'panel'       => 'general_settings_panel',
				'priority'    => 10,
			)
		);

		// Add Sections
		$option_page->add_sections( $sections );

		$fields = array(
			array(
				'section'           => 'general_settings_section',
				'id'                => 'designer_dashboard_logo_id',
				'type'              => 'media-uploader',
				'title'             => __( 'Dashboard logo', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Enter media image id.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 10,
				'sanitize_callback' => 'intval',
			),
			array(
				'section'           => 'general_settings_section',
				'id'                => 'terms_page_id',
				'type'              => 'select',
				'title'             => __( 'Terms and Condition Page', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose a page to act as your terms and condition page for designers.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 10,
				'sanitize_callback' => 'intval',
				'options'           => static::get_pages_for_options(),
			),
			array(
				'section'           => 'general_settings_section',
				'id'                => 'product_attribute_for_card_sizes',
				'type'              => 'select',
				'title'             => __( 'Product Attribute for card sizes', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose product attribute that will be used for card sizes.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 15,
				'sanitize_callback' => 'sanitize_text_field',
				'options'           => static::get_product_attribute_taxonomies(),
			),
			array(
				'section'           => 'general_settings_section',
				'id'                => 'product_attribute_taxonomies',
				'type'              => 'select',
				'multiple'          => true,
				'title'             => __( 'Product Attributes', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose additional product attributes for designer for submit with new card request.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 20,
				'sanitize_callback' => function ( $value ) {
					return array_map( 'sanitize_text_field', $value );
				},
				'options'           => static::get_product_attribute_taxonomies(),
			),
			array(
				'section'           => 'general_settings_section',
				'id'                => 'paypal_client_id',
				'type'              => 'text',
				'title'             => __( 'PayPal Client ID', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Enter PayPal Client id or define a new constant `PAYPAL_CLIENT_ID` in wp-config.php file.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 40,
				'sanitize_callback' => 'sanitize_text_field',
			),
			array(
				'section'           => 'general_settings_section',
				'id'                => 'paypal_client_secret',
				'type'              => 'text',
				'title'             => __( 'PayPal Client Secret', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Enter PayPal Client secret or define a new constant `PAYPAL_CLIENT_SECRET` in wp-config.php file.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 40,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		$option_page->add_fields( $fields );
	}

	/**
	 * Get page options
	 *
	 * @return array
	 */
	public static function get_pages_for_options() {
		$_pages  = get_posts( [ 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => - 1 ] );
		$options = [];
		foreach ( $_pages as $page ) {
			$options[] = [ 'label' => get_the_title( $page ), 'value' => $page->ID ];
		}

		return $options;
	}

	/**
	 * Get product attribute taxonomies
	 *
	 * @return array
	 */
	public static function get_product_attribute_taxonomies() {
		$attributes = [];

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$attributes[] = [ 'label' => esc_html( $tax->attribute_label ), 'value' => $tax->attribute_name ];
		}

		return $attributes;
	}
}
