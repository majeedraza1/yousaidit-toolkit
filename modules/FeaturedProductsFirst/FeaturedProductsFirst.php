<?php

namespace YouSaidItCards\Modules\FeaturedProductsFirst;

use WP_Query;

defined( 'ABSPATH' ) || die;

class FeaturedProductsFirst {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter( 'woocommerce_product_query', [ self::$instance, 'woocommerce_product_query' ], 99 );
		}

		return self::$instance;
	}

	/**
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public function woocommerce_product_query( $query ) {
		if ( $query->is_main_query() ) {
			add_filter( 'posts_clauses', array( $this, 'order_by_featured_products' ) );
		}

		return $query;
	}

	/**
	 * WP Core does not let us change the sort direction for individual orderby params
	 * - https://core.trac.wordpress.org/ticket/17065.
	 *
	 * This lets us sort by meta value desc, and have a second orderby param.
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	public function order_by_featured_products( $args ) {
		$orderby         = $this->get_order_by_sql() . " DESC, ";
		$args['orderby'] = $orderby . $args['orderby'];

		return $args;
	}

	/**
	 * Order by sql
	 *
	 * @return string
	 */
	public function get_order_by_sql() {
		global $wpdb;
		$featured_product_ids = wc_get_featured_product_ids();
		sort( $featured_product_ids );
		$orderby = "FIELD(" . $wpdb->posts . ".ID," . implode( ',', $featured_product_ids ) . ")";

		return $orderby;
	}
}
