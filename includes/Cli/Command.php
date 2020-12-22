<?php

namespace YouSaidItCards\Cli;

use Dompdf\Exception;
use Stackonet\WP\Framework\Supports\Logger;
use Yousaidit\Modules\InnerMessage\Fonts;

class Command extends \WP_CLI_Command {
	/**
	 * Display Carousel Slider Information
	 *
	 * @subcommand generate
	 */
	public function generate() {
		\WP_CLI::success( 'Welcome to the Carousel Slider WP-CLI Extension!' );
		foreach ( Fonts::get_list() as $item ) {
			$fontFamily = str_replace( ' ', '_', strtolower( $item['label'] ) );
			$path       = $item['fontFilePath'];
			if ( ! file_exists( $path ) ) {
				\WP_CLI::line( 'Font file not found: ' . $path );
			}

			try {
				Fonts::install_font_family( $fontFamily, $path, $path, $path, $path );
				\WP_CLI::line( 'Font file generated successfully for font: ' . $fontFamily );
			} catch ( Exception $e ) {
				Logger::log( $e );
			}
		}
		\WP_CLI::line( 'All operation done.' );
		\WP_CLI::line( '' );
	}
}
