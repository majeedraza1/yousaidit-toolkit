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
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_image_option( string $key ) {
		$default = [ "img" => [ "id" => 0 ], "width" => 0, "height" => "auto", "align" => "left", "marginRight" => 10 ];
		$options = wp_parse_args( (array) $this->get( 'imageOptions' ), $default );
		if ( 'id' == $key ) {
			return $options['img']['id'];
		}

		return $options[ $key ] ?? '';
	}
}
