<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Dompdf\Dompdf;
use JoyPixels\Client;
use JoyPixels\Ruleset;

class PdfGeneratorBase {
	protected $page_size = [ 300, 150 ];
	protected $font_family = 'Arial';
	protected $text_color = '#000000';
	protected $left_column_bg = '#ffffff';
	protected $right_column_bg = '#ffffff';
	protected $font_size = 16;
	protected $text_align = 'center';
	protected $message = '';
	protected $line_height = 18;
	protected $padding = '8'; // mm
	protected $dir = null;


	public function get_pdf( $mode = 'html', $context = 'view' ) {
		$dompdf = $this->get_dompdf();
		// $dompdf->set_option( 'dpi', 200 );

		// Output the generated PDF to Browser
		if ( 'pdf' == $mode ) {
			$dompdf->render();
			if ( 'download' == $context ) {
				$dompdf->stream();
			} else {
				$output = $dompdf->output();
				header( "Content-Type: application/pdf" );
				echo $output;
				die;
			}
		}

		// Render the HTML
		echo $dompdf->outputHtml();
	}

	/**
	 * @return Dompdf
	 */
	public function get_dompdf(): Dompdf {
		$lines = $this->get_message_lines();
		$html  = '';
		foreach ( $lines as $line ) {
			$line = str_replace( '&nbsp;', '', $line );
			if ( strlen( $line ) < 1 ) {
				continue;
			}
			if ( in_array( $line, [ '<br>', '<br/>', '<br />' ] ) ) {
				$html .= "<br>";
			} else {
				$html .= "<div>{$line}</div>";
			}
		}
		$final_html = $this->get_html_wrapper( $html );
		$final_html = preg_replace( '/>\s+</', "><", $final_html );

		// instantiate and use the dompdf class
		$dompdf = new Dompdf( [ 'enable_remote' => true ] );
		$dompdf->loadHtml( $final_html );

		// (Optional) Setup the paper size and orientation
		if ( is_array( $this->page_size ) ) {
			$dompdf->setPaper( [
				0,
				0,
				static::mm_to_points( $this->page_size[0] ),
				static::mm_to_points( $this->page_size[1] )
			] );
		} else {
			$dompdf->setPaper( $this->page_size );
		}

		return $dompdf;
	}

	/**
	 * Get pdf dynamic style
	 * @return void
	 */
	protected function get_pdf_dynamic_style() {
		$font_info      = Fonts::get_font_info( $this->font_family );
		$fontFamily     = str_replace( ' ', '_', strtolower( $font_info['label'] ) );
		$content_height = static::mm_to_points( $this->page_size[1] ) - static::px_to_points( $this->line_height * count( $this->get_message_lines() ) );
		?>
		<style type="text/css">
			@font-face {
				font-family: <?php echo $fontFamily?>;
				src: url(<?php echo $font_info['fontUrl'] ?>) format('truetype');
				font-weight: normal;
				font-style: normal;
			}

			body, .card-content-inner {
				font-family: <?php echo $fontFamily?>;
				font-weight: normal;
				font-size: <?php echo $this->font_size.'pt'?>;
				color: <?php echo $this->text_color?>;
				text-align: <?php echo $this->text_align?>;
			}

			.left-column {
				background-color: <?php echo $this->left_column_bg ?>;
			}

			.right-column {
				background-color: <?php echo $this->right_column_bg ?>;
			}

			.left-column, .right-column {
				width: <?php echo ($this->page_size[0] / 2).'mm'?>;
				height: <?php echo ($this->page_size[1] ).'mm'?>;
			}

			.card-content-inner {
				margin-top: <?php echo intval((static::points_to_mm($content_height) / 2) - $this->padding) .'mm'?>;
			}

			.padding-15 {
				padding: <?php echo $this->padding.'mm' ?>;
			}
		</style>
		<?php
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	protected function get_html_wrapper( string $content ): string {
		ob_start(); ?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<title>Document</title>
			<style type="text/css">
				<?php include YOUSAIDIT_TOOLKIT_PATH . '/templates/style-inner-message.css'; ?>
			</style>
			<?php $this->get_pdf_dynamic_style(); ?>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		</head>
		<body>
		<div class="card-content">
			<table class="container">
				<tr class="no-borders">
					<td class="no-borders left-column"></td>
					<td class="no-borders right-column">
						<div class="card-content-inner align-center justify-center padding-15">
							<?php echo $content; ?>
						</div>
					</td>
				</tr>
			</table>
		</div>
		</body>
		</html>
		<?php return ob_get_clean();
	}

	/**
	 * Get message lines
	 * @return array
	 */
	public function get_message_lines(): array {
		$client               = new Client( new Ruleset() );
		$client->imagePathPNG = YOUSAIDIT_TOOLKIT_ASSETS . '/emoji-assets-6.0.0/64/';
		$message              = str_replace( '<p>', '<div>', $this->message );
		$message              = str_replace( '</p>', '</div>', $message );
		$messages             = explode( '<div>', $message );
		foreach ( $messages as $index => $message ) {
			$msg                = str_replace( "</div>", '', $message );
			$messages[ $index ] = $client->toImage( $msg );
		}

		return $messages;
	}

	public function set_text_data( array $args ) {
		$this->font_size   = isset( $args['size'] ) ? intval( $args['size'] ) : 16;
		$this->line_height = $this->font_size * 1.5;
		$this->text_color  = $args['color'] ?? '#000000';
		$this->text_align  = $args['align'] ?? 'center';
		$this->message     = $args['content'] ?? '';
		$this->font_family = $args['font'] ?? 'Arial';
	}

	public function set_page_size( int $width, int $height ) {
		$this->page_size = [ $width, $height ];
	}

	protected static function mm_to_points( float $mm ): float {
		return $mm * 2.834646;
	}

	protected static function points_to_mm( float $points ): float {
		return $points / 2.834646;
	}

	protected static function px_to_points( float $px ): float {
		return $px * 0.75;
	}

	protected static function pt_to_px( float $pt ): float {
		return $pt * .75;
	}

	/**
	 * @param string $left_column_bg
	 */
	public function set_left_column_bg( string $left_column_bg ): void {
		$this->left_column_bg = $left_column_bg;
	}

	/**
	 * @param string $right_column_bg
	 */
	public function set_right_column_bg( string $right_column_bg ): void {
		$this->right_column_bg = $right_column_bg;
	}
}
