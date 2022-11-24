<?php

namespace YouSaidItCards;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;
use Stackonet\WP\Framework\Media\Uploader;
use Stackonet\WP\Framework\Supports\Logger;
use WC_Product;
use YouSaidItCards\Modules\Designers\DynamicCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\DynamicCard\EnvelopeColours;
use YouSaidItCards\Modules\InnerMessage\Fonts;

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
			add_action( 'wp_ajax_generate_dynamic_card_pdf', [ self::$instance, 'generate_dynamic_card_pdf' ] );
			add_action( 'wp_ajax_yousaidit_font_image', [ self::$instance, 'yousaidit_font_image' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_font_image', [ self::$instance, 'yousaidit_font_image' ] );
			add_action( 'wp_ajax_yousaidit_color_image', [ self::$instance, 'yousaidit_color_image' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_color_image', [ self::$instance, 'yousaidit_color_image' ] );
			add_action( 'wp_ajax_yousaidit_save_dynamic_card', [ self::$instance, 'save_dynamic_card' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_save_dynamic_card', [ self::$instance, 'save_dynamic_card' ] );
			add_action( 'wp_ajax_yousaidit_clear_background_task', [ self::$instance, 'clear_background_task' ] );
			add_action( 'wp_ajax_yousaidit_clear_tfpdf_fonts_cache', [ self::$instance, 'tfpdf_clear_fonts_cache' ] );
			add_action( 'wp_ajax_yousaidit_dompdf_install_font', [ self::$instance, 'dompdf_install_font' ] );
			add_action( 'wp_ajax_yousaidit_tfpdf_install_font', [ self::$instance, 'tfpdf_install_font' ] );
			add_action( 'wp_ajax_yousaidit_clear_transient_cache', [ self::$instance, 'clear_transient_cache' ] );
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

		$sections_values = [];
		var_dump( $sections_values );

		die();
	}

	/**
	 * Clear background task
	 *
	 * @return void
	 */
	public function clear_background_task() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for admin.', 'yousaidit-toolkit' ) );
		}
		global $wpdb;
		$options_name = [
			'background_dynamic_pdf_generator',
			'background_im_generator',
			'sync_ship_station_order',
			'background_pdf_size_calculator'
		];

		$sql = "SELECT * FROM $wpdb->options WHERE";
		foreach ( $options_name as $index => $item ) {
			if ( $index > 0 ) {
				$sql .= " OR";
			}
			$sql .= " option_name LIKE '%" . esc_sql( $item ) . "%'";
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );
		$ids     = wp_list_pluck( $results, 'option_id' );
		$ids     = count( $ids ) ? array_map( 'intval', $ids ) : [];

		$sql = "DELETE FROM $wpdb->options WHERE option_id IN(" . implode( ',', $ids ) . ")";
		$wpdb->query( $sql );

		echo count( $ids ) . " background tasks have been deleted.";
		die;
	}

	/**
	 * Clear fonts cache
	 *
	 * @return void
	 */
	public function tfpdf_clear_fonts_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for admin.', 'yousaidit-toolkit' ) );
		}
		$message = Fonts::tfpdf_clear_fonts_cache();
		echo $message;
		die;
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
		$width  = $_REQUEST['w'] ? intval( $_REQUEST['w'] ) * 11.811391223 : 0;
		$height = $_REQUEST['h'] ? intval( $_REQUEST['h'] ) * 11.811391223 : 0;
		$color  = $_REQUEST['c'] ? rawurldecode( $_REQUEST['c'] ) : 'white';

		try {
			// The Imagick constructor
			$textOnly = new Imagick();
			// Creates a new image
			$textOnly->newImage( $width, $height, new ImagickPixel( $color ) );
			$textOnly->setImageResolution( 300, 300 );
			// Sets the format of this particular image
			$textOnly->setImageFormat( 'png' );

			header( "Content-Type: image/png" );
			// Returns the image sequence as a blob
			echo $textOnly->getimageblob();
		} catch ( Exception $e ) {
		}
		die;
	}

	public function generate_dynamic_card_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You cannot perform this action.' );
		}

		$card_id = $_REQUEST['card_id'] ?? 0;
		$card    = ( new DesignerCard )->find_by_id( intval( $card_id ) );
		if ( ! $card instanceof DesignerCard ) {
			wp_die( 'No card available.' );
		}

		$pdf_id        = DynamicCard::create_card_pdf( $card );
		$pdf_file_path = wp_get_attachment_url( $pdf_id );

		try {
			$content = file_get_contents( $pdf_file_path );
			$im      = new Imagick();
			$im->setResolution( 72, 72 );
			$im->readImageBlob( $content . '[0]' );    //[0] for the first page
			$im->setImageFormat( 'jpg' );

			$envelopImage = EnvelopeColours::generate_thumb( $im, 72 );

			header( "Content-Type: image/jpeg" );
			echo $envelopImage->getImageBlob();
		} catch ( ImagickException $e ) {
			Logger::log( $e );
			echo 'Sorry, Could not generate dynamic pdf card.';
		}
		die();
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

		if ( $card->is_static_card() ) {
			$image_id   = $card->get_image_id();
			$image_path = wp_get_attachment_image_src( $image_id, 'full' );
			$content    = file_get_contents( $image_path[0] );
			$im         = new Imagick();
			try {
				$im->setResolution( 72, 72 );
				$im->readImageBlob( $content );
				$im->setImageFormat( 'jpg' );
				$imagick = EnvelopeColours::generate_thumb( $im, 72 );
				header( 'Content-Type: image/jpeg' );
				echo $imagick->getImageBlob();

				$this->replace_product_image( $imagick, $card, true );
			} catch ( ImagickException $e ) {
				var_dump( $e );
			}
			die;
		}

		$pdf_id = DynamicCard::create_card_pdf( $card );
		try {
			$new_file_path = get_attached_file( $pdf_id );
			DynamicCard::clone_pdf_to_jpg( $card, $new_file_path );
			$im = DynamicCard::pdf_to_image( $new_file_path );
			header( 'Content-Type: image/jpeg' );
			echo $im->getImageBlob();
			$this->replace_product_image( $im, $card, true );
		} catch ( ImagickException $e ) {
			var_dump( $e );
		}
		die;
	}

	/**
	 * Replace product image.
	 */
	public function replace_product_image( Imagick $imagick, DesignerCard $card, $delete_current = false ) {
		$upload_dir = Uploader::get_upload_dir();
		if ( is_wp_error( $upload_dir ) ) {
			return $upload_dir;
		}

		$product = wc_get_product( $card->get_product_id() );
		if ( $product instanceof WC_Product ) {
			$filename = sanitize_file_name( strtolower( $product->get_slug() ) . '-shop-image.webp' );
		} else {
			$filename = sanitize_file_name( strtolower( $card->get( 'card_title' ) ) . '-shop-image.webp' );
		}
		$filename      = wp_unique_filename( $upload_dir, $filename );
		$directory     = rtrim( $upload_dir, DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;

		try {
			$imagick->setImageFormat( "webp" );
			$imagick->setOption( 'webp:method', '6' );
			$imagick->writeImage( $new_file_path );

			$upload_dir = wp_upload_dir();
			$data       = array(
				'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path ),
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/webp',
			);

			$attachment_id = wp_insert_attachment( $data, $new_file_path );

			if ( ! is_wp_error( $attachment_id ) ) {
				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file_path );
				wp_update_attachment_metadata( $attachment_id, $attach_data );

				if ( $delete_current ) {
					wp_delete_attachment( $product->get_image_id(), true );
				}
				set_post_thumbnail( $product->get_id(), $attachment_id );
			}

			return $attachment_id;
		} catch ( ImagickException $e ) {
		}
	}

	/**
	 * Install font for Dompdf
	 *
	 * @return void
	 */
	public function dompdf_install_font() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for admin.', 'yousaidit-toolkit' ) );
		}
		foreach ( Fonts::get_list() as $item ) {
			$fontFamily = str_replace( ' ', '_', strtolower( $item['label'] ) );
			$path       = $item['fontFilePath'];
			if ( ! file_exists( $path ) ) {
				echo 'Source file is not available: ' . $item['fontFilePath'];
				continue;
			}

			try {
				echo '<pre><code>';
				Fonts::install_font_family( $fontFamily, $path, $path, $path, $path );
				echo '</code>Font file generated successfully for font: ' . $item['label'] . '<code>';
				echo '</code></pre>';
			} catch ( Exception $e ) {
				Logger::log( $e );
				echo $e->getMessage();
			}
		}
		echo 'Process run successfully. You can close this window.';
		die;
	}

	/**
	 * Install font for tFPDF
	 *
	 * @return void
	 */
	public function tfpdf_install_font() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for admin.', 'yousaidit-toolkit' ) );
		}
		$fonts     = Fonts::get_list();
		$base_path = YOUSAIDIT_TOOLKIT_PATH . '/vendor/setasign/tfpdf/font/unifont/';
		if ( ! is_writable( $base_path ) ) {
			echo 'Target directory is not writable: ' . $base_path;
			die;
		}
		foreach ( $fonts as $font ) {
			if ( ! file_exists( $font['fontFilePath'] ) ) {
				echo 'Source file is not available: ' . $font['fontFilePath'];
				continue;
			}
			if ( ! copy( $font['fontFilePath'], $base_path . $font['fileName'] ) ) {
				echo 'Fail to copy file: ' . $font['fontFilePath'];
			}
		}
		echo 'Process run successfully. You can close this window.';
		die;
	}

	/**
	 * Clear transient cache
	 *
	 * @return void
	 */
	public function clear_transient_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for admin.', 'yousaidit-toolkit' ) );
		}
		$static_names = [
			'get_shipstation_stores',
			'order_items_by_card_sizes',
			'__jwt_auth_token',
			'postcard_product',
			'hide_from_shop_products_ids',
			'rude_products_ids',
			'wc_general_data',
		];

		foreach ( $static_names as $static_name ) {
			delete_transient( $static_name );
		}

		echo count( $static_names ) . ' transient deleted.';


		$dynamic_names = [
			'yousaidit_preview_card_',
			'safe_search_',
			'ship_station_orders_',
			'shipstation_order_',
		];

		global $wpdb;
		$sql = "DELETE FROM `$wpdb->options` WHERE";
		foreach ( $dynamic_names as $index => $dynamic_name ) {
			if ( $index > 0 ) {
				$sql .= " OR";
			}
			$sql .= $wpdb->prepare( " `option_name` LIKE %s", '%' . $dynamic_name . '%' );
		}

		echo count( $static_names ) . ' dynamic transient deleted.';

		die;
	}
}
