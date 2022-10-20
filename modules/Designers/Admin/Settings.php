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

	public static function designer_default_commission_for_yousaidit() {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );

		return isset( $options['designer_commission'] ) ? floatval( $options['designer_commission'] ) : 0;
	}

	public static function designer_default_commission_for_yousaidit_trade() {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );

		return isset( $options['designer_trade_site_commission'] ) ?
			floatval( $options['designer_trade_site_commission'] ) : 0;
	}

	public static function designer_card_sku_prefix(): string {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );

		return isset( $options['designer_card_sku_prefix'] ) ?
			sanitize_text_field( $options['designer_card_sku_prefix'] ) : '';
	}

	public static function designer_card_price(): string {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );

		return isset( $options['designer_card_price'] ) ? floatval( $options['designer_card_price'] ) : '';
	}

	public static function designer_minimum_amount_to_pay() {
		$options        = (array) get_option( 'yousaiditcard_designers_settings' );
		$minimum_amount = isset( $options['designer_minimum_amount_to_pay'] ) ?
			intval( $options['designer_minimum_amount_to_pay'] ) : 1;

		return max( $minimum_amount, 1 );
	}

	/**
	 * Get email address
	 *
	 * @return string
	 */
	public static function email_for_card_limit_extension(): string {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );
		$email   = $options['email_for_card_limit_extension'] ?? '';

		return is_email( $email ) ? $email : get_option( 'admin_email' );
	}

	/**
	 * Designer maximum allowed card
	 *
	 * @return int
	 */
	public static function designer_maximum_allowed_card(): int {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );
		$value   = isset( $options['designer_maximum_allowed_card'] ) ?
			intval( $options['designer_maximum_allowed_card'] ) : 5;

		return max( $value, 1 );
	}

	public static function product_categories_for_designer(): array {
		$options = (array) get_option( 'yousaiditcard_designers_settings' );
		$ids     = $options['product_categories_for_designer'] ?? [];
		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_map( 'intval', $ids );
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

		$sections = [
			[
				'id'          => 'general_settings_section',
				'title'       => __( 'General', 'stackonet-yousaidit-toolkit' ),
				'description' => __( 'Plugin general options.', 'stackonet-yousaidit-toolkit' ),
				'panel'       => 'general_settings_panel',
				'priority'    => 10,
			],
			[
				'id'          => 'product_settings_section',
				'title'       => __( 'Product', 'stackonet-yousaidit-toolkit' ),
				'description' => __( 'Plugin product options.', 'stackonet-yousaidit-toolkit' ),
				'panel'       => 'general_settings_panel',
				'priority'    => 20,
			]
		];

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
				'id'                => 'product_categories_for_designer',
				'type'              => 'select',
				'multiple'          => true,
				'title'             => __( 'Product categories', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Product categories for designer.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 42,
				'sanitize_callback' => function ( $value ) {
					return array_map( 'sanitize_text_field', $value );
				},
				'options'           => static::get_product_categories(),
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
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_maximum_allowed_card',
				'type'              => 'number',
				'title'             => __( 'Max allowed card', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Designer maximum allowed card. Admin can allow more for individual designer.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 55,
				'sanitize_callback' => 'floatval',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'email_for_card_limit_extension',
				'type'              => 'text',
				'title'             => __( 'Admin email address', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Admin email address for receiving card limit extension request.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 56,
				'default'           => get_option( 'admin_email' ),
				'sanitize_callback' => 'sanitize_text_field',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_commission',
				'type'              => 'text',
				'title'             => __( 'Designer Commission (Fix amount)', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Designer commission per sale.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 60,
				'default'           => 0.30,
				'sanitize_callback' => 'floatval',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_trade_site_commission',
				'type'              => 'text',
				'title'             => __( 'Designer Commission for trade site (Fix amount)', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Designer commission per sale for trade site.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 65,
				'default'           => 0.10,
				'sanitize_callback' => 'floatval',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_card_sku_prefix',
				'type'              => 'text',
				'title'             => __( 'Product SKU', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( "{{card_type}} will be replaced with 'S' for static or 'D' for dynamic. {{card_size}} will be replaced with 'S' for square, 'A4', 'A5' or 'A6'. {{card_id}} will be replaced with card id.", 'stackonet-yousaidit-toolkit' ),
				'priority'          => 70,
				'default'           => 'DC-{{card_type}}-{{card_size}}-{{card_id}}',
				'sanitize_callback' => 'sanitize_text_field',
			],
			[
				'section'           => 'general_settings_section',
				'id'                => 'designer_card_price',
				'type'              => 'text',
				'title'             => __( 'Product Price', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( "Product default price.", 'stackonet-yousaidit-toolkit' ),
				'priority'          => 75,
				'default'           => 2.99,
				'sanitize_callback' => 'floatval',
			],
			[
				'section'           => 'product_settings_section',
				'id'                => 'default_product_title',
				'type'              => 'text',
				'title'             => __( 'Default Product Title', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Default product title when generating product from card. Add placeholder {{card_title}} to get dynamic card title.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 50,
				'sanitize_callback' => 'sanitize_text_field',
			],
			[
				'section'           => 'product_settings_section',
				'id'                => 'default_product_content',
				'type'              => 'textarea',
				'title'             => __( 'Default Product Content', 'stackonet-yousaidit-toolkit' ),
				'description'       => __( 'Default product content when generating product from card. Add placeholder {{card_title}} to get dynamic card title. Add placeholder {{card_description}} to get dynamic card description.', 'stackonet-yousaidit-toolkit' ),
				'priority'          => 50,
				'sanitize_callback' => 'wp_filter_post_kses',
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

	public static function get_product_categories(): array {
		$cats  = [];
		$terms = get_terms( [
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'taxonomy'   => 'product_cat',
		] );
		foreach ( $terms as $term ) {
			$cats[] = [ 'label' => esc_html( $term->name ), 'value' => $term->term_id ];
		}

		return $cats;
	}
}
