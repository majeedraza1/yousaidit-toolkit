<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

class CardSectionImageOption extends CardSectionBase {

	/**
	 * Get image id
	 *
	 * @return int
	 */
	public function get_image_id(): int {
		return (int) $this->get_image_option( 'id' );
	}

	/**
	 * @return array|false
	 */
	public function get_image() {
		return self::get_image_data( $this->get_image_id() );
	}

	/**
	 * Get image options
	 *
	 * @param  string  $key
	 *
	 * @return mixed
	 */
	public function get_image_option( string $key ) {
		$default = [ "img" => [ "id" => 0 ], "width" => 0, "height" => "auto", "align" => "left", "marginRight" => 10 ];
		$options = wp_parse_args( (array) $this->get_prop( 'imageOptions' ), $default );
		if ( 'id' == $key ) {
			return $options['img']['id'];
		}

		return $options[ $key ] ?? '';
	}

	/**
	 * Get user option
	 *
	 * @return array
	 */
	public function get_user_options(): array {
		$default = [ 'rotate' => 0, 'zoom' => 0, 'position' => [ 'top' => 0, 'left' => 0 ] ];
		$options = $this->get_prop( 'userOptions', $default );
		if ( is_array( $options ) ) {
			return wp_parse_args( $options, $default );
		}

		return $default;
	}

	/**
	 * Get rotation
	 *
	 * @return int
	 */
	public function get_user_rotation(): int {
		$options = $this->get_user_options();
		$rotate  = isset( $options['rotate'] ) ? intval( $options['rotate'] ) : 0;
		$rotate  = min( 360, max( 0, $rotate ) );

		// @TODO convert it to from css value

		return $rotate;
	}

	public function get_user_position_from_top(): int {
		$options = $this->get_user_options();
		$top     = isset( $options['position']['top'] ) ? intval( $options['position']['top'] ) : 0;

		return $top;
	}

	public function get_user_position_from_left(): int {
		$options = $this->get_user_options();
		$top     = isset( $options['position']['left'] ) ? intval( $options['position']['left'] ) : 0;

		return $top;
	}

	public function get_user_zoom(): float {
		$options = $this->get_user_options();
		$zoom    = isset( $options['zoom'] ) ? intval( $options['zoom'] ) : 0;

		return $zoom;
	}
}
