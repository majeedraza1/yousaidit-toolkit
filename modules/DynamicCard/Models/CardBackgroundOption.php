<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Abstracts\Data;

class CardBackgroundOption extends Data {

	public function __construct( $data = [] ) {
		parent::__construct( $data );

		$this->read_image_data();
	}

	public function get_image() {
		if ( ! $this->has( 'image_src' ) ) {
			$this->read_image_data();
		}

		return $this->get( 'image_src' );
	}

	/**
	 * Convert millimeters to pixels
	 *
	 * @param int|float $mm
	 *
	 * @return float
	 */
	public static function mm_to_px( float $mm ): float {
		return round( $mm * 3.7795275591 );
	}

	private function read_image_data(): void {
		if ( 'color' == $this->get( 'type', 'color' ) ) {
			$bg_color_image = add_query_arg( [
				'action' => 'yousaidit_color_image',
				'w'      => round( $this->get( 'card_width' ) / 2 ),
				'h'      => $this->get( 'card_height' ),
				'c'      => rawurlencode( $this->get( 'color', '#ffffff' ) )
			], admin_url( 'admin-ajax.php' ) );
			$this->set( 'image_src', $bg_color_image );
			$this->set( 'image_width', self::mm_to_px( $this->get( 'card_width' ) / 2 ) );
			$this->set( 'image_height', self::mm_to_px( $this->get( 'card_height' ) ) );
			$this->set( 'image_ext', 'png' );
		}

		if ( 'image' == $this->get( 'type', 'color' ) ) {
			$src = wp_get_attachment_image_src( $this->get( 'image', 0 ), 'full' );
			if ( is_array( $src ) ) {
				$this->set( 'image_src', $src[0] );
				$this->set( 'image_width', $src[1] );
				$this->set( 'image_height', $src[2] );
				$file_ext = explode( '.', basename( $src[0] ) );
				$this->set( 'image_ext', end( $file_ext ) );
			}
		}
	}
}
