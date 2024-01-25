<?php

namespace YouSaidItCards\Modules\Designers;

use finfo;
use Imagick;
use ImagickException;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use WP_Error;
use YouSaidItCards\FreePdf;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\DynamicCard\EnvelopeColours;

class DynamicCard {
	const LAYER_TYPE_INPUT_IMAGE = 'input-image';
	const LAYER_TYPE_STATIC_IMAGE = 'static-image';
	const LAYER_TYPE_INPUT_TEXT = 'input-text';
	const LAYER_TYPE_STATIC_TEXT = 'static-text';
	const LAYER_TYPES = [
		self::LAYER_TYPE_INPUT_IMAGE,
		self::LAYER_TYPE_STATIC_IMAGE,
		self::LAYER_TYPE_INPUT_TEXT,
		self::LAYER_TYPE_STATIC_TEXT
	];

	public static function sanitize_card_payload( array $payload ): array {
		$sanitized = [
			'card_background' => $payload['card_background'] ?? [],
			'card_bg_color'   => $payload['card_bg_color'] ?? '#ffffff',
			'card_bg_type'    => $payload['card_bg_type'] ?? 'color',
			'card_size'       => $payload['card_size'] ?? 'square',
			'card_items'      => [],
		];
		$items     = isset( $payload['card_items'] ) && is_array( $payload['card_items'] ) ? $payload['card_items'] : [];
		foreach ( $items as $index => $card_item ) {
			if ( is_array( $card_item ) ) {
				$sanitized['card_items'][ $index ] = static::sanitize_dynamic_card_layer( $card_item );
			}
		}

		return $sanitized;
	}

	public static function sanitize_dynamic_card_layer( array $payload ): array {
		$defaults   = [
			'section_type' => 'unknown',
			'label'        => 'unknown',
			'position'     => [ 'left' => 15, 'top' => 15 ],
		];
		$payload    = array_merge( $defaults, $payload );
		$type_text  = [ self::LAYER_TYPE_INPUT_TEXT, self::LAYER_TYPE_STATIC_TEXT ];
		$type_image = [ self::LAYER_TYPE_INPUT_IMAGE, self::LAYER_TYPE_STATIC_IMAGE ];

		$sanitized = [
			'section_type' => $payload['section_type'],
			'label'        => $payload['label'],
			'position'     => [
				'top'  => floatval( $payload['position']['top'] ),
				'left' => floatval( $payload['position']['left'] ),
			],
		];
		if ( in_array( $payload['section_type'], $type_text, true ) ) {
			$sanitized = array_merge( $sanitized, static::sanitize_dynamic_card_text_layer( $payload ) );
		}

		if ( in_array( $payload['section_type'], $type_image, true ) ) {
			$sanitized = array_merge( $sanitized, static::sanitize_dynamic_card_image_layer( $payload ) );
		}

		return $sanitized;
	}

	public static function sanitize_dynamic_card_text_layer( array $payload ): array {
		$textOptions = isset( $payload['textOptions'] ) && is_array( $payload['textOptions'] ) ? $payload['textOptions'] : [];

		return [
			'placeholder' => $payload['placeholder'] ?? '',
			'text'        => $payload['text'] ?? '',
			'textOptions' => [
				'fontFamily' => $textOptions['fontFamily'] ?? '',
				'size'       => isset( $textOptions['size'] ) ? intval( $textOptions['size'] ) : 16,
				'rotation'   => isset( $textOptions['rotation'] ) ? intval( $textOptions['rotation'] ) : 0,
				'spacing'    => isset( $textOptions['spacing'] ) ? intval( $textOptions['spacing'] ) : 0,
				'align'      => $textOptions['align'] ?? 'left',
				'color'      => $textOptions['color'] ?? '#000000',
			],
		];
	}

	public static function sanitize_dynamic_card_image_layer( array $payload ): array {
		$imageOptions  = isset( $payload['imageOptions'] ) && is_array( $payload['imageOptions'] ) ? $payload['imageOptions'] : [];
		$sourceImage   = isset( $imageOptions['img'] ) && is_array( $imageOptions['img'] ) ? $imageOptions['img'] : [];
		$sourceImageId = $sourceImage['id'] ? intval( $sourceImage['id'] ) : 0;
		$src           = wp_get_attachment_image_src( $sourceImageId, 'full' );
		if ( is_array( $src ) ) {
			list( $url, $width, $height ) = $src;
			$image = [ 'id' => $sourceImageId, 'src' => $url, 'width' => $width, 'height' => $height ];
		} else {
			$image = [ 'id' => 0, 'src' => '', 'width' => 0, 'height' => 0 ];
		}
		$sanitized = [
			'imageOptions' => [
				'align'  => $imageOptions['align'] ?? 'left',
				'width'  => $imageOptions['width'] ? intval( $imageOptions['width'] ) : 0,
				'height' => $imageOptions['height'] ? intval( $imageOptions['height'] ) : 0,
				'img'    => $image
			],
			'image'        => new \ArrayObject()
		];
		if ( self::LAYER_TYPE_INPUT_IMAGE == $payload['section_type'] ) {
			$userImage   = isset( $payload['image'] ) && is_array( $payload['image'] ) ? $payload['image'] : [];
			$userImageId = isset( $userImage['id'] ) ? intval( $userImage['id'] ) : 0;
			$src         = wp_get_attachment_image_src( $userImageId, 'full' );
			if ( is_array( $src ) ) {
				list( $url, $width, $height ) = $src;
				$sanitized['image'] = [ 'id' => $userImageId, 'src' => $url, 'width' => $width, 'height' => $height ];
			}
		}

		$userOptions              = isset( $payload['userOptions'] ) && is_array( $payload['userOptions'] ) ? $payload['userOptions'] : [];
		$sanitized['userOptions'] = [
			'rotate'   => isset( $userOptions['rotate'] ) ? intval( $userOptions['rotate'] ) : 0,
			'zoom'     => isset( $userOptions['zoom'] ) ? intval( $userOptions['zoom'] ) : 0,
			'position' => [
				'top'  => isset( $userOptions['position']['top'] ) ? intval( $userOptions['position']['top'] ) : 0,
				'left' => isset( $userOptions['position']['left'] ) ? intval( $userOptions['position']['left'] ) : 0,
			],
		];

		return $sanitized;
	}

