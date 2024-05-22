<?php

namespace YouSaidItCards\Utilities;

class FreePdfBase {
	/**
	 * Card sizes
	 *
	 * @var array
	 */
	protected static $sizes = [
		'square' => [ 306, 156 ],
		'a4'     => [ 426, 303 ],
		'a5'     => [ 303, 216 ],
		'a6'     => [ 216, 154 ],
	];

	protected $layer_types = [ 'static-text', 'input-text', 'static-image', 'input-image' ];

	/**
	 * Card options
	 *
	 * @var \int[][]
	 */
	protected static $card_options = [
		'square' => [
			'width'         => 306,
			'height'        => 156,
			'front_width'   => 154,
			'back_width'    => 152,
			'top_margin'    => 3,
			'right_margin'  => 3,
			'bottom_margin' => 3,
			'left_margin'   => 1,
		]
	];

	/**
	 * First element is width, second is height
	 *
	 * @var array
	 */
	protected $size = [];

	/**
	 * Size name. e.g. square
	 *
	 * @var string
	 */
	protected $size_string = 'square';

	protected $background = [];
	protected $layers = [];

	/**
	 * @return array
	 */
	public static function get_sizes(): array {
		return self::$sizes;
	}

	/**
	 * @return array
	 */
	public function get_size(): array {
		if ( empty( $this->size ) ) {
			$this->size = self::$sizes[ $this->size_string ];
		}

		return $this->size;
	}

	/**
	 * Get card options
	 *
	 * @return int[]
	 */
	public function get_option(): array {
		return self::$card_options[ $this->size_string ];
	}

	/**
	 * Set size
	 *
	 * @param string|array $size
	 */
	public function set_size( $size ): void {
		if ( is_string( $size ) && array_key_exists( $size, self::$sizes ) ) {
			$this->size_string = $size;
			$this->size        = self::$sizes[ $size ];
		}
		if ( is_array( $size ) ) {
			$this->size = $size;
		}
	}

	/**
	 * Find RGB color from a color
	 *
	 * @param string $color
	 *
	 * @return string|array
	 */
	public static function find_rgb_color( string $color ) {
		if ( '' === $color ) {
			return '';
		}

		// Trim unneeded whitespace
		$color = str_replace( ' ', '', $color );

		// 3 or 6 hex digits, or the empty string.
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			// Format the hex color string.
			$hex = str_replace( '#', '', $color );

			if ( 3 == strlen( $hex ) ) {
				$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) .
				       str_repeat( substr( $hex, 1, 1 ), 2 ) .
				       str_repeat( substr( $hex, 2, 1 ), 2 );
			}

			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );

			return array( $r, $g, $b, 1 );
		}

		// If this is rgb color
		if ( 'rgb(' === substr( $color, 0, 4 ) ) {
			list( $r, $g, $b ) = sscanf( $color, 'rgb(%d,%d,%d)' );

			return array( $r, $g, $b, 1 );
		}

		// If this is rgba color
		if ( 'rgba(' === substr( $color, 0, 5 ) ) {
			list( $r, $g, $b, $alpha ) = sscanf( $color, 'rgba(%d,%d,%d,%f)' );

			return array( $r, $g, $b, $alpha );
		}

		return '';
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

	/**
	 * Convert pixels to millimeters
	 *
	 * @param float $px
	 *
	 * @return float
	 */
	public static function px_to_mm( float $px ): float {
		return round( $px * 0.2645833333 );
	}

	/**
	 * Convert points to millimeters
	 *
	 * @param float $points
	 *
	 * @return float
	 */
	public static function points_to_mm( float $points ): float {
		return round( $points * 0.352778 );
	}

	/**
	 * @return array
	 */
	public function get_background(): array {
		return $this->background;
	}

	/**
	 * @param array $background
	 */
	public function set_background( array $background ): void {
		$default = [ 'type' => 'color', 'color' => '#ffffff', 'image' => [ 'id' => 0 ] ];

		$this->background = wp_parse_args( $background, $default );
	}

	/**
	 * @return array
	 */
	public function get_layers(): array {
		return $this->layers;
	}

	/**
	 * @param array $layers
	 */
	public function set_layers( array $layers ): void {
		foreach ( $layers as $layer ) {
			$this->set_layer( $layer );
		}
	}

	/**
	 * Set layer data
	 *
	 * @param array $data
	 */
	public function set_layer( array $data ) {
		$type = $data['section_type'] ?? '';
		if ( ! in_array( $type, $this->layer_types, true ) ) {
			return;
		}
		$default = [ 'label' => '', 'section_type' => '', 'position' => [ 'top' => 0, 'left' => 0 ] ];;
		if ( in_array( $type, [ 'static-image', 'input-image' ], true ) ) {
			$default = array_merge( $default, [
				'imageOptions' => [
					'img'         => [ 'id' => 0 ],
					'width'       => 10,
					'height'      => 'auto',
					'align'       => 'left',
					'marginRight' => 0
				]
			] );
		}
		if ( in_array( $type, [ 'static-text', 'input-text' ], true ) ) {
			$default = array_merge( $default, [
				'text'        => '',
				'placeholder' => '',
				'textOptions' => [
					'fontFamily'  => 'Arial',
					'size'        => 16,
					'align'       => 'left',
					'color'       => '#323232',
					'marginRight' => 0
				]
			] );
		}
		$this->layers[] = wp_parse_args( $data, $default );
	}
}
