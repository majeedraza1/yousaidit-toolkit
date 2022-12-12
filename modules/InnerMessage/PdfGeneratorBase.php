<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Dompdf\Dompdf;
use JoyPixels\Client;
use JoyPixels\Ruleset;

class PdfGeneratorBase {
	protected $page_size = [ 306, 156 ];
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
	protected $video_message_qr_code = [];
	protected $left_page_message = [];
	protected $has_video_message = false;
	protected $has_left_page_message = false;
	protected int $video_delete_after = 0;


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
		$lines      = $this->get_message_lines();
		$right_html = '';
		foreach ( $lines as $line ) {
			$line = str_replace( '&nbsp;', '', $line );
			if ( strlen( $line ) < 1 ) {
				continue;
			}
			if ( in_array( $line, [ '<br>', '<br/>', '<br />' ] ) ) {
				$right_html .= "<br>";
			} else {
				$right_html .= "<div>{$line}</div>";
			}
		}

		$left_html = '';
		if ( $this->has_video_message && isset( $this->video_message_qr_code['url'] ) ) {
			$left_html .= '<img src="' . esc_url( $this->video_message_qr_code['url'] ) . '" width="96" height="96" />';
			$left_html .= '<div style="max-width: 240px;margin-left:auto;margin-right:auto;font-size:12pt;line-height:12pt;font-family:arial">Scan to watch a video greeting made just for you</div>';
			if ( $this->video_delete_after ) {
				$left_html .= '<div style="font-size:7pt;line-height:7pt;position:absolute;bottom:8px;left:8px;font-family:arial">';
				$left_html .= sprintf( 'This QR code will be expired after %s', date( get_option( 'date_format' ), $this->video_delete_after ) );
				$left_html .= '</div>';
			}
		}
		if ( $this->has_left_page_message ) {
			foreach ( $this->get_left_page_message_lines() as $line ) {
				$line = str_replace( '&nbsp;', '', $line );
				if ( strlen( $line ) < 1 ) {
					continue;
				}
				if ( in_array( $line, [ '<br>', '<br/>', '<br />' ] ) ) {
					$left_html .= "<br>";
				} else {
					$left_html .= "<div>{$line}</div>";
				}
			}
		}

		$final_html = $this->get_html_wrapper( $right_html, $left_html );
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
	 * Get text box height
	 *
	 * @return mixed
	 */
	public function get_text_height( string $font_family = '', int $font_size = 0 ) {
		if ( empty( $font_family ) ) {
			$font_family = $this->font_family;
		}
		if ( empty( $font_size ) ) {
			$font_size = $this->font_size;
		}
		$font_info = Fonts::get_font_info( $font_family );
		$box       = imagettfbbox( $font_size, 0, $font_info['fontFilePath'], 'I only need height' );

		return $box[3] - $box[5];
	}

	/**
	 * Get pdf dynamic style
	 * @return void
	 */
	protected function get_pdf_dynamic_style() {
		$font_info       = Fonts::get_font_info( $this->font_family );
		$fontFamily      = str_replace( ' ', '_', strtolower( $font_info['label'] ) );
		$text_height     = $this->get_text_height();
		$text_box_height = ( $this->padding * 2 ) + static::points_to_mm( $text_height * count( $this->get_message_lines() ) );
		$content_height  = static::mm_to_points( $this->page_size[1] - $text_box_height );
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
				line-height: <?php echo $this->font_size.'pt'?>;
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
				width: <?php echo intval($this->page_size[0] / 2).'mm'?>;
				height: <?php echo intval($this->page_size[1] ).'mm'?>;
			}

			.card-content-inner {
				margin-top: <?php echo intval(static::points_to_mm($content_height) / 2) .'mm'?>;
			}