	public static function get_pdf_id( int $card_id, string $meta_key ): int {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key,
			$card_id );
		$item = $wpdb->get_row( $sql, ARRAY_A );

		return isset( $item['post_id'] ) ? intval( $item['post_id'] ) : 0;
	}

	/**
	 * @param  DesignerCard  $card
	 * @param  bool  $regenerate
	 *
	 * @return int
	 */
	public static function create_card_pdf( DesignerCard $card, bool $regenerate = false ) {
		if ( ! $card->is_dynamic_card() ) {
			return false;
		}
		$pdf_id    = self::get_pdf_id( $card->get_id(), '_dynamic_card_id_for_pdf' );
		$file_path = get_attached_file( $pdf_id );
		if ( is_string( $file_path ) && file_exists( $file_path ) ) {
			if ( false === $regenerate ) {
				return $pdf_id;
			}

			wp_delete_attachment( $pdf_id, true );
		}
		$payload = $card->get_dynamic_card_payload();

		$background = [
			'type'  => $payload['card_bg_type'],
			'color' => str_replace( '"', '', $payload['card_bg_color'] ),
			'image' => $payload['card_background']
		];

		$directory     = rtrim( Uploader::get_upload_dir(), DIRECTORY_SEPARATOR );
		$filename      = sprintf( "dynamic-card-%s-%s.pdf", $card->get_id(), uniqid() );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		if ( ! file_exists( $new_file_path ) ) {
			$pdf = new FreePdf();
			$pdf->generate( $payload['card_size'], $payload['card_items'], $background, [
				'dest' => 'F',
				'name' => $new_file_path,
			] );
			if ( file_exists( $new_file_path ) ) {
				$stat  = stat( dirname( $new_file_path ) );
				$perms = $stat['mode'] & 0000666;
				@chmod( $new_file_path, $perms );

				$post_id = self::add_attachment_metadata( $new_file_path );
				update_post_meta( $post_id, '_dynamic_card_id_for_pdf', $card->get_id() );

				$sizes   = (array) $card->get( 'card_sizes', [] );
				$pdf_ids = [];
				foreach ( $sizes as $size ) {
					$pdf_ids[ $size ] = [ $post_id ];
				}
				$card->set( 'attachment_ids', array_merge( $card->get_attachment_ids(), [ 'pdf_ids' => $pdf_ids ] ) );
				$card->update();

				return $post_id;
			}
		}

		return 0;
	}

	/**
	 * @param  string  $pdf_file_path
	 * @param  int  $resolution
	 * @param  bool  $envelop
	 *
	 * @return Imagick
	 * @throws ImagickException
	 */
	public static function pdf_to_image( string $pdf_file_path, int $resolution = 72, bool $envelop = true ): Imagick {
		$content = file_get_contents( $pdf_file_path );
		$im      = new Imagick();
		$im->setResolution( $resolution, $resolution );
		$im->readImageBlob( $content . '[0]' );    //[0] for the first page
		$im->setImageFormat( 'jpg' );

		if ( $envelop ) {
			return EnvelopeColours::generate_thumb( $im, $resolution );
		}

		return $im;

	}

	/**
	 * Clone PDF to JPG
	 *
	 * @param  DesignerCard  $card
	 * @param  string  $pdf_file_path
	 *
	 * @return string
	 */
	public static function clone_pdf_to_jpg( DesignerCard $card, string $pdf_file_path ): string {
		$card_id       = $card->get_id();
		$image_id      = self::get_pdf_id( $card_id, '_dynamic_card_id_for_image' );
		$img_file_path = get_attached_file( $image_id );
		if ( is_string( $img_file_path ) && file_exists( $img_file_path ) ) {
			return $img_file_path;
		}

		$upload_dir = Uploader::get_upload_dir();
		$new_file   = join( "/", [ $upload_dir, sprintf( "dynamic-card-%s-%s.jpg", $card_id, uniqid() ) ] );
		try {
			$im = self::pdf_to_image( $pdf_file_path );
			$im->writeImage( $new_file );

			$stat  = stat( dirname( $new_file ) );
			$perms = $stat['mode'] & 0000666;
			@chmod( $new_file, $perms );

			$post_id = self::add_attachment_metadata( $new_file );
			update_post_meta( $post_id, '_dynamic_card_id_for_image', $card_id );

			$card->set( 'attachment_ids', array_merge( $card->get_attachment_ids(), [ 'image_id' => $post_id ] ) );
			$card->update();

			return get_attached_file( $post_id );
		} catch ( ImagickException $e ) {
			Logger::log( $e );
		}

		return $new_file;
	}

	/**
	 * Generate attachment metadata
	 *
	 * @param  string  $file_path
	 *
	 * @return int|WP_Error
	 */
	public static function add_attachment_metadata( string $file_path ) {
		$mime_type  = ( new finfo )->file( $file_path, FILEINFO_MIME_TYPE );
		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_text_field( basename( $file_path ) ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => $mime_type,
		];

		$attachment_id = wp_insert_attachment( $data, $file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		}

		return $attachment_id;
	}
}
