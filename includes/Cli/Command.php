<?php

namespace YouSaidItCards\Cli;

use Dompdf\Exception;
use Stackonet\WP\Framework\Supports\Logger;
use WP_CLI;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\InnerMessage\Fonts;

class Command {
	/**
	 * generate fonts metrics for Dompdf
	 *
	 * @subcommand dompdf_install_font
	 */
	public function dompdf_install_font() {
		foreach ( Font::get_fonts_info() as $item ) {
			$path = $item->get_font_path();
			if ( ! file_exists( $path ) ) {
				WP_CLI::line( 'Font file not found: ' . $path );
			}

			try {
				Fonts::install_font_family( $item->get_font_family_for_dompdf(), $path, $path, $path, $path );
				WP_CLI::line( 'Font file generated successfully for font: ' . $item->get_font_family_for_dompdf() );
			} catch ( Exception $e ) {
				Logger::log( $e );
			}
		}
		WP_CLI::success( 'All operation done.' );
	}

	/**
	 * Generate tFPDF unicode fonts
	 *
	 * @subcommand tfpdf_install_font
	 */
	public function tfpdf_install_font() {
		$base_path = YOUSAIDIT_TOOLKIT_PATH . '/vendor/setasign/tfpdf/font/unifont/';
		foreach ( Font::get_fonts_info() as $font_key => $font ) {
			copy( $font->get_font_path(), $base_path . $font->get_font_file() );
		}
	}

	/**
	 * Clear tFPDF unicode fonts file cache
	 *
	 * @subcommand tfpdf_clear_font_cache
	 */
	public function tfpdf_clear_font_cache() {
		$base_path       = YOUSAIDIT_TOOLKIT_PATH . '/vendor/setasign/tfpdf/font/unifont/';
		$files           = scandir( $base_path );
		$sections_values = [];
		foreach ( $files as $file ) {
			if ( false !== strpos( $file, '.mtx.php' ) || false !== strpos( $file, '.cw.dat' ) ) {
				if ( unlink( join( '/', [ $base_path, $file ] ) ) ) {
					$sections_values[] = $file;
					WP_CLI::line( sprintf( "file '%s' has been deleted.", $file ) );
				}
			}
		}
		WP_CLI::success(
			sprintf(
				_n( '%s cache file has been cleaned.', '%s cache files have been cleaned.', count( $sections_values ),
					'yousaidit-toolkit' ),
				number_format_i18n( count( $sections_values ) )
			)
		);
	}
}
