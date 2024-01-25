<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Abstracts\Data;

class CardSectionBase extends Data {
	public function get_position_from_top(): int {
		return (int) $this->get_prop( 'position' )['top'];
	}

	public function get_position_from_left(): int {
		return (int) $this->get_prop( 'position' )['left'];
	}

	/**
	 * @param  int  $id
	 *
	 * @return array|false
	 */
	public static function get_image_data( int $id ) {
		$path = get_attached_file( $id );
		$src  = wp_get_attachment_image_src( $id, 'full' );
		if ( ! is_array( $src ) ) {
			return false;
		}
		list( $url, $width, $height ) = $src;
		if ( empty( $width ) || empty( $height ) ) {
			$size = getimagesize( $url );
			if ( is_array( $size ) ) {
				list( $width, $height ) = $size;
			}
		}

		$file_ext = explode( '.', basename( $url ) );
		$ext      = end( $file_ext );

		return [ 'path' => $path, 'url' => $url, 'width' => $width, 'height' => $height, 'ext' => $ext ];
	}
}
