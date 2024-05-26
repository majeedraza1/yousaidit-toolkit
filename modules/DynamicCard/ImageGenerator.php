<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Exception;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use Stackonet\WP\Framework\Media\Uploader;
use WP_Error;
use YouSaidItCards\Modules\DynamicCard\Models\CardSectionImageOption;
use YouSaidItCards\Utils;

/**
 * ImageGenerator class
 */
class ImageGenerator {
	/**
	 * @var CardSectionImageOption
	 */
	protected $image_option;
	protected int $image_id = 0;
	protected string $image_url = '';
	protected int $image_width = 0;
	protected int $image_height = 0;
	protected int $from_left = 0;
	protected int $from_top = 0;
	protected int $zoom = 0;
	protected int $ppi = 300;
	protected int $width_px = 0;
	protected int $height_px = 0;
	protected bool $debug = false;

	public function __construct( CardSectionImageOption $image_option, bool $debug = false ) {
		$this->image_option = $image_option;
		$this->set_zoom( $image_option->get_user_zoom() );
		$this->set_from_left( $image_option->get_computed_position_from_left_mm() );
		$this->set_from_top( $image_option->get_computed_position_from_top_mm() );
		$this->set_image_id( $image_option->get_image_id() );
		$this->width_px  = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_WIDTH_MM, $this->ppi );
		$this->height_px = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_HEIGHT_MM, $this->ppi );
		$this->debug     = $debug;
	}

	public function preview_image() {
		if ( ! $this->image_url ) {
			wp_die( 'No image found for the id.' );
		}

		try {
			$imagick = $this->get_editor();

			header( "Content-Type: image/png" );
			// Returns the image sequence as a blob
			echo $imagick->getimageblob();

			$imagick->destroy();
		} catch ( Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}


	public function generate_image() {
		if ( ! $this->image_url ) {
			return new WP_Error( 'invalid_image_id', 'No image found for the id.' );
		}

		if ( file_exists( $this->get_dynamic_image_path() ) ) {
			return [
				'url'  => $this->get_dynamic_image_url(),
				'path' => $this->get_dynamic_image_path()
			];
		}

		try {
			$imagick = $this->get_editor();
			$this->save_image( $imagick );

			$imagick->destroy();
		} catch ( Exception $e ) {
			return new WP_Error( 'imagick_error', $e->getMessage() );
		}

		return [
			'url'  => $this->get_dynamic_image_url(),
			'path' => $this->get_dynamic_image_path()
		];
	}

	/**
	 * @param  Imagick  $image
	 * @param  int  $page_width_mm
	 * @param  int  $page_height_mm
	 * @param  int  $ppi
	 *
	 * @return void
	 * @throws ImagickDrawException
	 * @throws ImagickException
	 */
	protected static function add_rect_structure(
		Imagick $image,
		int $page_width_mm,
		int $page_height_mm,
		int $ppi = 300
	) {
		$cell_size = 15;
		$cols      = ceil( $page_width_mm / $cell_size );
		$rows      = ceil( $page_height_mm / $cell_size );
		foreach ( range( 1, $cols ) as $col_index => $col ) {
			foreach ( range( 1, $rows ) as $row_index => $row ) {
				$draw1 = new ImagickDraw();
				$draw1->setFillColor( new ImagickPixel( 'white' ) );
				$draw1->setFillOpacity( 0 );
				$draw1->setStrokeColor( new ImagickPixel( 'red' ) );
				$draw1->setStrokeWidth( 1 );
				$draw1->setStrokeOpacity( .12 );
				$x1 = Utils::millimeter_to_pixels( $col_index * $cell_size, $ppi );
				$y1 = Utils::millimeter_to_pixels( $row_index * $cell_size, $ppi );
				$x2 = $x1 + Utils::millimeter_to_pixels( $cell_size, $ppi );
				$y2 = $y1 + Utils::millimeter_to_pixels( $cell_size, $ppi );
				$draw1->rectangle( $x1, $y1, $x2, $y2 );
				$image->drawImage( $draw1 );
			}
		}
	}

	public function get_zoom(): int {
		return $this->zoom;
	}

	public function set_zoom( int $zoom ): void {
		$this->zoom = min( 100, max( - 50, $zoom ) );
	}

	public function get_from_left(): int {
		return $this->from_left;
	}

	public function set_from_left( int $from_left ): void {
		$this->from_left = max( - 154, min( 154, $from_left ) );
	}

	public function get_from_top(): int {
		return $this->from_top;
	}

	public function set_from_top( int $from_top ): void {
		$this->from_top = max( - 156, min( 156, $from_top ) );
	}

	public function get_image_id(): int {
		return $this->image_id;
	}

	public function get_image_url(): string {
		return $this->image_url;
	}

	public function set_image_id( int $image_id ): void {
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( is_array( $src ) ) {
			$this->image_id     = $image_id;
			$this->image_url    = $src[0];
			$this->image_width  = $src[1];
			$this->image_height = $src[2];
		}
	}

	/**
	 * Get upload directory
	 *
	 * @return string|WP_Error
	 */
	public static function get_upload_dir() {
		return Uploader::get_upload_dir( 'dynamic-images' );
	}

	public function get_dynamic_image_filename(): string {
		$args = [
			'action'    => 'yousaidit_edit_image',
			'image_id'  => $this->get_image_id(),
			'zoom'      => $this->get_zoom(),
			'from-top'  => $this->get_from_top(),
			'from-left' => $this->get_from_left(),
		];

		return md5( wp_json_encode( $args ) ) . '.png';
	}

	public function get_dynamic_image_path(): string {
		return join( DIRECTORY_SEPARATOR, [ static::get_upload_dir(), $this->get_dynamic_image_filename() ] );
	}

	public function get_dynamic_image_url(): string {
		return str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $this->get_dynamic_image_path() );
	}

	/**
	 * @param  Imagick  $imagick
	 *
	 * @return void
	 * @throws ImagickException
	 */
	public function save_image( Imagick $imagick ): void {
		// Sets the format of this particular image
		$imagick->setImageFormat( 'png' );

		$image_path = $this->get_dynamic_image_path();

		$imagick->writeImage( $image_path );
		// Set correct file permissions.
		$stat  = stat( dirname( $image_path ) );
		$perms = $stat['mode'] & 0000666;
		chmod( $image_path, $perms );
	}

	/**
	 * @return Imagick
	 * @throws ImagickException
	 */
	public function get_editor(): Imagick {
		$imagick = $this->get_canvas();
		$layer   = $this->get_layer();
		$imagick->compositeImage( $layer, Imagick::COMPOSITE_DEFAULT, 0, 0 );

		return $imagick;
	}

	/**
	 * @return Imagick
	 * @throws ImagickException
	 */
	public function get_canvas(): Imagick {
		$imagick = new Imagick();
		$imagick->newImage( $this->width_px, $this->height_px, new ImagickPixel( 'transparent' ) );
		$imagick->setImageFormat( 'png' );

		return $imagick;
	}

	/**
	 * @return Imagick
	 * @throws ImagickException
	 */
	public function get_layer(): Imagick {
		$zoom_percentage = 1 + ( $this->get_zoom() / 100 );
		$from_top        = $this->get_from_top();
		$from_left       = $this->get_from_left();

		if ( $from_top > 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) ) * - 1;
		} elseif ( $from_top < 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) );
		} else {
			$top = 0;
		}

		if ( $from_left > 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) ) * - 1;
		} elseif ( $from_left < 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) );
		} else {
			$left = 0;
		}

		$layer = new Imagick();
		$layer->readImage( $this->get_image_url() );
		if ( $zoom_percentage > 1 ) {
			$this->zoom_in( $layer, $zoom_percentage, $from_left, $from_top );
		}
		if ( $zoom_percentage < 1 ) {
			$layer = $this->zoom_out( $layer, $zoom_percentage, $from_left, $from_top );
		}
		$layer->setImageFormat( 'png' );

		return $layer;
	}

	/**
	 * @param  Imagick  $layer
	 * @param $zoom_percentage
	 * @param $left
	 * @param $top
	 *
	 * @return void
	 * @throws ImagickException
	 */
	public function zoom_in( Imagick $layer, float $zoom_percentage, int $from_left, int $from_top ): void {
		$layer_width_px     = Utils::millimeter_to_pixels( $this->image_option->get_image_area_width_mm() );
		$layer_height_px    = Utils::millimeter_to_pixels( $this->image_option->get_image_area_height_mm() );
		$layer_image_width  = $layer->getImageWidth() * $zoom_percentage;
		$layer_image_height = $layer->getImageHeight() * $zoom_percentage;

		if ( $from_top > 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) ) * - 1;
		} elseif ( $from_top < 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) );
		} else {
			$top = 0;
		}

		if ( $from_left > 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) ) * - 1;
		} elseif ( $from_left < 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) );
		} else {
			$left = 0;
		}

		$layer->resizeImage(
			$layer_image_width,
			$layer_image_height,
			\Imagick::FILTER_LANCZOS,
			1,
			true
		);

		$left = $left / $layer_image_width * $layer_width_px;
		$top  = $top / $layer_image_height * $layer_height_px;

		$layer->cropImage( $layer_width_px - $left, $layer_height_px - $top, $left, $top );
		$layer->resizeImage(
			$layer_width_px,
			$layer_height_px,
			\Imagick::FILTER_LANCZOS,
			1,
			false
		);
	}

	/**
	 * @param  Imagick  $layer
	 * @param  float  $zoom_percentage
	 * @param  float  $left
	 * @param  float  $top
	 *
	 * @return Imagick
	 * @throws ImagickException
	 */
	private function zoom_out( Imagick $layer, float $zoom_percentage, float $from_left, float $from_top ): Imagick {
		$canvas = $this->get_canvas();

		if ( $from_top > 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) ) * $zoom_percentage;
		} elseif ( $from_top < 0 ) {
			$top = Utils::millimeter_to_pixels( abs( $from_top ) ) * - 1 * $zoom_percentage;
		} else {
			$top = 0;
		}

		if ( $from_left > 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) ) * $zoom_percentage;
		} elseif ( $from_left < 0 ) {
			$left = Utils::millimeter_to_pixels( abs( $from_left ) ) * - 1 * $zoom_percentage;
		} else {
			$left = 0;
		}

		$layer->resizeImage(
			$layer->getImageWidth() * $zoom_percentage,
			$layer->getImageHeight() * $zoom_percentage,
			\Imagick::FILTER_LANCZOS,
			1,
			true
		);

		$canvas->compositeImage( $layer, Imagick::COMPOSITE_BLEND, $left, $top );

		return $canvas;
	}
}
