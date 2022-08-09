<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Dompdf\Dompdf;
use Dompdf\Exception;
use FontLib\Font;
use YouSaidItCards\Utilities\Filesystem;

defined( 'ABSPATH' ) || die;

class Fonts {

	/**
	 * Installs a new font family
	 * This function maps a font-family name to a font.  It tries to locate the
	 * bold, italic, and bold italic versions of the font as well.  Once the
	 * files are located, ttf versions of the font are copied to the fonts
	 * directory.  Changes to the font lookup table are saved to the cache.
	 *
	 * @param string $fontName the font-family name
	 * @param string $normal the filename of the normal face font subtype
	 * @param string $bold the filename of the bold face font subtype
	 * @param string $italic the filename of the italic face font subtype
	 * @param string $bold_italic the filename of the bold italic face font subtype
	 *
	 * @throws Exception
	 */
	public static function install_font_family( $fontName, $normal, $bold = null, $italic = null, $bold_italic = null ) {
		$dompdf = new Dompdf();
		$dir    = Filesystem::get_uploads_dir( 'inner-message-fonts' );
		Filesystem::maybe_create_dir( $dir['path'] );
		// $dompdf->getOptions()->set( 'fontDir', $dir['path'] );
		$fontMetrics = $dompdf->getFontMetrics();

		// Check if the base filename is readable
		if ( ! is_readable( $normal ) ) {
			throw new Exception( "Unable to read '$normal'." );
		}

		$dir      = dirname( $normal );
		$basename = basename( $normal );
		$last_dot = strrpos( $basename, '.' );
		if ( $last_dot !== false ) {
			$file = substr( $basename, 0, $last_dot );
			$ext  = strtolower( substr( $basename, $last_dot ) );
		} else {
			$file = $basename;
			$ext  = '';
		}

		if ( ! in_array( $ext, array( ".ttf", ".otf" ) ) ) {
			throw new Exception( "Unable to process fonts of type '$ext'." );
		}

		// Try $file_Bold.$ext etc.
		$path = "$dir/$file";

		$patterns = array(
			"bold"        => array( "_Bold", "b", "B", "bd", "BD" ),
			"italic"      => array( "_Italic", "i", "I" ),
			"bold_italic" => array( "_Bold_Italic", "bi", "BI", "ib", "IB" ),
		);

		foreach ( $patterns as $type => $_patterns ) {
			if ( ! isset( $$type ) || ! is_readable( $$type ) ) {
				foreach ( $_patterns as $_pattern ) {
					if ( is_readable( "$path$_pattern$ext" ) ) {
						$$type = "$path$_pattern$ext";
						break;
					}
				}

				if ( is_null( $$type ) ) {
					echo( "Unable to find $type face file.\n" );
				}
			}
		}

		$fonts = compact( "normal", "bold", "italic", "bold_italic" );
		$entry = array();

		// Copy the files to the font directory.
		foreach ( $fonts as $var => $src ) {
			if ( is_null( $src ) ) {
				$entry[ $var ] = $dompdf->getOptions()->get( 'fontDir' ) . '/' . mb_substr( basename( $normal ), 0, - 4 );
				continue;
			}

			// Verify that the fonts exist and are readable
			if ( ! is_readable( $src ) ) {
				throw new Exception( "Requested font '$src' is not readable" );
			}

			$dest = $dompdf->getOptions()->get( 'fontDir' ) . '/' . basename( $src );

			if ( ! is_writeable( dirname( $dest ) ) ) {
				throw new Exception( "Unable to write to destination '$dest'." );
			}

			echo "Copying $src to $dest...\n";

			if ( ! copy( $src, $dest ) ) {
				throw new Exception( "Unable to copy '$src' to '$dest'" );
			}

			$entry_name = mb_substr( $dest, 0, - 4 );

			echo "Generating Adobe Font Metrics for $entry_name...\n";

			$font_obj = Font::load( $dest );
			$font_obj->saveAdobeFontMetrics( "$entry_name.ufm" );
			$font_obj->close();

			$entry[ $var ] = $entry_name;
		}

		// Store the fonts in the lookup table
		$fontMetrics->setFontFamily( $fontName, $entry );

		// Save the changes
		$fontMetrics->saveFontFamilies();
	}

