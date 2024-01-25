<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Dompdf\Dompdf;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use JoyPixels\Client;
use JoyPixels\Ruleset;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\FontManager\Models\FontInfo;
use YouSaidItCards\Utils;

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
	protected $padding = 8; // mm
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
		$lines      = $this->get_right_page_message_lines();
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
				$left_html .= '<div style="max-width: 240px;margin-left:auto;margin-right:auto;margin-top:50px;font-size:7pt;line-height:7pt;font-family:arial">';
				$left_html .= sprintf( 'This QR code will be expired after %s',
					date( get_option( 'date_format' ), $this->video_delete_after ) );
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
		$dompdf = new Dompdf( [
			'enable_remote'     => true,
			'font_height_ratio' => 1
		] );
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

	public function get_left_page_text_box_height(): float {
		$lines  = $this->get_left_page_message_lines();
		$height = 0;
		foreach ( $lines as $line ) {
			$metrics = $this->get_font_metrics(
				wp_strip_all_tags( $line ),
				$this->left_page_message['font'],
				$this->left_page_message['size']
			);
			if ( is_array( $metrics ) && isset( $metrics['textHeight'] ) ) {
				$height += floatval( $metrics['textHeight'] );
			}
		}

		return $height;
	}

	public function get_right_page_text_box_height(): float {
		$lines  = $this->get_right_page_message_lines();
		$height = 0;
		foreach ( $lines as $line ) {
			$metrics = $this->get_font_metrics( wp_strip_all_tags( $line ) );
			if ( is_array( $metrics ) && isset( $metrics['textHeight'] ) ) {
				$height += floatval( $metrics['textHeight'] );
			}
		}

		return $height;
	}

	/**
	 * Get font metrics
	 *
	 * @param  string  $text  The string to test for font metrics.
	 * @param  string  $font_family  The font family.
	 * @param  int  $font_size  The font size.
	 *
	 * @return array|false {
	 * Array of font metrics info
	 * }
	 */
	public function get_font_metrics( string $text = '', string $font_family = '', int $font_size = 0 ) {
		if ( empty( $text ) ) {
			$text = 'A quick brown fox jumps over the lazy dogs.';
		}
		if ( empty( $font_family ) ) {
			$font_family = $this->font_family;
		}
		if ( empty( $font_size ) ) {
			$font_size = $this->font_size;
		}
		$font_info = Font::find_font_info( $font_family );
		try {
			$im = new Imagick();
			$im->setResolution( 300, 300 );
			$draw = new ImagickDraw();
			$draw->setFont( $font_info->get_font_path() );
			$draw->setFontSize( $font_size );

			return $im->queryFontMetrics( $draw, $text );
		} catch ( ImagickDrawException|ImagickException $e ) {
			Logger::log( $e );

			return false;
		}
	}

	protected function recalculate_lines(
		array $text_lines,
		float $max_box_width = 0,
		string $font_family = '',
		int $font_size = 0
	): array {
		if ( empty( $max_box_width ) ) {
			$half_of_page      = $this->page_size[0] / 2;
			$max_content_width = $half_of_page - ( $this->padding * 2 ) - 2; // 2 mm extra edge
			$max_box_width     = static::mm_to_points( $max_content_width );
		}
		$computed_lines = [];
		foreach ( $text_lines as $str ) {
			$matrix = $this->get_font_metrics( $str, $font_family, $font_size );
			if ( $matrix['textWidth'] <= $max_box_width ) {
				$computed_lines[] = $str;
			} else {
				$lines = $this->split_long_line_into_multiple_lines( $str, $max_box_width );
				foreach ( $lines as $line ) {
					$computed_lines[] = $line;
				}
			}
		}

		return $computed_lines;
	}

	/**
	 * @param  string  $message  The message to be calculated.
	 * @param  float  $line_max_width  Line maximum width in points.
	 *
	 * @return array
	 */
	protected function split_long_line_into_multiple_lines( string $message, float $line_max_width ): array {
		$messages = array_filter( explode( PHP_EOL, $message ) );

		$lines = "";
		foreach ( $messages as $str ) {
			$matrix = $this->get_font_metrics( $str );
			if ( $matrix['textWidth'] <= $line_max_width ) {
				$lines .= $str . PHP_EOL;
				continue;
			}

			$words = preg_split( '/ +/', $str );
			$width = 0;
			$_line = "";
			foreach ( $words as $word ) {
				$word_matrix = $this->get_font_metrics( $word );
				if ( $width + $word_matrix['textWidth'] < $line_max_width ) {
					$_line        .= " " . $word;
					$word_matrix2 = $this->get_font_metrics( " " . $word );
					$width        += $word_matrix2['textWidth'];
				} else {
					$_line .= PHP_EOL . $word;
					$width = $word_matrix['textWidth'];
				}
			}
			$lines .= trim( $_line ) . PHP_EOL;
		}

		return array_filter( explode( PHP_EOL, $lines ) );
	}

	/**
	 * Get pdf dynamic style
	 * @return void
	 */
	protected function get_pdf_dynamic_style() {
		$font_info       = Font::find_font_info( $this->font_family );
		$text_box_height = static::points_to_mm( $this->get_right_page_text_box_height() );
		$max_height      = $this->page_size[1] - ( $this->padding * 2 );
		if ( $text_box_height >= $max_height ) {
			$content_inner_mt = 1;
		} else {
			$content_inner_mt = intval( ( $max_height - $text_box_height ) / 2 );
		}
		$show_structure = isset( $_GET['show_structure'] );
		?>
        <style type="text/css">
            <?php
            if ($font_info instanceof FontInfo){
                echo $font_info->font_face_css();
            }
            ?>

            body, .card-content-inner {
                font-family: <?php echo $font_info instanceof FontInfo?$font_info->get_font_family_for_dompdf():'Arial'?>;
                font-weight: normal;
                font-size: <?php echo $this->font_size.'pt'?>;
                line-height: <?php echo $this->font_size.'pt'?>;
                color: <?php echo $this->text_color?>;
                text-align: <?php echo $this->text_align?>;
            }

            br {
                line-height: <?php echo $this->font_size.'pt'?>;
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
                margin-top: <?php echo $content_inner_mt .'mm'?>;
            }

            .padding-15 {
                padding: <?php echo $this->padding.'mm' ?>;
            }
        </style>
		<?php
		if ( $show_structure ) {
			?>
            <style>
                .left-column {
                    background-color: lightyellow;
                }

                .right-column {
                    background-color: whitesmoke;
                    position: relative;
                }

                .middle-of-the-page {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    left: 0;
                    top: 0;
                    z-index: -1;
                }

                .structure-row {
                    position: absolute;
                    width: 100%;
                    height: 10%;
                    left: 0;
                }

                .bg-even {
                    background-color: #fef2f2;
                }

                .bg-odd {
                    background-color: rgb(231 229 228);
                }

                <?php
				foreach ( range( 1, 10 ) as $index => $item ) {
					echo '.row-' . intval( $item ) . '-of-10 {';
					echo 'top: '. (10 * $index).'%';
					echo '}';
				}
				?>
            </style>
			<?php
		}
	}

	/**
	 * Get pdf dynamic style
	 * @return void
	 */
	protected function get_pdf_left_page_dynamic_style() {
		if ( false === $this->has_left_page_message ) {
			return;
		}
		$text_box_height = static::points_to_mm( $this->get_left_page_text_box_height() );
		$max_height      = $this->page_size[1] - ( $this->padding * 2 );
		if ( $text_box_height >= $max_height ) {
			$content_inner_mt = 1;
		} else {
			$content_inner_mt = intval( ( $max_height - $text_box_height ) / 2 );
		}

		$font_info = Font::find_font_info( $this->left_page_message['font'] );
		?>
        <style type="text/css">
            <?php
               if ($font_info instanceof FontInfo){
                   echo $font_info->font_face_css();
               }
            ?>

            .card-content-inner-left {
                font-family: <?php echo $font_info instanceof FontInfo?$font_info->get_font_family_for_dompdf():'Arial'?>;
                font-weight: normal;
                font-size: <?php echo $this->left_page_message['size'].'pt'?>;
                line-height: <?php echo $this->left_page_message['size'].'pt'?>;
                color: <?php echo $this->left_page_message['color']?>;
                text-align: <?php echo $this->left_page_message['align']?>;
            }

            .card-content-inner-left {
                margin-top: <?php echo $content_inner_mt .'mm'?>;
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
	 * @param  string  $content  Right side content.
	 * @param  string  $left_content  Left side content.
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
						<?php
						if ( isset( $_GET['show_structure'] ) ) {
							echo '<div class="middle-of-the-page">';
							foreach ( range( 1, 10 ) as $index => $item ) {
								$color_class = ( $index % 2 ) ? 'bg-even' : 'bg-odd';
								echo '<div class="structure-row row-' . intval( $item ) . '-of-10 ' . $color_class . '"></div>';
							}
							echo '</div>';
						}
						?>
                        <div class="card-content-inner card-content-inner-left align-center justify-center padding-15">
							<?php echo $left_content; ?>
                        </div>
                    </td>
                    <td class="no-borders right-column" align="center">
						<?php
						if ( isset( $_GET['show_structure'] ) ) {
							echo '<div class="middle-of-the-page">';
							foreach ( range( 1, 10 ) as $index => $item ) {
								$color_class = ( $index % 2 ) ? 'bg-odd' : 'bg-even';
								echo '<div class="structure-row row-' . intval( $item ) . '-of-10 ' . $color_class . '"></div>';
							}
							echo '</div>';
						}
						?>
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
	public function get_right_page_message_lines(): array {
		$client               = new Client( new Ruleset() );
		$client->imagePathPNG = PdfGeneratorBase::get_emoji_assets_url();
		$message              = Utils::sanitize_inner_message_text( $this->message );
		$messages             = explode( '<div>', $message );
		foreach ( $messages as $index => $message ) {
			$messages[ $index ] = str_replace( "</div>", '', $message );
		}
		$messages = array_filter( $messages );// Remove empty elements
		$messages = $this->recalculate_lines( $messages );
		foreach ( $messages as $index => $message ) {
			$messages[ $index ] = $client->toImage( $message );
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
		$message              = Utils::sanitize_inner_message_text( $this->left_page_message['content'] ?? '' );
		$messages             = explode( '<div>', $message );
		foreach ( $messages as $index => $message ) {
			$msg                = str_replace( "</div>", '', $message );
			$messages[ $index ] = preg_replace( '/(<[^>]+) style=".*?"/i', '$1', $msg );
		}

		$messages = array_filter( $messages );// Remove empty elements
		$messages = $this->recalculate_lines(
			$messages,
			0,
			$this->left_page_message['font'],
			$this->left_page_message['size']
		);
		foreach ( $messages as $index => $message ) {
			$messages[ $index ] = $client->toImage( $message );
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
	 * @param  string  $left_column_bg
	 */
	public function set_left_column_bg( string $left_column_bg ): void {
		$this->left_column_bg = $left_column_bg;
	}

	/**
	 * @param  string  $right_column_bg
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
