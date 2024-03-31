<?php

namespace YouSaidItCards\Modules\DynamicCard;

use Imagick;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use YouSaidItCards\Modules\DynamicCard\Models\CardBackgroundOption;
use YouSaidItCards\Modules\DynamicCard\Models\CardSectionImageOption;
use YouSaidItCards\Modules\DynamicCard\Models\OrderItemDynamicCard;
use YouSaidItCards\Utils;

/**
 * ImageGenerator class
 */
class ImageGenerator {
	/**
	 * @var CardBackgroundOption
	 */
	protected $background;
	protected $sections = [];
	protected int $ppi = 300;
	private int $width_px = 0;
	protected int $height_px = 0;

	public function __construct( OrderItemDynamicCard $order_item_dynamic_card ) {
		$this->background = $order_item_dynamic_card->get_background();
		$this->sections   = $order_item_dynamic_card->get_card_sections();
		$this->width_px   = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_WIDTH_MM, $this->ppi );
		$this->height_px  = Utils::millimeter_to_pixels( Utils::SQUARE_CARD_HEIGHT_MM, $this->ppi );
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
				$im->scaleImage(
					Utils::millimeter_to_pixels( $section->get_image_area_width_mm() ),
					Utils::millimeter_to_pixels( $section->get_image_area_height_mm() ),
					true
				);

//				var_dump( $im->getImageWidth(), $im->getImageHeight() );
//				die;

				$background->compositeImage( $im, Imagick::COMPOSITE_DEFAULT, 0, 0 );
			}
		}

		return $background;
	}
}
