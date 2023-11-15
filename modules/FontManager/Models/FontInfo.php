<?php

namespace YouSaidItCards\Modules\FontManager\Models;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\Validate;

/**
 * FontInfo class
 */
class FontInfo extends Data {
	protected static $default = [
		'slug'         => '',
		'font_family'  => '',
		'font_file'    => '',
		'group'        => '',
		'for_public'   => true,
		'for_designer' => true,
	];

	/**
	 * Get font unique slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return (string) $this->get_prop( 'slug' );
	}

	/**
	 * Get font family
	 *
	 * @return string
	 */
	public function get_font_family(): string {
		return (string) $this->get_prop( 'font_family' );
	}

	/**
	 * Get font group
	 *
	 * @return string
	 */
	public function get_font_group(): string {
		return (string) $this->get_prop( 'group' );
	}

	/**
	 * Get font family for DomPDF
	 *
	 * @return string
	 */
	public function get_font_family_for_dompdf(): string {
		return str_replace( ' ', '_', strtolower( $this->get_font_family() ) );
	}

	/**
	 * If it is available for public
	 *
	 * @return bool
	 */
	public function is_available_for_public(): bool {
		return Validate::checked( $this->get_prop( 'for_public' ) );
	}

	/**
	 * If it is available for designer
	 *
	 * @return bool
	 */
	public function is_available_for_designer(): bool {
		return Validate::checked( $this->get_prop( 'for_designer' ) );
	}

	/**
	 * Get font file name
	 *
	 * @return string
	 */
	public function get_font_file(): string {
		return (string) $this->get_prop( 'font_file' );
	}

	/**
	 * Get font path
	 *
	 * @return string
	 */
	public function get_font_path(): string {
		return join( DIRECTORY_SEPARATOR, [ static::get_base_directory(), $this->get_font_file() ] );
	}

	/**
	 * Get font url
	 *
	 * @return string
	 */
	public function get_font_url(): string {
		return str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $this->get_font_path() );
	}

	/**
	 * If the font file exists
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return file_exists( $this->get_font_path() );
	}

	/**
	 * Get font face css
	 *
	 * @return string
	 */
	public function font_face_css(): string {
		if ( ! $this->is_valid() ) {
			return '';
		}

		return sprintf( "@font-face { font-family: %s; src: url(%s) format('truetype'); font-weight: normal; font-style: normal;}",
			$this->get_font_family_for_dompdf(),
			$this->get_font_url()
		);
	}

	/**
	 * Get base directory
	 *
	 * @return string
	 */
	public static function get_base_directory(): string {
		return join( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, 'uploads', 'yousaidit-web-fonts' ] );
	}
}
