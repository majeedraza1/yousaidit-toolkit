<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use YouSaidItCards\Modules\DynamicCard\Models\CardSectionImageOption;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Utils;

/**
 * ImageGenerator class
 */
class ImageGenerator {
	/**
	 * @var CardSectionImageOption
	 */
	protected $image_option;
	protected int $ppi = 300;
	private int $width_px = 0;
	protected int $height_px = 0;
	protected bool $debug = false;

	public function __construct( CardSectionImageOption $image_option, bool $debug = false ) {
		$this->image_option = $image_option;
		$this->width_px     = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_WIDTH_MM, $this->ppi );
		$this->height_px    = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_HEIGHT_MM, $this->ppi );
		$this->debug        = $debug;
	}

	public static function generate_image() {

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
