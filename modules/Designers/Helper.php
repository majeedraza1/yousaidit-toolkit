<?php

namespace YouSaidItCards\Modules\Designers;

use Imagick;
use ImagickException;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use WC_Product;
use WP_Error;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

class Helper {

	/**
	 * Generate product image
	 *
	 * @param  Imagick  $imagick
	 * @param  DesignerCard  $card
	 * @param  bool  $delete_current
	 *
	 * @return int|WP_Error
	 */
	public static function generate_product_image( Imagick $imagick, DesignerCard $card, bool $delete_current = false ) {
		$upload_dir = Uploader::get_upload_dir();
		if ( is_wp_error( $upload_dir ) ) {
			return $upload_dir;
		}

		$product = wc_get_product( $card->get_product_id() );
		if ( $product instanceof WC_Product ) {
			$filename = strtolower( $product->get_slug() ) . '-' . uniqid() . '-shop-image.webp';
		} else {
			$filename = strtolower( $card->get_title() ) . '-' . uniqid() . '-shop-image.webp';
		}
		$filename      = wp_unique_filename( $upload_dir, sanitize_file_name( $filename ) );
		$directory     = rtrim( $upload_dir, DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		try {
			$imagick->setImageFormat( "webp" );
			$imagick->setOption( 'webp:method', '6' );
			$imagick->writeImage( $new_file_path );

			$upload_dir = wp_upload_dir();
			$data       = array(
				'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path ),
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/webp',
			);

			$attachment_id = wp_insert_attachment( $data, $new_file_path );

			if ( ! is_wp_error( $attachment_id ) ) {
				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file_path );
				wp_update_attachment_metadata( $attachment_id, $attach_data );

				if ( $product instanceof WC_Product ) {
					set_post_thumbnail( $product->get_id(), $attachment_id );
				}

				// Delete current image
				if ( $delete_current ) {
					wp_delete_attachment( $card->get_product_thumbnail_id(), true );
				}

				$card->set_prop( 'product_thumbnail_id', $attachment_id );
				$card->update();
			}

			return $attachment_id;
		} catch ( ImagickException $e ) {
			Logger::log( $e );

			return new WP_Error( 'imagick_error', $e->getMessage() );
		}
	}
}