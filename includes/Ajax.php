<?php

namespace YouSaidItCards;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;
use Stackonet\WP\Framework\Media\Uploader;
use YouSaidItCards\Modules\Designers\DynamicCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

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
			add_action( 'wp_ajax_yousaidit_color_image', [ self::$instance, 'yousaidit_color_image' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_color_image', [ self::$instance, 'yousaidit_color_image' ] );
			add_action( 'wp_ajax_yousaidit_save_dynamic_card', [ self::$instance, 'save_dynamic_card' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_save_dynamic_card', [ self::$instance, 'save_dynamic_card' ] );
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

		$sections_values = wc_get_order_item_meta( 53163, '_dynamic_card', true );
		foreach ( $sections_values as $value ) {
			if ( ! is_numeric( $value['value'] ) ) {
				continue;
			}
			$meta = get_post_meta( $value['value'], '_should_delete_after_time', true );
			if ( is_numeric( $meta ) ) {
				delete_post_meta( $value['value'], '_should_delete_after_time', $meta );
			}
		}
		var_dump( $sections_values );

		die();
	}

	public function generate_preview_card() {
		$card_size       = $_POST['card_size'] ?? 'square';
		$card_bg_type    = $_POST['card_bg_type'] ?? 'color';
		$card_bg_color   = $_POST['card_bg_color'] ?? '#ffffff';
		$card_background = $_POST['card_background'] ?? [];
		if ( is_string( $card_background ) ) {
			$card_background = json_decode( stripslashes( $card_background ), true );
		}
		$card_items = $_POST['card_items'] ?? [];
		if ( is_string( $card_items ) ) {
			$card_items = json_decode( stripslashes( $card_items ), true );
		}
		$data           = [
			'size'     => $card_size,
			'bg_type'  => $card_bg_type,
			'bg_color' => stripslashes( $card_bg_color ),
			'bg_image' => $card_background,
			'items'    => $card_items
		];
		$token          = md5( serialize( $data ) );
		$transient_name = sprintf( "yousaidit_preview_card_%s", $token );
		set_transient( $transient_name, $data, HOUR_IN_SECONDS );
		$url = add_query_arg( [
			'action' => 'yousaidit_preview_card',
			'_token' => $token
		], admin_url( 'admin-ajax.php' ) );
		wp_send_json_success( [ 'redirect' => $url, 'request_data' => $data ] );
	}

	public function yousaidit_preview_card() {
		if ( ! isset( $_REQUEST['_token'], $_REQUEST['action'] ) ) {
			die( 'No valid options' );
		}
		$card_id = $_REQUEST['card_id'] ?? 0;

		$transient_name = sprintf( "%s_%s", $_REQUEST['action'], $_REQUEST['_token'] );
		$transient      = get_transient( $transient_name );
		if ( empty( $card_id ) && false === $transient ) {
			die( 'No valid options' );
		}

		if ( $card_id ) {
			$card = ( new DesignerCard() )->find_by_id( $card_id );
			if ( $card instanceof DesignerCard && $card->is_dynamic_card() ) {
				$payload = $card->get_dynamic_card_payload();
				$pdf     = new FreePdf();
				$pdf->generate( $payload['card_size'], $payload['card_items'], [
					'type'  => $payload['card_bg_type'],
					'color' => $payload['card_bg_color'],
					'image' => $payload['card_background']
				] );
				die();
			}
		}

		$background = [
			'type'  => $transient['bg_type'],
			'color' => str_replace( '"', '', $transient['bg_color'] ),
			'image' => $transient['bg_image']
		];

		$pdf = new FreePdf();
		$pdf->generate( $transient['size'], $transient['items'], $background );
		die();
	}

	/**
	 * @return void
	 * @throws ImagickException
	 * @throws \ImagickDrawException
	 * @throws \ImagickPixelException
	 */
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
		$draw->setFillColor( new ImagickPixel( $_color ) );

		// The Imagick constructor
		$textOnly = new Imagick();
		// Set transparent background color
		$textOnly->setBackgroundColor( new ImagickPixel( 'white' ) );
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
		$textOnly->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
		// Sets the image matte channel
		$textOnly->setImageMatte( true );


		header( "Content-Type: image/png" );
		// Returns the image sequence as a blob
		echo $textOnly->getimageblob();

		die();
	}

	public function yousaidit_color_image() {
		$width  = $_REQUEST['w'] ? intval( $_REQUEST['w'] ) * 37.795275591 : 0;
		$height = $_REQUEST['h'] ? intval( $_REQUEST['h'] ) * 37.795275591 : 0;
		$color  = $_REQUEST['c'] ? rawurldecode( $_REQUEST['c'] ) : 'white';

		try {
			// The Imagick constructor
			$textOnly = new Imagick();
			// Creates a new image
			$textOnly->newImage( $width, $height, new ImagickPixel( $color ) );
			// Sets the format of this particular image
			$textOnly->setImageFormat( 'png' );

			header( "Content-Type: image/png" );
			// Returns the image sequence as a blob
			echo $textOnly->getimageblob();
		} catch ( Exception $e ) {
		}
		die;
	}

	public function save_dynamic_card() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You cannot perform this action.' );
		}

		$card_id = $_REQUEST['card_id'] ?? 0;
		$card    = ( new DesignerCard )->find_by_id( intval( $card_id ) );
		if ( ! $card instanceof DesignerCard ) {
			wp_die( 'No card available.' );
		}

		$new_file_path = DynamicCard::create_card_pdf( $card );
		try {
			DynamicCard::clone_pdf_to_jpg( $card, $new_file_path );
			$im = DynamicCard::pdf_to_image( $new_file_path );
			header( 'Content-Type: image/jpeg' );
			echo $im->getImageBlob();
		} catch ( ImagickException $e ) {
			var_dump( $e );
		}
		die;
	}
}
