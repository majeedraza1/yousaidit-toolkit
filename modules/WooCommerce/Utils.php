<?php

namespace YouSaidItCards\Modules\WooCommerce;

use WP_Term;

class Utils {
	/**
	 * Read attribute data
	 *
	 * @param string|null $attribute_name
	 *
	 * @return array
	 */
	public static function get_size_attribute( ?string $attribute_name = null ): array {
		if ( empty( $attribute_name ) ) {
			$options        = (array) get_option( 'yousaiditcard_designers_settings' );
			$attribute_name = isset( $options['product_attribute_for_card_sizes'] ) ?
				$options['product_attribute_for_card_sizes'] : '';
		}

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			if ( $tax->attribute_name != $attribute_name ) {
				continue;
			}

			$taxonomy = wc_attribute_taxonomy_name( $attribute_name );

			/** @var WP_Term[] $terms */
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, ] );

			if ( ! is_wp_error( $terms ) ) {
				return $terms;
			}
		}

		return [];
	}

	public static function get_formatted_size_attribute( ?string $attribute_name = null ): array {
		$terms = static::get_size_attribute( $attribute_name );
		$data  = [];
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$data[] = [
				'id'          => $term->term_id,
				'slug'        => $term->slug,
				'name'        => $term->name,
				'description' => $term->description,
				'count'       => $term->count,
			];
		}

		return $data;
	}
}
