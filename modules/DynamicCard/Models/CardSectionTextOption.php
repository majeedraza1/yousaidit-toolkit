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

	/**
	 * Get text options
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_text_option( string $key ) {
		$default = [
			"fontFamily"  => "arial",
			"size"        => 16,
			"align"       => "left",
			"color"       => "#000000",
			"marginRight" => 10,
		];
		$options = wp_parse_args( $this->get( 'textOptions', [] ), $default );

		return $options[ $key ] ?? '';
	}
}
