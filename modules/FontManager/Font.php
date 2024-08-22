<?php

namespace YouSaidItCards\Modules\FontManager;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\Modules\FontManager\Models\DesignerFont;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;
use YouSaidItCards\Utilities\Filesystem;
use YouSaidItCards\Utils;

class Font {
	/**
	 * Get sans serif fonts
	 *
	 * @return array[]
	 */
	private static function _sans_serif_fonts(): array {
		return [
			'OpenSans'       => [ 'label' => 'Open Sans', 'file_name' => 'OpenSans.ttf' ],
			'OpenSansLight'  => [ 'label' => 'Open Sans Light', 'file_name' => 'OpenSansLight.ttf' ],
			'BigMom'         => [ 'label' => 'BigMom', 'file_name' => 'BigMom.ttf' ],
			'Dekar'          => [ 'label' => 'Dekar', 'file_name' => 'Dekar.ttf' ],
			'Gagalin'        => [ 'label' => 'Gagalin', 'file_name' => 'Gagalin.ttf' ],
			'Hatton'         => [ 'label' => 'Hatton', 'file_name' => 'Hatton.ttf' ],
			'JunkDog'        => [ 'label' => 'JunkDog', 'file_name' => 'JunkDog.ttf' ],
			'LovileTypeBold' => [ 'label' => 'Lovile Type Bold', 'file_name' => 'LovileTypeBold.ttf' ],
			'MoonFlower'     => [ 'label' => 'Moon Flower', 'file_name' => 'MoonFlower.ttf' ],
			'MoonFlowerBold' => [ 'label' => 'Moon Flower Bold', 'file_name' => 'MoonFlowerBold.ttf' ],
			'Simplicity'     => [ 'label' => 'Simplicity', 'file_name' => 'Simplicity.ttf' ],
			'Sovereign'      => [ 'label' => 'Sovereign', 'file_name' => 'Sovereign.ttf' ],
		];
	}

	/**
	 * Get serif fonts
	 *
	 * @return array[]
	 */
	private static function _serif_fonts(): array {
		return [
			'JosefinSlab' => [ 'label' => 'Josefin Slab', 'file_name' => 'JosefinSlab.ttf' ],
			'Prata'       => [ 'label' => 'Prata', 'file_name' => 'Prata.ttf' ],
			'sunday'      => [ 'label' => 'sunday', 'file_name' => 'sunday.ttf' ],
		];
	}

	/**
	 * Get cursive fonts
	 *
	 * @return array[]
	 */
	private static function _cursive_fonts(): array {
		return [
			'IndieFlower'          => [ 'label' => 'Indie Flower', 'file_name' => 'IndieFlower.ttf' ],
			'AmaticSC'             => [ 'label' => 'Amatic SC', 'file_name' => 'AmaticSC.ttf' ],
			'Caveat'               => [ 'label' => 'Caveat', 'file_name' => 'Caveat.ttf' ],
			'CedarvilleCursive'    => [ 'label' => 'Cedarville Cursive', 'file_name' => 'CedarvilleCursive.ttf' ],
			'FontdinerSwanky'      => [ 'label' => 'Fontdiner Swanky', 'file_name' => 'FontdinerSwanky.ttf' ],
			'Handlee'              => [ 'label' => 'Handlee', 'file_name' => 'Handlee.ttf' ],
			'Kranky'               => [ 'label' => 'Kranky', 'file_name' => 'Kranky.ttf' ],
			'LoversQuarrel'        => [ 'label' => 'Lovers Quarrel', 'file_name' => 'LoversQuarrel.ttf' ],
			'MountainsofChristmas' => [
				'label'     => 'Mountains of Christmas',
				'file_name' => 'MountainsofChristmas.ttf'
			],
			'Sacramento'           => [ 'label' => 'Sacramento', 'file_name' => 'Sacramento.ttf' ],
			'EllieBellie'          => [ 'label' => 'EllieBellie', 'file_name' => 'EllieBellie.ttf' ],
		];
	}