	/**
	 * Get fonts list
	 *
	 * @return array
	 */
	public static function get_list(): array {
		$fonts                         = [];
		$fonts['OpenSans']             = static::get_font_info( 'Open Sans', 'sans-serif' );
		$fonts['JosefinSlab']          = static::get_font_info( 'Josefin Slab', 'serif' );
		$fonts['Prata']                = static::get_font_info( 'Prata', 'serif' );
		$fonts['IndieFlower']          = static::get_font_info( 'Indie Flower', 'cursive' );
		$fonts['AmaticSC']             = static::get_font_info( 'Amatic SC', 'cursive' );
		$fonts['Caveat']               = static::get_font_info( 'Caveat', 'cursive' );
		$fonts['CedarvilleCursive']    = static::get_font_info( 'Cedarville Cursive', 'cursive' );
		$fonts['FontdinerSwanky']      = static::get_font_info( 'Fontdiner Swanky', 'cursive' );
		$fonts['Handlee']              = static::get_font_info( 'Handlee', 'cursive' );
		$fonts['Kranky']               = static::get_font_info( 'Kranky', 'cursive' );
		$fonts['LoversQuarrel']        = static::get_font_info( 'Lovers Quarrel', 'cursive' );
		$fonts['MountainsofChristmas'] = static::get_font_info( 'Mountains of Christmas', 'cursive' );
		$fonts['Sacramento']           = static::get_font_info( 'Sacramento', 'cursive' );
		$fonts['NotoEmoji']            = static::get_font_info( 'Noto Emoji', 'sans-serif' );
		$fonts['BigMom']               = static::get_font_info( 'BigMom', 'sans-serif' );
		$fonts['Dekar']                = static::get_font_info( 'Dekar', 'sans-serif' );
		$fonts['EllieBellie']          = static::get_font_info( 'EllieBellie', 'cursive' );
		$fonts['Gagalin']              = static::get_font_info( 'Gagalin', 'sans-serif' );
		$fonts['Hatton']               = static::get_font_info( 'Hatton', 'sans-serif' );
		$fonts['JunkDog']              = static::get_font_info( 'JunkDog', 'sans-serif' );
		$fonts['LovileTypeBold']       = static::get_font_info( 'Lovile Type Bold', 'sans-serif' );
		$fonts['MoonFlower']           = static::get_font_info( 'Moon Flower', 'sans-serif' );
		$fonts['MoonFlowerBold']       = static::get_font_info( 'Moon Flower Bold', 'sans-serif' );
		$fonts['Simplicity']           = static::get_font_info( 'Simplicity', 'sans-serif' );
		$fonts['Sovereign']            = static::get_font_info( 'Sovereign', 'sans-serif' );
		$fonts['sunday']               = static::get_font_info( 'sunday', 'serif' );

		return $fonts;
	}

	/**
	 * Get list for the web
	 *
	 * @return array
	 */
	public static function get_list_for_web(): array {
		$items = [];
		foreach ( self::get_list() as $key => $item ) {
			$items[] = [ 'key' => $key, 'label' => $item['label'], 'fontUrl' => $item['fontUrl'] ];
		}

		return $items;
	}

	/**
	 * Get font info
	 *
	 * @param string $fontFamily
	 * @param string|null $group
	 *
	 * @return array
	 */
	public static function get_font_info( $fontFamily, $group = null ): array {
		$toArray = explode( ",", $fontFamily );
		if ( count( $toArray ) > 1 ) {
			$fontFamily = str_replace( [ "'", '"' ], '', $toArray[0] );
			$group      = $toArray[ count( $toArray ) - 1 ];
		}

		$path = static::get_font_path( $fontFamily );
		$url  = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );

		return [
			"label"        => $fontFamily,
			"fontFamily"   => sprintf( "'%s', %s", $fontFamily, $group ),
			"fileName"     => basename( $path ),
			"fontFilePath" => $path,
			"fontUrl"      => $url,
		];
	}

	/**
	 * Get font path
	 *
	 * @param string $fontFamily
	 *
	 * @return string
	 */
	public static function get_font_path( string $fontFamily ): string {
		$toArray    = explode( ",", $fontFamily );
		$fontFamily = str_replace( [ "'", '"' ], '', $toArray[0] );
		$file       = str_replace( " ", "", $fontFamily );
		$filename   = sprintf( "%s.ttf", $file );

		$in_content_dir = join( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, 'yousaidit-web-fonts', $filename ] );
		if ( file_exists( $in_content_dir ) ) {
			return $in_content_dir;
		}

		return join( '/', [ YOUSAIDIT_TOOLKIT_PATH . '/assets/web-fonts', $filename ] );
	}

	public static function tfpdf_clear_fonts_cache() {
		$base_path       = YOUSAIDIT_TOOLKIT_PATH . '/vendor/setasign/tfpdf/font/unifont/';
		$files           = scandir( $base_path );
		$sections_values = [];
		foreach ( $files as $file ) {
			if ( false !== strpos( $file, '.mtx.php' ) || false !== strpos( $file, '.cw.dat' ) ) {
				$sections_values[] = $file;
				unlink( join( '/', [ $base_path, $file ] ) );
			}
		}

		if ( count( $sections_values ) < 1 ) {
			return 'Fonts cache files are already clean. Nothing to clear';
		}

		return sprintf(
			_n(
				'%s cache file has been cleaned.',
				'%s cache files have been cleaned.',
				count( $sections_values ),
				'yousaidit-toolkit'
			),
			number_format_i18n( count( $sections_values ) )
		);
	}

	/**
	 * Get font face rules
	 *
	 * @return string
	 */
	public static function get_font_face_rules(): string {
		$fonts         = self::get_list();
		$js_fonts_list = [];

		$css = "<style id='yousaidit-inline-font-face-css' type='text/css'>";
		foreach ( $fonts as $key => $font ) {
			if ( 'NotoEmoji' === $key ) {
				continue;
			}

			$css .= '@font-face {' . PHP_EOL;
			$css .= sprintf(
				        "font-family: '%s'; font-style: normal; font-weight: 400;font-display: swap;",
				        $font['label']
			        ) . PHP_EOL;
			$css .= sprintf(
				        "src: url(%s) format('truetype');",
				        $font['fontUrl']
			        ) . PHP_EOL;
			$css .= '}' . PHP_EOL;

			$js_fonts_list[] = [
				'label'      => $font['label'],
				'fontFamily' => $font['fontFamily']
			];
		}
		$css .= '</style>' . PHP_EOL;
		$css .= "<script id='yousaidit-inline-font-face-js' type='text/javascript'>" . PHP_EOL;
		$css .= 'window.YousaiditFontsList = ' . wp_json_encode( $js_fonts_list ) . PHP_EOL;
		$css .= '</script>' . PHP_EOL;

		return $css;
	}
}
