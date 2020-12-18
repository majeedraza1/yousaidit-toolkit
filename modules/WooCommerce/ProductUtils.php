<?php

namespace YouSaidItCards\Modules\WooCommerce;

use ArrayObject;
use WC_Product;
use WC_Product_Query;
use WP_Term;

class ProductUtils {
	/**
	 * Parse arguments
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function parse_args( array $args = [] ): array {
		if ( isset( $args['categories'] ) ) {
			$args['category'] = static::format_term_for_query( $args['categories'], 'product_cat' );
			unset( $args['categories'] );
		}
		if ( isset( $args['tags'] ) ) {
			$args['tag'] = static::format_term_for_query( $args['tags'], 'product_tag' );
			unset( $args['tags'] );
		}

		$args = wp_parse_args( $args, [
			'visibility' => 'catalog',
			'return'     => 'objects',
			// Pagination
			'paginate'   => false,
			'limit'      => 20,
			'page'       => 1,
			// Sorting
			'orderby'    => 'date',
			'order'      => 'DESC',
		] );

		return $args;
	}

	public static function get_products( array $args = [] ) {
		$args          = static::parse_args( $args );
		$product_query = new WC_Product_Query( $args );
		$result        = $product_query->get_products();
		$products      = [];
		/** @var WC_Product $product */
		foreach ( $result->products as $product ) {
			$products[] = static::format_product_for_response( $product );
		}

		return [
			'products'    => $products,
			'total_items' => $result->total,
			'total_pages' => $result->max_num_pages,
		];
	}

	/**
	 * @param int $image_id
	 *
	 * @return array
	 */
	public static function format_image_for_response( int $image_id ): array {
		$title    = get_the_title( $image_id );
		$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		$image_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ];
		$images      = [];
		foreach ( $image_sizes as $image_size ) {
			$image_src = wp_get_attachment_image_src( $image_id, $image_size );
			if ( ! is_array( $image_src ) ) {
				$images[ $image_size ] = new ArrayObject;
				continue;
			}

			$images[ $image_size ] = [
				'src'       => $image_src[0],
				'width'     => $image_src[1],
				'height'    => $image_src[2],
				'hard_crop' => $image_src[3],
				'title'     => $title,
				'alt'       => $alt_text,
			];
		}

		return $images;
	}

	/**
	 * Format term slug
	 *
	 * @param array  $tags
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	protected static function format_term_for_query( array $tags, string $taxonomy ): array {
		$ids = [];
		foreach ( $tags as $index => $tag ) {
			if ( is_numeric( $tag ) ) {
				$ids[] = intval( $tag );
				unset( $tags[ $index ] );
			}
		}
		if ( count( $ids ) ) {
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'include' => $ids ] );
			$slugs = wp_list_pluck( $terms, 'slug' );
			$tags  = array_merge( $slugs, array_values( $tags ) );
		}

		return $tags;
	}

	/**
	 * List all (or limited) product categories.
	 *
	 * @param array $args
	 *
	 * @return array|WP_Term[]
	 */
	public static function product_categories( $args = [] ): array {
		$args = wp_parse_args( $args, [
			'hide_empty' => true,
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
			'meta_key'   => 'order',
		] );

		$args['taxonomy'] = 'product_cat';

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return $terms;
	}

	public static function format_product_for_response( WC_Product $product ): array {
		$images = static::format_image_for_response( intval( $product->get_image_id() ) );

		return [
			'product_id'    => $product->get_id(),
			'title'         => $product->get_title(),
			'is_on_sale'    => $product->is_on_sale(),
			'regular_price' => wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] ),
			'sale_price'    => wc_get_price_to_display( $product ),
			'image'         => $images,
			'category_ids'  => $product->get_category_ids(),
			'tag_ids'       => $product->get_tag_ids(),
		];
	}
}
