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
	protected $text_box_height = 0;


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

	/**
	 * Get text box height
	 *
	 * @return mixed
	 */
	public function get_text_height() {
		$font_info = Fonts::get_font_info( $this->font_family );
		$box       = imagettfbbox(
			$this->font_size,
			0,
			$font_info['fontFilePath'],
			'A quick brown fox jumps over the lazy dogs.'
		);

		return $box[3] - $box[5];
	}

	public function get_text_box_height(): float {
		$lines  = $this->get_message_lines();
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
	 * @param  string  $text
	 *
	 * @return array|false {
	 * Array of font metrics info
	 * }
	 */
	public function get_font_metrics( string $text = '' ) {
		if ( empty( $text ) ) {
			$text = 'A quick brown fox jumps over the lazy dogs.';
		}
		$font_info = Fonts::get_font_info( $this->font_family );
		try {
			$im = new Imagick();
			$im->setResolution( 300, 300 );
			$draw = new ImagickDraw();
			$draw->setFont( $font_info['fontFilePath'] );
			$draw->setFontSize( $this->font_size );

			return $im->queryFontMetrics( $draw, $text );
		} catch ( ImagickDrawException|ImagickException $e ) {
			Logger::log( $e );

			return false;
		}
	}

	protected function recalculate_lines( array $text_lines, float $max_box_width = 0 ): array {
		if ( empty( $max_box_width ) ) {
			$half_of_page      = $this->page_size[0] / 2;
			$max_content_width = $half_of_page - ( $this->padding * 2 ) - 2; // 2 mm extra edge
			$max_box_width     = static::mm_to_points( $max_content_width );
		}
		$computed_lines = [];
		foreach ( $text_lines as $str ) {
			$matrix = $this->get_font_metrics( $str );
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
		$font_info       = Fonts::get_font_info( $this->font_family );
		$fontFamily      = str_replace( ' ', '_', strtolower( $font_info['label'] ) );
		$text_box_height = static::points_to_mm( $this->get_text_box_height() );
		$max_height      = $this->page_size[1] - ( $this->padding * 2 );
		if ( $text_box_height >= $max_height ) {
			$content_inner_mt = 1;
		} else {
			$content_inner_mt = intval( ( $max_height - $text_box_height ) / 2 );
		}
		$show_structure = isset( $_GET['show_structure'] );
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
	 * @param  string  $content
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
                    <td class="no-borders right-column" align="center">
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
			$messages[ $index ] = preg_replace( '/(<[^>]+) style=".*?"/i', '$1', $msg );
		}
		$messages = array_filter( $messages );// Remove empty elements
		$messages = $this->recalculate_lines( $messages );
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
