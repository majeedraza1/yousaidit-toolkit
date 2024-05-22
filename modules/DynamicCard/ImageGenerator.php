<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use YouSaidItCards\Modules\DynamicCard\Models\CardBackgroundOption;
use YouSaidItCards\Modules\DynamicCard\Models\CardSectionImageOption;
use YouSaidItCards\Modules\DynamicCard\Models\OrderItemDynamicCard;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Utils;

/**
 * ImageGenerator class
 */
class ImageGenerator {
	/**
	 * @var CardBackgroundOption
	 */
	protected $background;
	protected array $sections = [];
	protected int $ppi = 300;
	private int $width_px = 0;
	protected int $height_px = 0;
	protected bool $debug = false;

	public function __construct( OrderItemDynamicCard $order_item_dynamic_card, bool $debug = false ) {
		$this->background = $order_item_dynamic_card->get_background();
		$this->sections   = $order_item_dynamic_card->get_card_sections();
		$this->width_px   = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_WIDTH_MM, $this->ppi );
		$this->height_px  = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_HEIGHT_MM, $this->ppi );
		$this->debug      = $debug;
	}

	/**
	 * Get background image
	 *
	 * @throws ImagickException
	 * @throws ImagickPixelException
	 */
	public function get_background_image(): Imagick {
		$imagick = new Imagick();
		$imagick->setResolution( $this->ppi, $this->ppi );
		if ( $this->background->is_image_background() ) {
			$imagick->readImageBlob( file_get_contents( $this->background->get_image_src() ) );
		} else {
			$imagick->newImage( $this->width_px, $this->height_px,
				new ImagickPixel( $this->background->get_hex_color() ) );
		}
		$imagick->setImageFormat( 'png' );

		return $imagick;
	}

	/**
	 * @throws ImagickException
	 * @throws ImagickPixelException
	 */
	public function generate_image_card(): Imagick {
		$background = $this->get_background_image();
		foreach ( $this->sections as $section ) {
			if ( $section instanceof CardSectionImageOption ) {
				$zoom = $section->get_user_zoom();

				$im = new Imagick();
				$im->readImageBlob( file_get_contents( $section->get_image_url() ) );
				//@TODO need to zoom scaleImage if image is small
				$_zoom       = ( 100 + $zoom ) / 100;
				$crop_width  = intval( $im->getImageWidth() / $_zoom );
				$crop_height = intval( $im->getImageHeight() / $_zoom );

				$computed_x = $section->get_computed_position_from_left_mm();
				$computed_y = $section->get_computed_position_from_top_mm();
				$crop_x_pos = Utils::millimeter_to_pixels( $computed_x );
				$crop_y_pos = Utils::millimeter_to_pixels( $computed_y );

				if ( $crop_x_pos <= 0 && $crop_y_pos <= 0 ) {
					$im->cropImage( $crop_width, $crop_height, absint( $crop_x_pos ), absint( $crop_y_pos ) );
				} else {
					$crop_x_pos = ( $im->getImageWidth() - $crop_width ) / 2;
					$crop_y_pos = ( $im->getImageHeight() - $crop_height ) / 2;
					$im->cropImage( $crop_width, $crop_height, $crop_x_pos, $crop_y_pos );
				}
//				static::add_rect_structure( $im, Utils::SQUARE_CARD_WIDTH_MM, Utils::SQUARE_CARD_HEIGHT_MM, 300 );
//				static::add_rect_for_zoom( $im, $section );
				$im->scaleImage(
					Utils::millimeter_to_pixels( $section->get_image_area_width_mm() ),
					Utils::millimeter_to_pixels( $section->get_image_area_height_mm() ),
					true
				);


				$background->compositeImage( $im, Imagick::COMPOSITE_DEFAULT, 0, 0 );
			}
		}

		return $background;
	}

	/**
	 * @param  Imagick  $image
	 * @param  CardSectionImageOption  $option
	 *
	 * @return void
	 * @throws ImagickDrawException
	 * @throws ImagickException
	 */
	protected static function add_rect_for_zoom( Imagick $image, CardSectionImageOption $option ) {
		$image_info = sprintf( 'Width:%smm/%spx; Height:%smm/%spx;',
			Utils::pixels_to_millimeter( $image->getImageWidth() ), $image->getImageWidth(),
			Utils::pixels_to_millimeter( $image->getImageHeight() ), $image->getImageHeight(),
		);

		$dpi         = 300;
		$_zoom       = ( 100 + $option->get_user_zoom() ) / 100;
		$crop_width  = Utils::millimeter_to_pixels( $option->get_image_area_width_mm() / $_zoom, $dpi );
		$crop_height = Utils::millimeter_to_pixels( $option->get_image_area_height_mm() / $_zoom, $dpi );
		$computed_x  = $option->get_computed_position_from_left_mm();
		$computed_y  = $option->get_computed_position_from_top_mm();
		$crop_x_pos  = Utils::millimeter_to_pixels( $computed_x, $dpi );
		$crop_y_pos  = Utils::millimeter_to_pixels( $computed_y, $dpi );

		if ( $crop_x_pos < 0 && $crop_y_pos < 0 ) {
			$x1 = absint( $crop_x_pos );
			$y1 = absint( $crop_y_pos );
		} else {
			$x1 = $crop_x_pos;
			$y1 = $crop_y_pos;
		}
		$x2    = $x1 + $crop_height;
		$y2    = $y1 + $crop_width;
		$draw1 = new ImagickDraw();
		$draw1->setFillColor( new ImagickPixel( 'white' ) );
		$draw1->setFillOpacity( 0 );
		$draw1->setStrokeColor( new ImagickPixel( 'red' ) );
		$draw1->setStrokeWidth( 1 );
		$draw1->setStrokeOpacity( .86 );
		$draw1->rectangle( $x1, $y1, $x2, $y2 );
		$image->drawImage( $draw1 );

		$font_size_pt = 8;
		$text         = new ImagickDraw();
		$text->setFillColor( new ImagickPixel( 'red' ) );
		$font_info = Font::find_font_info( 'OpenSans' );
		$text->setFont( $font_info->get_font_path() );
		$text->setFontSize( Utils::font_size_pt_to_px( $font_size_pt ) );

		$string = sprintf( 'Width:%smm/%spx; Height:%smm/%spx; Zoom: %s; Top: %s; Left: %s',
			$option->get_image_area_width_mm(), $crop_width,
			$option->get_image_area_height_mm(), $crop_height,
			$option->get_user_zoom(),
			$option->get_computed_position_from_top_mm(),
			$option->get_computed_position_from_left_mm()
		);

		$font_metrics = Font::get_font_metrics( $font_info->get_slug(), $font_size_pt, $string );
		$x_pos        = $x1;
		$y_pos        = $y1 + $crop_height - $font_metrics['textHeight'];
		$image->annotateImage( $text, $x_pos, $y_pos, 0, $string );

		$image->annotateImage( $text, 0, $image->getImageHeight() - $font_metrics['textHeight'], 0, $image_info );
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
}
