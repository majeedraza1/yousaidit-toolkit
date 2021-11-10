<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Abstracts\Data;

class CardSectionBase extends Data {
	public function get_position_from_top(): int {
		return (int) $this->get( 'position' )['top'];
	}

	public function get_position_from_left(): int {
		return (int) $this->get( 'position' )['left'];
	}

	/**
	 * @param int $id
	 *
	 * @return array|false
	 */
	public static function get_image_data( int $id ) {
		$path = get_attached_file( $id );
		$src  = wp_get_attachment_image_src( $id, 'full' );
		if ( ! is_array( $src ) ) {
			return false;
		}

		$file_ext = explode( '.', basename( $src[0] ) );
		$ext      = end( $file_ext );

		return [ 'path' => $path, 'url' => $src[0], 'width' => $src[1], 'height' => $src[2], 'ext' => $ext ];
	}
}
