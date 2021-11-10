<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

class CardSectionTextOption extends CardSectionBase {

	/**
	 * Get text
	 *
	 * @return string
	 */
	public function get_text(): string {
		$placeholder = $this->get( 'placeholder' );
		$text        = $this->get( 'text' );

		return ! empty( $text ) ? $text : $placeholder;
	}
}
