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
	 * @param  string  $fontName  the font-family name
	 * @param  string  $normal  the filename of the normal face font subtype
	 * @param  string  $bold  the filename of the bold face font subtype
	 * @param  string  $italic  the filename of the italic face font subtype
	 * @param  string  $bold_italic  the filename of the bold italic face font subtype
	 *
	 * @throws Exception
	 */
	public static function install_font_family(
		$fontName,
		$normal,
		$bold = null,
		$italic = null,
		$bold_italic = null
	) {
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
				$entry[ $var ] = $dompdf->getOptions()->get( 'fontDir' ) . '/' . mb_substr( basename( $normal ), 0,
						- 4 );
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
	 * Get list for the web
	 *
	 * @return array
	 */
	public static function get_list_for_web(): array {
		$items = [];
		foreach ( \YouSaidItCards\Modules\FontManager\Font::get_fonts_info() as $font ) {
			$items[] = [
				'key'     => $font->get_slug(),
				'label'   => $font->get_font_family(),
				'fontUrl' => $font->get_font_url()
			];
		}

		return $items;
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
}
