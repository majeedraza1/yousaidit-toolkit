<?php

namespace YouSaidItCards;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_ajax_yousaidit_test', [ self::$instance, 'stackonet_test' ] );
			add_action( 'wp_ajax_yousaidit_generate_preview_card', [ self::$instance, 'generate_preview_card' ] );
			add_action( 'wp_ajax_yousaidit_preview_card', [ self::$instance, 'yousaidit_preview_card' ] );
			add_action( 'wp_ajax_yousaidit_font_image', [ self::$instance, 'yousaidit_font_image' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_font_image', [ self::$instance, 'yousaidit_font_image' ] );
		}

		return self::$instance;
	}

	/**
	 * A AJAX method just to test some data
	 */
	public function stackonet_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ) );
		}

		$items = [
			[
				'label'        => 'Section 1',
				'section_type' => 'static-text',
				'position'     => [ 'top' => 30, 'left' => 10 ],
				'text'         => 'Hello',
				'textOptions'  => [
					'fontFamily' => 'Arial',
					'size'       => 96,
					'align'      => 'center',
					'color'      => '#00ff00'
				]
			],
			[
				'label'        => 'Section 1',
				'section_type' => 'static-image',
				'position'     => [ 'top' => 80, 'left' => 10 ],
				'imageOptions' => [
					'img'    => [ 'id' => 37494 ],
					'width'  => 101,
					'height' => 'auto',
					'align'  => 'center'
				]
			]
		];

		$pdf = new FreePdf();
		$pdf->generate( 'square', $items );
		die();
	}

	public function generate_preview_card() {
		$card_size       = $_POST['card_size'] ?? 'square';
		$card_background = $_POST['card_background'] ?? [];
		if ( is_string( $card_background ) ) {
			$card_background = json_decode( stripslashes( $card_background ), true );
		}
		$card_items = $_POST['card_items'] ?? [];
		if ( is_string( $card_items ) ) {
			$card_items = json_decode( stripslashes( $card_items ), true );
		}
		$data = [ 'size' => $card_size, 'background' => $card_background, 'items' => $card_items ];
		set_transient( 'yousaidit_preview_card_options', $data, HOUR_IN_SECONDS );
		$url = add_query_arg( [
			'action' => 'yousaidit_preview_card',
			'_token' => md5( serialize( $data ) )
		], admin_url( 'admin-ajax.php' ) );
		wp_send_json_success( [ 'redirect' => $url, 'request_data' => $data ] );
	}

	public function yousaidit_preview_card() {
		$transient = get_transient( 'yousaidit_preview_card_options' );
		if ( false === $transient ) {
			die( 'No valid options' );
		}


		$pdf = new FreePdf();
		$pdf->generate( $transient['size'], $transient['items'] );
		die();
	}

	public function yousaidit_font_image() {
		// ?t[f]=Indie%20Flower&t[s]=96pt&t[c]=rgb(0,%20255,%200)
		$args = wp_parse_args( $_GET['t'] ?? [], [
			't' => 'Example Text',
			'f' => 'Indie Flower',
			's' => '96pt',
			'c' => 'rgb(0, 255, 0)',
			'a' => '',
		] );

		$width  = $_GET['w'] ? intval( $_GET['w'] ) : 150;
		$width  = round( $width * 3.7795275591 );
		$height = $_GET['h'] ? intval( $_GET['h'] ) : 150;
		$height = round( $height * 3.7795275591 );
		$pos_x  = $_GET['x'] ? intval( $_GET['x'] ) : 0;
		$pos_x  = round( $pos_x * 3.7795275591 );
		$pos_y  = $_GET['y'] ? intval( $_GET['y'] ) : 0;
		$pos_y  = round( $pos_y * 3.7795275591 );

		$font_family = $args['f'];
		$_font_size  = (int) str_replace( 'pt', '', $args['s'] );
		$font_file   = '';
		$_color      = $args['c'];
		$_text       = $args['t'];

		$base_path = YOUSAIDIT_TOOLKIT_PATH . '/vendor/setasign/tfpdf/font/unifont/';
		$fonts     = [
			[
				'family' => [ 'Indie Flower', 'IndieFlower', 'indie_flower' ],
				'path'   => $base_path . 'IndieFlower-Regular.ttf'
			]
		];

		foreach ( $fonts as $font ) {
			if ( in_array( $font_family, $font['family'] ) ) {
				$font_file = $font['path'];
			}
		}

		$draw = new \ImagickDraw();
		$draw->setFont( $font_file );
		$draw->setFontSize( $_font_size );
		$draw->setStrokeAntialias( true );
		$draw->setTextAntialias( true );
		$draw->setFillColor( new \ImagickPixel( $_color ) );

		// The Imagick constructor
		$textOnly = new \Imagick();
		// Set transparent background color
		$textOnly->setBackgroundColor( new \ImagickPixel( 'white' ) );
		// Creates a new image
		$textOnly->newImage( $width, $height, "white" );
		// Sets the format of this particular image
		$textOnly->setImageFormat( 'png' );

		$metrics = $textOnly->queryFontMetrics( $draw, $_text );
		if ( 'center' == $args['a'] ) {
			$pos_x = round( $width / 2 - $metrics['textWidth'] / 2 );
		}

		// Annotates an image with text
		$textOnly->annotateImage( $draw, $pos_x, $pos_y, 0, $_text );

		// Remove edges from the image
//		$textOnly->trimImage( 0 );
		// Sets the page geometry of the image
//		$textOnly->setImagePage( $textOnly->getimageWidth(), $textOnly->getimageheight(), 0, 0 );

		// Sets the image virtual pixel method
		$textOnly->setImageVirtualPixelMethod( \Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
		// Sets the image matte channel
		$textOnly->setImageMatte( true );


		header( "Content-Type: image/png" );
		// Returns the image sequence as a blob
		echo $textOnly->getimageblob();

		die();
	}
}
