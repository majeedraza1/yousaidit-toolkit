<?php

namespace YouSaidItCards\Modules\FontManager;

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
	 * Get font info
	 *
	 * @param  array  $font_info  Font info array.
	 * @param  string  $group  Font group.
	 *
	 * @return array
	 */
	public static function get_font_info( array $font_info, string $group ): array {
		$base_dir = join( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, 'uploads', 'yousaidit-web-fonts' ] );
		$path     = join( DIRECTORY_SEPARATOR, [ $base_dir, $font_info['file_name'] ] );
		$url      = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );

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
			$list[] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'sans-serif' );
		}
		foreach ( static::_serif_fonts() as $slug => $font ) {
			$list[] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'serif' );
		}
		foreach ( static::_cursive_fonts() as $slug => $font ) {
			$list[] = static::get_font_info( array_merge( $font, [ 'slug' => $slug ] ), 'cursive' );
		}

		return $list;
	}

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

	public static function update_pre_installed_fonts_permissions( string $slug, array $data ) {
		$default = [ 'for_public' => true, 'for_designer' => true ];
		$data    = wp_parse_args( $data, $default );

		$options = get_option( 'pre_installed_fonts_permissions' );
		$options = is_array( $options ) ? $options : [];

		$options[ $slug ] = $data;

		update_option( 'pre_installed_fonts_permissions', $options, true );

		return $options;
	}
}
