<?php

namespace YouSaidItCards\Modules\Designers\Models;

use Stackonet\WP\Framework\Media\Uploader;
use WC_Data_Exception;
use WC_Product_Attribute;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class CardToProduct {
	/**
	 * @param  int|DesignerCard  $card_id
	 * @param  array  $args
	 *
	 * @return int|WP_Error
	 * @throws WC_Data_Exception
	 */
	public static function create( $card_id, array $args = [] ) {
		$args = wp_parse_args( $args, [
			'product_sku'   => [],
			'product_price' => [],
		] );

		$item = null;

		if ( $card_id instanceof DesignerCard ) {
			$item = $card_id;
		}

		if ( is_numeric( $card_id ) ) {
			$item = ( new DesignerCard() )->find_by_id( $card_id );
		}

		if ( ! $item instanceof DesignerCard ) {
			return new WP_Error( 'card_not_found', 'No card found with card id.' );
		}

		// If product already has product, return its id
		if ( $item->get_product_id() ) {
			return $item->get_product_id();
		}

		if ( count( $item->get( 'card_sizes' ) ) > 1 ) {
			return static::create_variable_product( $item, $args );
		}

		return static::create_simple_product( $item, $args );
	}

	/**
	 * @param  DesignerCard  $designer_card
	 * @param  array  $args
	 *
	 * @return int
	 * @throws WC_Data_Exception
	 */
	private static function create_simple_product( DesignerCard $designer_card, array $args = [] ): int {
		$card_sizes = $designer_card->get( 'card_sizes' );
		$card_size  = $card_sizes[0];

		$_sku = is_array( $args['product_sku'] ) ? $args['product_sku'] : [];
		$sku  = isset( $_sku[ $card_size ] ) ? sanitize_text_field( $_sku[ $card_size ] ) : '';

		$_prices = is_array( $args['product_price'] ) ? $args['product_price'] : [];
		$prices  = isset( $_prices[ $card_size ] ) ? floatval( $_prices[ $card_size ] ) : 0;

		$product = new WC_Product_Simple( 0 );

		$product->set_regular_price( $prices );
		$product->set_sku( $sku );

		$options       = (array) get_option( 'yousaiditcard_designers_settings' );
		$default_title = $options['default_product_title'] ?? '';
		$default_title = str_replace( "{{card_title}}", $designer_card->get( 'card_title' ), $default_title );
		$product->set_name( wp_filter_post_kses( $default_title ) );

		$default_content = $options['default_product_content'] ?? '';
		$default_content = str_replace( "{{card_title}}", $designer_card->get( 'card_title' ), $default_content );
		$default_content = str_replace( "{{card_description}}", $designer_card->get( 'description' ),
			$default_content );
		$product->set_description( wp_filter_post_kses( $default_content ) );

		$product->set_status( 'draft' );

		$product->set_category_ids( $designer_card->get( 'categories_ids' ) );
		$product->set_tag_ids( $designer_card->get( 'tags_ids' ) );

		$image_id = $designer_card->get_image_id();
		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}

		$attributes = [];

		// Add product attribute
		$_attributes = $designer_card->get_attributes();
		foreach ( $_attributes as $_attribute ) {
			$taxonomy  = wc_attribute_taxonomy_name( $_attribute['attribute_name'] );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $_attribute['attribute_id'] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( wp_list_pluck( $_attribute['options'], 'id' ) );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[] = $attribute;
		}

		// Add size attribute
		$card_size_attr = CardSizeAttribute::init()->get_attribute();

		$size_ids = [];
		foreach ( $designer_card->get( 'card_sizes' ) as $_size ) {
			$term       = get_term_by( 'slug', $_size, $card_size_attr->get_taxonomy() );
			$size_ids[] = $term->term_id;
		}

		$card_size_attr->set_options( $size_ids );
		$card_size_attr->set_visible( false );
		$card_size_attr->set_variation( false );

		$attributes[] = $card_size_attr;

		$product->set_attributes( $attributes );

		// Add card metadata
		$product->add_meta_data( '_card_id', $designer_card->get_id() );
		$product->add_meta_data( '_card_size', $card_size );
		$product->add_meta_data( '_card_designer_id', $designer_card->get_designer_user_id() );
		$commission = $designer_card->get_commission_for_size( $card_size );
		$product->add_meta_data( '_card_designer_commission', is_numeric( $commission ) ? $commission : 0 );
		$product->add_meta_data( '_is_rude_card', $designer_card->is_rude_card() ? 'yes' : 'no' );
		$product->add_meta_data( '_card_type', $designer_card->get_card_type() );
		$pdf_id = $designer_card->get_pdf_id_for_size( $card_size );
		if ( $designer_card->is_static_card() && $pdf_id ) {
			$product->add_meta_data( '_pdf_id', $pdf_id );
		}
		if ( $designer_card->is_dynamic_card() ) {
			$product->add_meta_data( '_dynamic_card_payload', $designer_card->get_dynamic_card_payload() );
		}
		$product->save_meta_data();

		$product_id = $product->save();

		if ( $designer_card->is_static_card() && empty( $pdf_id ) ) {
			$pdf_id = self::generatePdfFromImage( $designer_card, $sku );
			$product->add_meta_data( '_pdf_id', $pdf_id );
			$product->save_meta_data();
		}

		// Update item product id
		$designer_card->update( [ 'id' => $designer_card->get( 'id' ), 'product_id' => $product_id ] );

		return $product_id;
	}

	/**
	 * @param  DesignerCard  $designer_card
	 * @param  array  $args
	 *
	 * @return int
	 */
	private static function create_variable_product( DesignerCard $designer_card, array $args = [] ): int {
		$_sku    = is_array( $args['product_sku'] ) ? $args['product_sku'] : [];
		$_prices = is_array( $args['product_price'] ) ? $args['product_price'] : [];

		$product = new WC_Product_Variable( 0 );

		$product->set_regular_price( '' );
		$product->set_sale_price( '' );
		$product->set_date_on_sale_to( '' );
		$product->set_date_on_sale_from( '' );
		$product->set_price( '' );

		$options       = (array) get_option( 'yousaiditcard_designers_settings' );
		$default_title = $options['default_product_title'] ?? '';
		$default_title = str_replace( "{{card_title}}", $designer_card->get_title(), $default_title );
		$product->set_name( wp_filter_post_kses( $default_title ) );

		$default_content = $options['default_product_content'] ?? '';
		$default_content = str_replace( "{{card_title}}", $designer_card->get_title(), $default_content );
		$product->set_description( wp_filter_post_kses( $default_content ) );

		$product->set_status( 'draft' );

		$product->set_category_ids( $designer_card->get_prop( 'categories_ids' ) );
		$product->set_tag_ids( $designer_card->get_prop( 'tags_ids' ) );

		$product->set_image_id( $designer_card->get_product_thumbnail_id() );

		$gallery_ids = $designer_card->get_gallery_images_ids();
		if ( $gallery_ids ) {
			$product->set_gallery_image_ids( $gallery_ids );
		}

		$attributes = [];

		// Add product attribute
		$_attributes = $designer_card->get_attributes();
		foreach ( $_attributes as $_attribute ) {
			$taxonomy  = wc_attribute_taxonomy_name( $_attribute['attribute_name'] );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $_attribute['attribute_id'] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( wp_list_pluck( $_attribute['options'], 'id' ) );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[] = $attribute;
		}

		// Add size attribute
		$card_size_attr = CardSizeAttribute::init()->get_attribute();

		$size_ids = [];
		foreach ( $designer_card->get_prop( 'card_sizes' ) as $_size ) {
			$term       = get_term_by( 'slug', $_size, $card_size_attr->get_taxonomy() );
			$size_ids[] = $term->term_id;
		}

		$card_size_attr->set_options( $size_ids );
		$card_size_attr->set_visible( false );
		$card_size_attr->set_variation( true );

		$attributes[] = $card_size_attr;

		$product->set_attributes( $attributes );

		// Add card metadata
		$product->add_meta_data( '_card_id', $designer_card->get_id() );
		$product->add_meta_data( '_card_designer_id', $designer_card->get_designer_user_id() );
		$product->add_meta_data( '_is_rude_card', $designer_card->is_rude_card() ? 'yes' : 'no' );
		$product->add_meta_data( '_card_type', $designer_card->get_card_type() );
		if ( $designer_card->is_dynamic_card() ) {
			$product->add_meta_data( '_dynamic_card_payload', $designer_card->get_dynamic_card_payload() );
		}

		$product->save_meta_data();

		$product_id = $product->save();

		// Add variation
		foreach ( $designer_card->get_prop( 'card_sizes' ) as $size ) {
			try {
				$variation_sku = isset( $_sku[ $size ] ) ? sanitize_text_field( $_sku[ $size ] ) : '';
				$_attrs        = [ $card_size_attr->get_name() => $size ];
				$variation     = new WC_Product_Variation();
				$variation->set_parent_id( $product_id );
				$variation->set_regular_price( isset( $_prices[ $size ] ) ? floatval( $_prices[ $size ] ) : 0 );
				$variation->set_sku( $variation_sku );
				$variation->set_attributes( $_attrs );

				$commission = $designer_card->get_commission_for_size( $size );
				$variation->add_meta_data( '_card_size', $size );
				$variation->add_meta_data( '_card_designer_commission', is_numeric( $commission ) ? $commission : 0 );
				$pdf_id = $designer_card->get_pdf_id_for_size( $size );
				if ( $designer_card->is_static_card() ) {
					if ( $pdf_id ) {
						$variation->add_meta_data( '_pdf_id', $pdf_id );
					} else {
						$pdf_id = self::generatePdfFromImage( $designer_card, $variation_sku );
						$variation->add_meta_data( '_pdf_id', $pdf_id );
					}
				}
				$variation->save_meta_data();

				$variation->save();
			} catch ( WC_Data_Exception $e ) {
			}
		}

		// Update item product id
		$designer_card->set_prop( 'product_id', $product_id );
		$designer_card->update();

		return $product_id;
	}

	/**
	 * Generate PDF from Image
	 *
	 * @param  DesignerCard  $designer_card
	 * @param  string|null  $sku
	 *
	 * @return int
	 */
	public static function generatePdfFromImage( DesignerCard $designer_card, ?string $sku = '' ): int {
		$image = $designer_card->get_image();
		if ( empty( $image['path'] ) ) {
			return 0;
		}
		$fpd = new StaticCardGenerator();
		$fpd->set_background_image( $image['url'] );
		if ( ! empty( $sku ) ) {
			$fpd->set_product_sku( $sku );
		}

		$upload_dir = Uploader::get_upload_dir();
		if ( ! is_wp_error( $upload_dir ) ) {
			$filename      = wp_unique_filename( $upload_dir, sprintf( "%s.pdf", strtoupper( $sku ) ) );
			$directory     = rtrim( $upload_dir, DIRECTORY_SEPARATOR );
			$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

			$fpd->pdf( [ 'dest' => 'F', 'name' => $new_file_path ] );
		}

		return $fpd->get_pdf_id();
	}
}