			.padding-15 {
				padding: <?php echo $this->padding.'mm' ?>;
			}
		</style>
		<?php
	}

	/**
	 * Get pdf dynamic style
	 * @return void
	 */
	protected function get_pdf_left_page_dynamic_style() {
		if ( false === $this->has_left_page_message ) {
			return;
		}
		$font_info       = Fonts::get_font_info( $this->left_page_message['font'] );
		$fontFamily      = str_replace( ' ', '_', strtolower( $font_info['label'] ) );
		$text_height     = $this->get_text_height( $this->left_page_message['font'], $this->left_page_message['size'] );
		$text_box_height = ( $this->padding * 2 ) + static::points_to_mm( $text_height * count( $this->get_left_page_message_lines() ) );
		$content_height  = static::mm_to_points( $this->page_size[1] - $text_box_height );
		?>
		<style type="text/css">
			@font-face {
				font-family: <?php echo $fontFamily?>;
				src: url(<?php echo $font_info['fontUrl'] ?>) format('truetype');
				font-weight: normal;
				font-style: normal;
			}

			.card-content-inner-left {
				font-family: <?php echo $fontFamily?>;
				font-weight: normal;
				font-size: <?php echo $this->left_page_message['size'].'pt'?>;
				line-height: <?php echo $this->left_page_message['size'].'pt'?>;
				color: <?php echo $this->left_page_message['color']?>;
				text-align: <?php echo $this->left_page_message['align']?>;
			}

			.card-content-inner-left {
				margin-top: <?php echo intval(static::points_to_mm($content_height) / 2) .'mm'?>;
			}
		</style>
		<?php
	}

	protected function get_pdf_left_video_qr_code_style() {
		if ( ! $this->has_video_message ) {
			return;
		}
		$content_height = static::mm_to_points( $this->page_size[1] ) - static::px_to_points( 200 );
		?>
		<style type="text/css">
			.card-content-inner-left {
				margin-top: <?php echo intval(static::points_to_mm($content_height) / 2) .'mm'?>;
			}
		</style>
		<?php
	}

	/**
	 * Get HTML wrapper
	 *
	 * @param string $content Right side content.
	 * @param string $left_content Left side content.
	 *
	 * @return string
	 */
	protected function get_html_wrapper( string $content, string $left_content = '' ): string {
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
			<?php $this->get_pdf_left_page_dynamic_style(); ?>
			<?php $this->get_pdf_left_video_qr_code_style(); ?>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		</head>
		<body>
		<div class="card-content">
			<table class="container">
				<tr class="no-borders">
					<td class="no-borders left-column" align="center">
						<div class="card-content-inner card-content-inner-left align-center justify-center padding-15">
							<?php echo $left_content; ?>
						</div>
					</td>
					<td class="no-borders right-column" align="center">
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
		$client->imagePathPNG = PdfGeneratorBase::get_emoji_assets_url();
		$message              = str_replace( '<p>', '<div>', $this->message );
		$message              = str_replace( '</p>', '</div>', $message );
		$messages             = explode( '<div>', $message );
		foreach ( $messages as $index => $message ) {
			$msg                = str_replace( "</div>", '', $message );
			$messages[ $index ] = $client->toImage( $msg );
		}

		return $messages;
	}

	/**
	 * Get message lines
	 * @return array
	 */
	public function get_left_page_message_lines(): array {
		$client               = new Client( new Ruleset() );
		$client->imagePathPNG = PdfGeneratorBase::get_emoji_assets_url();
		$message              = str_replace( '<p>', '<div>', $this->left_page_message['content'] ?? '' );
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

	/**
	 * @return string
	 */
	public static function get_emoji_assets_url(): string {
		$upload_dir = wp_upload_dir();
		$media_dir  = join( DIRECTORY_SEPARATOR, [ $upload_dir['basedir'], 'emoji-assets-6.0.0/64/' ] );
		$media_url  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $media_dir );
		if ( ! file_exists( $media_dir ) ) {
			return YOUSAIDIT_TOOLKIT_ASSETS . '/emoji-assets-6.0.0/64/';
		}

		return $media_url;
	}
}
