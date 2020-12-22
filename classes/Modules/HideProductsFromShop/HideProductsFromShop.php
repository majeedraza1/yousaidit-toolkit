<?php

namespace Yousaidit\Modules\HideProductsFromShop;

// If this file is called directly, abort.
use Yousaidit\Modules\Designers\Models\CardDesigner;

defined( 'ABSPATH' ) || die;

class HideProductsFromShop {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter( 'woocommerce_shortcode_products_query', [ self::$instance, 'shortcode_products_query' ] );
			add_action( 'woocommerce_product_query', [ self::$instance, 'custom_pre_get_posts_query' ] );

			add_action( 'add_meta_boxes', [ self::$instance, 'add_meta_boxes' ] );
			add_action( 'save_post', [ self::$instance, 'save_product_meta' ] );
		}

		return self::$instance;
	}

	/**
	 * Add metabox
	 *
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		if ( $post_type == 'product' ) {
			add_meta_box( 'stackonet-toolkit-options', 'Stackonet Toolkit Options',
				[ $this, 'meta_box_callback' ], $post_type, 'side', 'low' );
		}
	}

	/**
	 * @param \WP_Post $post
	 */
	public function meta_box_callback( $post ) {
		$_hide_from_shop = get_post_meta( $post->ID, '_hide_from_shop', true );
		?>
		<div>
			<fieldset>
				<legend class="screen-reader-text"><span>Hide from Shop</span></legend>
				<label for="_hide_from_shop">
					<input type="hidden" name="_hide_from_shop" value="off">
					<input name="_hide_from_shop" type="checkbox" id="_hide_from_shop"
					       value="on" <?php checked( 'on', $_hide_from_shop ) ?>>
					Hide from Shop
				</label>
			</fieldset>
			<p class="description">Check to hide this product from shop.</p>
		</div>
		<?php
	}

	/**
	 * Save metabox
	 *
	 * @param int $post_id
	 */
	public function save_product_meta( $post_id ) {
		if ( isset( $_POST['_hide_from_shop'] ) ) {
			update_post_meta( $post_id, '_hide_from_shop', $_POST['_hide_from_shop'] );
		} elseif ( false !== get_post_meta( $post_id, '_hide_from_shop', true ) ) {
			delete_post_meta( $post_id, '_hide_from_shop' );
		}
	}

	/**
	 * Hide product from shop by product tag term
	 *
	 * @param \WP_Query $query
	 */
	public function custom_pre_get_posts_query( $query ) {
		if ( empty( $query->get( CardDesigner::PROFILE_ENDPOINT ) ) && static::is_woocommerce_page() ) {
			// Meta Query
			$meta_query   = (array) $query->get( 'meta_query' );
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => '_hide_from_shop',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_hide_from_shop',
					'value'   => 'on',
					'compare' => '!=',
				]
			];
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Hide product from shortcode with custom meta value
	 *
	 * @param $query_args
	 *
	 * @return array
	 */
	public function shortcode_products_query( $query_args ) {
		global $wp_query;
		if ( empty( $wp_query->get( CardDesigner::PROFILE_ENDPOINT ) ) ) {
			$query_args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => '_hide_from_shop',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_hide_from_shop',
					'value'   => 'on',
					'compare' => '!=',
				]
			];
		}

		return $query_args;
	}

	/**
	 * Check if is woocommerce page
	 *
	 * @return bool
	 */
	public static function is_woocommerce_page() {
		return ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() );
	}
}
