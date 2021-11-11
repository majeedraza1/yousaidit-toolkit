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
		$options = (array) $this->get( 'imageOptions' );
		if ( 'id' == $key ) {
			return $options['img']['id'];
		}

		return $options[ $key ] ?? '';
	}
}
