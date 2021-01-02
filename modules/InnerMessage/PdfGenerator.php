<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Dompdf\Dompdf;
use JoyPixels\Client;
use JoyPixels\Ruleset;
use YouSaidItCards\Utilities\Filesystem;

class PdfGenerator {
	protected $page_size = [ 300, 150 ];
	protected $font_family = 'Arial';
	protected $text_color = [ 0, 0, 0, 1 ];
	protected $font_size = 14;
	protected $line_height = 18;
	protected $text_align = 'center';
	protected $message = '';
	protected $padding = '8'; // mm
	protected $dir = null;
	protected $order_id = 0;
	protected $item_id = 0;
	protected $product_id = 0;

	/**
	 * PdfGenerator constructor.
	 *
	 * @param \WC_Order_Item_Product $order_item
	 * @param \WC_Order|int|null $order
	 */
	public function __construct( \WC_Order_Item_Product $order_item, $order = null ) {
		$this->item_id    = $order_item->get_id();
		$this->order_id   = $order_item->get_order_id();
		$this->product_id = $order_item->get_product_id();

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $this->order_id );
		}

		$product = $order_item->get_product();
		$pdf_id  = (int) $product->get_meta( '_pdf_id', true );
		$width   = (int) get_post_meta( $pdf_id, '_pdf_width_millimeter', true );
		$height  = (int) get_post_meta( $pdf_id, '_pdf_height_millimeter', true );

		$this->dir  = $order->get_date_created()->format( "Y-m-d" );
		$meta       = $order_item->get_meta( '_inner_message', true );
		$inner_info = is_array( $meta ) ? $meta : [];

		$this->font_size   = isset( $inner_info['size'] ) ? intval( $inner_info['size'] ) : 14;
		$this->line_height = $this->font_size * 1.5;
		$this->text_color  = isset( $inner_info['color'] ) ? $inner_info['color'] : '#000000';
		$this->text_align  = isset( $inner_info['align'] ) ? $inner_info['align'] : 'center';
		$this->message     = isset( $inner_info['content'] ) ? $inner_info['content'] : '';
		$this->font_family = isset( $inner_info['font'] ) ? $inner_info['font'] : 'Arial';;

		if ( $width && $height ) {
			$this->page_size = [ $width, $height ];
		} else {
			$page_size = isset( $inner_info['page_size'] ) ? $inner_info['page_size'] : 'square';
			if ( in_array( $page_size, [ 'a4', 'a5', 'a6' ] ) ) {
				$this->page_size = $page_size;
			} else {
				$this->page_size = [ 300, 150 ];
			}
		}
	}

	public static function get_pdf_for_order_item( \WC_Order_Item_Product $item_product ) {
		$generator = new static( $item_product, $item_product->get_order_id() );

		return $generator->create_if_not_exists();
	}

	public function create_if_not_exists() {
		$dir = Filesystem::get_uploads_dir( 'inner-message' );
		Filesystem::maybe_create_dir( $dir['path'] );
		$fileName = sprintf( "%s-o%s-i%s.pdf", $this->dir, $this->order_id, $this->item_id );
		$file     = $dir['path'] . '/' . $fileName;
		if ( ! file_exists( $file ) ) {
			$this->save_to_file_system();
		}

		return $file;
	}

	public function save_to_file_system() {
		$dir = Filesystem::get_uploads_dir( 'inner-message' );
		Filesystem::maybe_create_dir( $dir['path'] );
		$fileName = sprintf( "%s-o%s-i%s.pdf", $this->dir, $this->order_id, $this->item_id );
		$dompdf   = $this->get_dompdf();
		$dompdf->render();
		$output = $dompdf->output();

		Filesystem::update_file_content( $output, $dir['path'] . '/' . $fileName );
	}

	private function get_pdf_dynamic_style() {
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
				font-size: <?php echo intval(static::px_to_points($this->font_size)).'pt'?>;
				color: <?php echo $this->text_color?>;
				text-align: <?php echo $this->text_align?>;
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

	private function get_pdf_browser_style() {
		?>
		<style>
			.card-content {
				width: <?php echo $this->page_size[0] . 'mm'; ?>;
				height: <?php echo $this->page_size[1] . 'mm'; ?>;
				margin: 1em;
				border: 1px solid rgba(0, 0, 0, 0.12);
				margin-left: auto;
				margin-right: auto;
			}

			.left-column, .right-column {
				width: <?php echo ( $this->page_size[0] / 2 ) . 'mm' ?>;
				height: <?php echo ( $this->page_size[1] ) . 'mm' ?>;
			}
		</style>
		<?php
	}

	private function get_html_wrapper( $content ) {
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

	public function get_message_lines() {
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

	public function get_pdf( $mode = 'html', $context = 'view' ) {
		$dompdf = $this->get_dompdf();

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
	 * @param int $mm
	 *
	 * @return float|int
	 */
	protected static function mm_to_points( $mm ) {
		return intval( $mm ) * 2.834646;
	}

	protected static function points_to_mm( $points ) {
		return intval( $points ) / 2.834646;
	}

	protected static function px_to_points( $px ) {
		return intval( $px ) * 0.75;
	}

	/**
	 * @return Dompdf
	 */
	public function get_dompdf() {
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
		$dir    = Filesystem::get_uploads_dir( 'inner-message-fonts' );
		Filesystem::maybe_create_dir( $dir['path'] );
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
}
