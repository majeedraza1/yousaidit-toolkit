<?php

namespace YouSaidItCards\Utilities;

use WC_Product;
use WC_Product_Variable;

class WcUtils {

	/**
	 * Get settings
	 *
	 * @param null|WC_Product $wc_product
	 *
	 * @return array
	 */
	public static function get_settings( WC_Product $wc_product ): array {
		$bt_products = [];
		$bt_settings = (array) $wc_product->get_meta( '_stackonet_brought_together_settings', true );
		if ( isset( $bt_settings['products'] ) &&
		     ( is_array( $bt_settings['products'] ) && count( $bt_settings['products'] ) )
		) {
			$products_ids = wp_list_pluck( $bt_settings['products'], 'id' );
			$bt_products  = static::get_products( $products_ids, [ $wc_product->get_id() ] );
		}

		$pricing_rules = $wc_product->get_meta( '_stackonet_pricing_rules_settings', true );

		return [
			'product_id'    => $wc_product->get_id(),
			'product'       => static::format_product_data( $wc_product ),
			'pricing_rules' => $pricing_rules,
			'bt_products'   => array_values( $bt_products ),
		];
	}

	/**
	 * Get products
	 *
	 * @param array $ids
	 * @param array $exclude
	 *
	 * @return array
	 */
	public static function get_products( array $ids, array $exclude = [] ): array {
		$products = wc_get_products( [ 'include' => $ids ] );
		$data     = [];
		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			if ( count( $exclude ) && in_array( $product->get_id(), $exclude ) ) {
				continue;
			}
			if ( ! $product->is_purchasable() ) {
				continue;
			}
			$id          = $product->get_id();
			$data[ $id ] = static::format_product_data( $product );
		}

		return $data;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public static function format_product_data( WC_Product $product ): array {
		$image_id = (int) $product->get_image_id();
		$src      = wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' );
		$data     = [
			'id'         => $product->get_id(),
			'title'      => $product->get_title(),
			'price_html' => $product->get_price_html(),
			'type'       => $product->get_type(),
			'permalink'  => $product->get_permalink(),
			'thumbnail'  => is_array( $src ) ? $src[0] : wc_placeholder_img_src(),
		];

		if ( $product instanceof WC_Product_Variable ) {
			$attributes = [];
			foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {

				$_options = [];
				if ( $product && taxonomy_exists( $attribute_name ) ) {
					$terms = wc_get_product_terms( $product->get_id(), $attribute_name, [ 'fields' => 'all', ] );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$_options[ $term->slug ] = [ 'value' => $term->slug, 'label' => $term->name ];
						}
					}
				} else {
					foreach ( $options as $option ) {
						$_options[ $option ] = [ 'value' => $option, 'label' => $option ];
					}
				}

				$attributes[ $attribute_name ] = [
					'taxonomy'  => taxonomy_exists( $attribute_name ),
					'attribute' => $attribute_name,
					'label'     => wc_attribute_label( $attribute_name ),
					'options'   => $_options,
				];
			}

			$data['attributes']           = $attributes;
			$data['selected_attributes']  = $product->get_default_attributes();
			$data['available_variations'] = $product->get_available_variations();
		}

		return $data;
	}
}
