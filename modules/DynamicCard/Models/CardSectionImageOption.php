<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

class CardSectionImageOption extends CardSectionBase {
	public function get_image_id(): int {
		return (int) $this->get_image_option( 'id' );
	}

	public function get_image_option( string $key ) {
		$options = (array) $this->get( 'imageOptions' );
		if ( 'id' == $key ) {
			return $options['img']['id'];
		}

		return $options[ $key ] ?? '';
	}
}
