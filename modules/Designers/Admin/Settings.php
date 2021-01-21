<?php

namespace YouSaidItCards\Modules\Designers\Admin;

use YouSaidItCards\Modules\Designers\Supports\SettingHandler;

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

	public static function designer_minimum_amount_to_pay() {
		$options        = (array) get_option( 'yousaiditcard_designers_settings' );
		$minimum_amount = isset( $options['designer_minimum_amount_to_pay'] ) ?
			intval( $options['designer_minimum_amount_to_pay'] ) : 1;

		return max( $minimum_amount, 1 );
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

		$fields = [
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_dashboard_logo_id',
				'type'              => 'media-uploader',
				'title'             => __( 'Dashboard logo', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Enter media image id.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 10,
				'sanitize_callback' => 'intval',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'terms_page_id',
				'type'              => 'select',
				'title'             => __( 'Terms and Condition Page', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose a page to act as your terms and condition page for designers.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 20,
				'sanitize_callback' => 'intval',
				'options'           => static::get_pages_for_options(),
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'product_attribute_for_card_sizes',
				'type'              => 'select',
				'title'             => __( 'Product Attribute for card sizes', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose product attribute that will be used for card sizes.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 30,
				'sanitize_callback' => 'sanitize_text_field',
				'options'           => static::get_product_attribute_taxonomies(),
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'product_attribute_taxonomies',
				'type'              => 'select',
				'multiple'          => true,
				'title'             => __( 'Product Attributes', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Choose additional product attributes for designer for submit with new card request.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 40,
				'sanitize_callback' => function ( $value ) {
					return array_map( 'sanitize_text_field', $value );
				},
				'options'           => static::get_product_attribute_taxonomies(),
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_minimum_amount_to_pay',
				'type'              => 'number',
				'title'             => __( 'Min amount to pay', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Enter minimum commission amount required to be payable. Value must be 1 or more.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 50,
				'sanitize_callback' => 'floatval',
			],
		];

		$option_page->add_fields( $fields );
	}

	/**
	 * Get page options
	 *
	 * @return array
	 */
	public static function get_pages_for_options(): array {
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
	public static function get_product_attribute_taxonomies(): array {
		$attributes = [];

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$attributes[] = [ 'label' => esc_html( $tax->attribute_label ), 'value' => $tax->attribute_name ];
		}

		return $attributes;
	}
}
