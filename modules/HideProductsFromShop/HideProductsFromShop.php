<?php

namespace YouSaidItCards\Modules\HideProductsFromShop;

use YouSaidItCards\Modules\Designers\Models\CardDesigner;

// If this file is called directly, abort.
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
			add_action( 'save_post', [ self::$instance, 'save_product_meta' ], 10, 2 );
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
	public function save_product_meta( $post_id, $post ) {
		if ( isset( $_POST['_hide_from_shop'] ) ) {
			update_post_meta( $post_id, '_hide_from_shop', $_POST['_hide_from_shop'] );
		} elseif ( false !== get_post_meta( $post_id, '_hide_from_shop', true ) ) {
			delete_post_meta( $post_id, '_hide_from_shop' );
		}
		if ( 'product' == get_post_type( $post ) ) {
			delete_transient( 'hide_from_shop_products_ids' );
		}
	}

	/**
	 * Hide product from shop by product tag term
	 *
	 * @param \WP_Query $query
	 */
	public function custom_pre_get_posts_query( $query ) {
		if ( empty( $query->get( CardDesigner::PROFILE_ENDPOINT ) ) && static::is_woocommerce_page() ) {
			$ids          = static::get_hide_from_shop_products_ids();
			$existing_ids = (array) $query->get( 'post__not_in' );
			$new_ids      = array_filter( array_merge( $existing_ids, $ids ) );

			if ( count( $new_ids ) ) {
				$query->set( 'post__not_in', $new_ids );
			}
		}
	}

	/**
	 * Hide product from shortcode with custom meta value
	 *
	 * @param array $query_args
	 *
	 * @return array
	 */
	public function shortcode_products_query( array $query_args ): array {
		global $wp_query;
		if ( empty( $wp_query->get( CardDesigner::PROFILE_ENDPOINT ) ) ) {
			$query_args['meta_query'][] = [
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
	public static function is_woocommerce_page(): bool {
		return ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() );
	}

	/**
	 * Hide from shop products ids
	 *
	 * @return array
	 */
	public static function get_hide_from_shop_products_ids(): array {
		$ids = get_transient( 'hide_from_shop_products_ids' );
		if ( ! is_array( $ids ) ) {
			$ids = [];
			global $wpdb;
			$sql    = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hide_from_shop' AND meta_value = 'on'";
			$result = $wpdb->get_results( $sql, ARRAY_A );
			if ( is_array( $result ) && count( $result ) ) {
				$_ids = wp_list_pluck( $result, 'post_id' );
				$ids  = array_map( 'intval', $_ids );
			}

			set_transient( 'hide_from_shop_products_ids', $ids, YEAR_IN_SECONDS );
		}

		return $ids;
	}
}