	/**
	 * Get base directory
	 *
	 * @return string
	 */
	public static function get_base_directory(): string {
		return join( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, 'uploads', 'yousaidit-web-fonts' ] );
	}

	/**
	 * Get font info
	 *
	 * @param  array  $font_info  Font info array.
	 * @param  string  $group  Font group.
	 *
	 * @return array
	 */
	public static function get_font_info( array $font_info, string $group ): array {
		$path = join( DIRECTORY_SEPARATOR, [ static::get_base_directory(), $font_info['file_name'] ] );
		$url  = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );

		return [
			'slug'        => $font_info['slug'],
			'font_family' => $font_info['label'],
			'font_file'   => $font_info['file_name'],
			'group'       => $group,
			"path"        => file_exists( $path ) ? $path : false,
			"url"         => file_exists( $path ) ? $url : false,
		];
	}

	/**
	 * Get pre-installed fonts list
	 *
	 * @return array
	 */
	public static function pre_installed_fonts(): array {
		$list = [];
		foreach ( static::_sans_serif_fonts() as $slug => $font ) {
			$list[ $slug ] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'sans-serif' );
		}
		foreach ( static::_serif_fonts() as $slug => $font ) {
			$list[ $slug ] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'serif' );
		}
		foreach ( static::_cursive_fonts() as $slug => $font ) {
			$list[ $slug ] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'cursive' );
		}

		return $list;
	}

	public static function is_pre_installed_font( string $slug ): bool {
		return array_key_exists( $slug, static::pre_installed_fonts() );
	}

	/**
	 * Get pre-installed fonts with permissions
	 *
	 * @return array
	 */
	public static function get_pre_installed_fonts_with_permissions(): array {
		$default    = [ 'for_public' => true, 'for_designer' => true ];
		$options    = get_option( 'pre_installed_fonts_permissions' );
		$options    = is_array( $options ) ? $options : [];
		$final_list = [];
		$fonts      = static::pre_installed_fonts();
		foreach ( $fonts as $font ) {
			if ( isset( $options[ $font['slug'] ] ) ) {
				$permission = wp_parse_args( $options[ $font['slug'] ], $default );
			} else {
				$permission = $default;
			}
			$final_list[] = array_merge( $font, $permission );
		}

		return $final_list;
	}

	/**
	 * Update pre-installed fonts permissions
	 *
	 * @param  string  $slug  font unique slug.
	 * @param  array  $data  Font data.
	 *
	 * @return array
	 */
	public static function update_pre_installed_fonts_permissions( string $slug, array $data ): array {
		$default = [ 'for_public' => true, 'for_designer' => true ];
		$data    = wp_parse_args( $data, $default );

		$options = get_option( 'pre_installed_fonts_permissions' );
		$options = is_array( $options ) ? $options : [];

		$options[ $slug ] = $data;

		update_option( 'pre_installed_fonts_permissions', $options, true );

		return $options;
	}

	public static function get_extra_font_default_args(): array {
		return [
			'slug'         => '',
			'font_family'  => '',
			'font_file'    => '',
			'group'        => '',
			'for_public'   => true,
			'for_designer' => true,
		];
	}

	/**
	 * Get extra font
	 *
	 * @return array[] {
	 * @type string $slug Font unique slug.
	 * @type string $font_family Font family name.
	 * @type string $font_file Font file name.
	 * @type string $group Font group.
	 * @type bool $for_public If it is available for public use.
	 * @type bool $for_designer If it is available for designer use.
	 * }
	 */
	public static function get_extra_fonts(): array {
		$options = get_option( 'extra_fonts_with_permissions' );
		if ( ! is_array( $options ) ) {
			return [];
		}
		$fonts = [];
		foreach ( $options as $font ) {
			$path = join( DIRECTORY_SEPARATOR, [ static::get_base_directory(), $font['font_file'] ] );
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$fonts[] = $font;
		}

		return $fonts;
	}

	public static function get_extra_fonts_with_path_and_url(): array {
		$fonts = [];
		foreach ( static::get_extra_fonts() as $font ) {
			$path    = join( DIRECTORY_SEPARATOR, [ static::get_base_directory(), $font['font_file'] ] );
			$url     = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );
			$fonts[] = array_merge( $font, [ 'path' => $path, 'url' => $url ] );
		}

		return $fonts;
	}

	public static function add_extra_font( array $data ): array {
		$fonts   = static::get_extra_fonts();
		$fonts[] = wp_parse_args( $data, static::get_extra_font_default_args() );
		update_option( 'extra_fonts_with_permissions', $fonts, true );

		return $fonts;
	}

	public static function update_extra_font_permission( string $slug, array $data ): array {
		$fonts = static::get_extra_fonts();
		$slugs = wp_list_pluck( $fonts, 'slug' );
		$index = array_search( $slug, $slugs, true );
		if ( false !== $index ) {
			if ( isset( $data['for_designer'] ) ) {
				$fonts[ $index ]['for_designer'] = $data['for_designer'];
			}
			if ( isset( $data['for_public'] ) ) {
				$fonts[ $index ]['for_public'] = $data['for_public'];
			}
		}
		update_option( 'extra_fonts_with_permissions', $fonts, true );

		return $fonts;
	}

	public static function delete_extra_font( string $slug ): array {
		$fonts = static::get_extra_fonts();
		$slugs = wp_list_pluck( $fonts, 'slug' );
		$index = array_search( $slug, $slugs, true );
		if ( false !== $index ) {
			$file_path = join( DIRECTORY_SEPARATOR, [ static::get_base_directory(), $fonts[ $index ]['font_file'] ] );
			$deleted   = Filesystem::get_filesystem()->delete( $file_path );
			if ( $deleted ) {
				unset( $fonts[ $index ] );
			}
		}
		update_option( 'extra_fonts_with_permissions', $fonts, true );

		return $fonts;
	}

	public static function get_fonts_with_permissions( int $user_id = 0 ): array {
		$pre_installed  = static::get_pre_installed_fonts_with_permissions();
		$extra_fonts    = static::get_extra_fonts_with_path_and_url();
		$designer_fonts = [];

		if ( $user_id ) {
			$designer_font_objects = DesignerFont::get_fonts( $user_id );
		} else {
			$designer_font_objects = DesignerFont::find_multiple( [ 'per_page' => - 1 ] );
		}
		foreach ( $designer_font_objects as $font ) {
			$designer_fonts[] = $font->to_array();
		}

		return array_merge( $pre_installed, $extra_fonts, $designer_fonts );
	}

	public static function get_fonts_for_designer( int $user_id = 0 ): array {
		$_fonts = static::get_fonts_with_permissions( $user_id );
		$fonts  = [];
		foreach ( $_fonts as $font ) {
			$_font = [
				'key'          => $font['slug'],
				'label'        => $font['font_family'],
				'fontUrl'      => $font['url'],
				'for_public'   => $font['for_public'],
				'for_designer' => $font['for_designer'],
			];
			if ( isset( $font['for_public'] ) && true === $font['for_public'] ) {
				$fonts[] = $_font;
				continue;
			}
			if ( isset( $font['for_designer'] ) && true === $font['for_designer'] ) {
				$fonts[] = $_font;
			}
		}

		return $fonts;
	}

	/**
	 * Get font face rules
	 *
	 * @return string
	 */
	public static function get_font_face_rules(): string {
		$fonts         = self::get_fonts_with_permissions();
		$js_fonts_list = [];

		$css = "<style id='yousaidit-inline-font-face-css' type='text/css'>" . PHP_EOL;
		foreach ( $fonts as $font ) {
			$css .= '@font-face {';
			$css .= sprintf(
				"font-family: '%s'; font-style: normal; font-weight: 400;font-display: swap;",
				$font['font_family']
			);
			$css .= sprintf(
				"src: url(%s) format('truetype');",
				$font['url']
			);
			$css .= '}' . PHP_EOL;

			$js_fonts_list[] = [
				'label'        => $font['font_family'],
				'fontFamily'   => $font['font_family'],
				'for_public'   => $font['for_public'],
				'for_designer' => $font['for_designer'],
			];
		}
		$css .= '</style>' . PHP_EOL;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$fonts_list = wp_json_encode( $js_fonts_list, \JSON_PRETTY_PRINT );
		} else {
			$fonts_list = wp_json_encode( $js_fonts_list );
		}

		$css .= "<script id='yousaidit-inline-font-face-js' type='text/javascript'>" . PHP_EOL;
		$css .= 'window.YousaiditFontsList = ' . $fonts_list . PHP_EOL;
		$css .= '</script>' . PHP_EOL;

		return $css;
	}

	public static function print_font_face_rules() {
		echo static::get_font_face_rules();
	}

	/**
	 * Get fonts info
	 *
	 * @return FontInfo[]
	 */
	public static function get_fonts_info(): array {
		$_fonts = static::get_fonts_with_permissions();
		$fonts  = [];
		foreach ( $_fonts as $font ) {
			$fonts[ $font['slug'] ] = new FontInfo( $font );
		}

		return $fonts;
	}

	/**
	 * @param  string  $font_family_or_slug
	 *
	 * @return false|FontInfo
	 */
	public static function find_font( string $font_family_or_slug ) {
		if ( 'arial' === $font_family_or_slug ) {
			$font_family_or_slug = 'OpenSans';
		}
		$toArray             = explode( ",", $font_family_or_slug );
		$font_family_or_slug = trim( str_replace( [ "'", '"' ], '', $toArray[0] ) );
		$_fonts              = static::get_fonts_info();
		if ( array_key_exists( $font_family_or_slug, $_fonts ) ) {
			return $_fonts[ $font_family_or_slug ];
		}

		foreach ( $_fonts as $font ) {
			if ( $font->get_font_family() === $font_family_or_slug ) {
				return $font;
			}
		}

		return false;
	}

	/**
	 * @param  string  $font_family_or_slug
	 *
	 * @return FontInfo|false
	 */
	public static function find_font_info( string $font_family_or_slug ) {
		return static::find_font( $font_family_or_slug );
	}


	/**
	 * Get font metrics
	 *
	 * @param  string  $font_family_or_slug  The font family.
	 * @param  int  $font_size  The font size.
	 * @param  string  $text  The string to test for font metrics.
	 *
	 * @return array|false {
	 * Array of font metrics info
	 *
	 * @type float $characterWidth maximum character ("em") width
	 * @type float $characterHeight maximum character height
	 * @type float $ascender the height of character ascensions (i.e. the straight bit on a 'b')
	 * @type float $descender the height of character descensions (i.e. the straight bit on a 'p')
	 * @type float $textWidth width of drawn text in pixels
	 * @type float $textHeight height of drawn text in pixels
	 * }
	 */
	public static function get_font_metrics(
		string $font_family_or_slug,
		int $font_size,
		string $text = '',
		int $resolution = 300
	) {
		if ( empty( $text ) ) {
			$text = 'A quick brown fox jumps over the lazy dogs.';
		}
		$font_info = static::find_font_info( $font_family_or_slug );
		try {
			$im = new Imagick();
			$im->setResolution( $resolution, $resolution );
			$draw = new ImagickDraw();
			$draw->setFont( $font_info->get_font_path() );
			$draw->setFontSize( Utils::font_size_pt_to_px( $font_size, $resolution ) );

			return $im->queryFontMetrics( $draw, $text );
		} catch ( ImagickDrawException|ImagickException $e ) {
			Logger::log( $e );

			return false;
		}
	}
}
