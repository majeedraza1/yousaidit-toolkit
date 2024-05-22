<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

class CardSectionTextOption extends CardSectionBase {

	/**
	 * Get text
	 *
	 * @return string
	 */
	public function get_text(): string {
		$placeholder = $this->get_prop( 'placeholder' );
		$text        = $this->get_prop( 'text' );

		return ! empty( $text ) ? $text : $placeholder;
	}

	/**
	 * Get text options
	 *
	 * @param  string  $key
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
			"rotation"    => 0,
			"spacing"     => 0,
		];
		$options = wp_parse_args( $this->get_prop( 'textOptions', [] ), $default );

		return $options[ $key ] ?? '';
	}

	/**
	 * Get rotation
	 *
	 * @return int
	 */
	public function get_rotation(): int {
		return (int) $this->get_text_option( 'rotation' );
	}

	/**
	 * Get text spacing
	 *
	 * @return int
	 */
	public function get_text_spacing(): int {
		return (int) $this->get_text_option( 'spacing' );
	}

	/**
	 * Get font family
	 *
	 * @return string
	 */
	public function get_font_family(): string {
		return (string) $this->get_text_option( 'fontFamily' );
	}

	/**
	 * Get font size
	 *
	 * @return int
	 */
	public function get_font_size(): int {
		return (int) $this->get_text_option( 'size' );
	}
}
