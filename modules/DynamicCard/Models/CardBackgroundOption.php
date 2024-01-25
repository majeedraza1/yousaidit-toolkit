<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Abstracts\Data;

class CardBackgroundOption extends Data {

	/**
	 * Class constructor
	 *
	 * @param  mixed  $data  Object data.
	 */
	public function __construct( $data = [] ) {
		parent::__construct( $data );

		$this->read_image_data();
	}

	/**
	 * Get background color
	 *
	 * @return string
	 */
	public function get_type(): string {
		$type = $this->get_prop( 'type', 'color' );

		return in_array( $type, [ 'image', 'color' ], true ) ? $type : 'color';
	}

	/**
	 * Get image extension
	 *
	 * @return string
	 */
	public function get_image_extension(): string {
		return (string) $this->get_prop( 'image_ext' );
	}

	/**
	 * Get image source
	 *
	 * @return string
	 */
	public function get_image_src() {
		if ( ! $this->has_prop( 'image_src' ) ) {
			$this->read_image_data();
		}

		return $this->get_prop( 'image_src' );
	}

	/**
	 * Convert millimeters to pixels
	 *
	 * @param  int|float  $mm
	 *
	 * @return float
	 */
	private static function mm_to_px( float $mm ): float {
		return round( $mm * 3.7795275591 );
	}

	private function read_image_data(): void {
		if ( 'color' == $this->get_type() ) {
			$bg_color_image = add_query_arg( [
				'action' => 'yousaidit_color_image',
				'w'      => round( $this->get_prop( 'card_width' ) / 2 ),
				'h'      => $this->get_prop( 'card_height' ),
				'c'      => rawurlencode( $this->get_prop( 'color', '#ffffff' ) )
			], admin_url( 'admin-ajax.php' ) );
			$this->set_prop( 'image_src', $bg_color_image );
			$this->set_prop( 'image_width', self::mm_to_px( $this->get_prop( 'card_width' ) / 2 ) );
			$this->set_prop( 'image_height', self::mm_to_px( $this->get_prop( 'card_height' ) ) );
			$this->set_prop( 'image_ext', 'png' );
		}

		if ( 'image' == $this->get_type() ) {
			$src = wp_get_attachment_image_src( $this->get_prop( 'image', 0 ), 'full' );
			if ( is_array( $src ) ) {
				$this->set_prop( 'image_src', $src[0] );
				$this->set_prop( 'image_width', $src[1] );
				$this->set_prop( 'image_height', $src[2] );
				$file_ext = explode( '.', basename( $src[0] ) );
				$this->set_prop( 'image_ext', end( $file_ext ) );
			}
		}
	}
}
