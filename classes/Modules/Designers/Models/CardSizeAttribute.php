<?php

namespace Yousaidit\Modules\Designers\Models;

use WC_Product_Attribute;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class CardSizeAttribute {

	/**
	 * Card size product attribute
	 *
	 * @var string
	 */
	protected $attribute_name = '';

	/**
	 * @var WC_Product_Attribute
	 */
	protected $attribute;

	/**
	 * @var WP_Term[]|array
	 */
	protected $card_sizes = [];

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Required card sizes
	 *
	 * @var array
	 */
	protected $required_sizes = [ 'square', 'a4', 'a5', 'a6' ];

	/**
	 * Default card size
	 *
	 * @var string
	 */
	protected $default_size = 'square';

	/**
	 * Only one instance of the class
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->read_attribute();
		}

		return self::$instance;
	}

	/**
	 * CardSizeAttribute constructor.
	 */
	protected function __construct() {
		// return static::init();
	}

	/**
	 * Read attribute data
	 */
	protected function read_attribute() {
		$options        = (array) get_option( 'yousaiditcard_designers_settings' );
		$attribute_name = isset( $options['product_attribute_for_card_sizes'] ) ? $options['product_attribute_for_card_sizes'] : '';

		if ( empty( $attribute_name ) ) {
			return;
		}

		$this->attribute_name = $attribute_name;

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $tax ) {
			if ( $tax->attribute_name != $this->attribute_name ) {
				continue;
			}

			$taxonomy = wc_attribute_taxonomy_name( $this->attribute_name );

			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $tax->attribute_id );
			$attribute->set_name( $taxonomy );

			$this->attribute = $attribute;

			/** @var WP_Term[] $terms */
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, ] );

			if ( ! is_wp_error( $terms ) ) {
				$this->card_sizes = $terms;
			}
		}
	}

	/**
	 * Get card sizes
	 *
	 * @return array|WP_Term[]
	 */
	public function get_card_sizes() {
		return $this->card_sizes;
	}

	/**
	 * Get card attribute
	 *
	 * @return WC_Product_Attribute
	 */
	public function get_attribute() {
		return $this->attribute;
	}

	/**
	 * Get card slugs
	 *
	 * @return array
	 */
	public function get_card_slugs() {
		$sizes = $this->get_card_sizes();
		if ( empty( $sizes ) ) {
			return [];
		}

		return wp_list_pluck( $sizes, 'slug' );
	}

	/**
	 * Check if valid card size
	 *
	 * @param string $size_slug
	 *
	 * @return bool
	 */
	public function is_valid_card_size( $size_slug ) {
		return in_array( $size_slug, $this->get_card_slugs() );
	}

	/**
	 * Get attribute name
	 *
	 * @return string
	 */
	public function get_attribute_name() {
		return $this->attribute_name;
	}
}
