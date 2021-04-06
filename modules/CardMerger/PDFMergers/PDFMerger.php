<?php

namespace YouSaidItCards\Modules\CardMerger\PDFMergers;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\PdfReader\PdfReaderException;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\InnerMessage\PdfGenerator;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\Modules\OrderDispatcher\QtyCode;
use YouSaidItCards\ShipStation\OrderItem;

class PDFMerger {
	/**
	 * @var bool
	 */
	protected static $print_inner_message = false;

	/**
	 * Array of files to be merged
	 *
	 * @var array
	 */
	protected $files = [];

	/**
	 * PDF orientation
	 *
	 * @var string
	 */
	protected $orientation = 'p';

	/**
	 * PDF unit
	 *
	 * @var string
	 */
	protected $unit = 'mm';

	/**
	 * PDF size
	 *
	 * @var string|array
	 */
	protected $size = 'a4';

	/**
	 * Card width
	 *
	 * @var int|float
	 */
	protected $card_width = 0;

	/**
	 * Card height
	 *
	 * @var int|float
	 */
	protected $card_height = 0;

	/**
	 * Top Padding
	 *
	 * @var int|float
	 */
	protected $top_padding = 0;

	/**
	 * Right padding
	 *
	 * @var int|float
	 */
	protected $right_padding = 0;

	/**
	 * Bottom Padding
	 *
	 * @var int|float
	 */
	protected $bottom_padding = 0;

	/**
	 * @param OrderItem[] $order_items
	 * @param Fpdi $pdf
	 * @param array $args
	 *
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	public static function add_page_to_pdf( array $order_items, Fpdi &$pdf, array $args = [] ) {
		$im   = isset( $args['inner_message'] ) && $args['inner_message'] == true;
		$type = isset( $args['type'] ) && in_array( $args['type'], [ 'both', 'pdf', 'im' ] ) ? $args['type'] : 'both';

		foreach ( $order_items as $order_item ) {
			if ( ! filter_var( $order_item->get_pdf_url(), FILTER_VALIDATE_URL ) ) {
				continue;
			}
			if ( ( $im && ! $order_item->has_inner_message() ) || ( ! $im && $order_item->has_inner_message() ) ) {
				continue;
			}

			if ( isset( $args['card_width'], $args['card_height'] ) ) {
				if ( ! ( $args['card_width'] == $order_item->get_pdf_width() && $args['card_height'] == $order_item->get_pdf_height() ) ) {
					continue;
				}
			}

			foreach ( range( 1, $order_item->get_quantity() ) as $qty ) {
				if ( in_array( $type, [ 'both', 'pdf' ] ) ) {
					// Import card
					self::import_base_card( $pdf, $order_item );

					// Add qr code
					self::add_qr_code( $pdf, $order_item->get_ship_station_order_id(),
						$order_item->get_pdf_width(), $order_item->get_pdf_height() );
					// Add total quantity
					$string = sprintf( "%s - %s", $order_item->get_total_quantities_in_order(),
						$order_item->get_ship_station_order_id() );
					self::add_total_qty( $pdf, $order_item->get_pdf_height(), $string );
				}

				if ( in_array( $type, [ 'both', 'im' ] ) ) {
					// Add new page for inner message
					self::_add_message( $pdf, $order_item, $order_item->get_pdf_width(), $order_item->get_pdf_height() );
				}
			}
		}
	}

	/**
	 * @param Fpdi $pdf
	 * @param OrderItem $order_item
	 * @param int|float $card_width
	 * @param int|float $card_height
	 */
	protected static function add_inner_message( Fpdi &$pdf, OrderItem $order_item, $card_width, $card_height ) {
		$pdf->addPage();

		$lines        = $order_item->get_inner_message();
		$lines        = preg_split( "/\r\n|\n|\r/", $lines );
		$total_lines  = count( $lines );
		$line_start_y = absint( ( $card_height / 2 ) - ( $total_lines * 2 ) );

		$inner_info = $order_item->get_inner_message_info();
		$font_size  = isset( $inner_info['size'] ) ? intval( $inner_info['size'] ) : 14;
		$hex_color  = isset( $inner_info['color'] ) ? $inner_info['color'] : '#000000';
		$align      = isset( $inner_info['align'] ) ? $inner_info['align'] : 'C';

		foreach ( $lines as $index => $line ) {
			$text      = trim( $line );
			$font_size = self::px_to_points( $font_size );
			$pdf->SetFont( 'Arial', '', $font_size );
			$pdf->SetTextColor( 0, 0, 0 );
			$pdf->SetLineWidth( 1 );
			$pdf->SetX( ( $card_width / 2 ) );
			$pdf->SetY( $line_start_y + ( $index * 10 ), false );

			$text_width = $pdf->GetStringWidth( $text );

			$body_width  = round( $card_width / 2 ) - ( 10 * 2 );
			$should_wrap = $body_width < $text_width;

			if ( $should_wrap ) {
				$pdf->MultiCell( $body_width, 10, $text, 0, 'C' );
			} else {
				$pdf->Cell( $text_width, 1, $text, 0, 0, 'C' );
			}
		}
	}

	/**
	 * @param int $px
	 *
	 * @return float|int
	 */
	protected static function px_to_points( $px ) {
		return intval( $px ) * .75;
	}

	/**
	 * @param Fpdi $pdf
	 * @param int|string $order_id
	 * @param int $card_width
	 * @param int $card_height
	 */
	protected static function add_qr_code( Fpdi &$pdf, $order_id, $card_width, $card_height ) {
		$qr_size = 10;

		$pdf->Image(
			QrCode::get_qr_code_file( $order_id ), // QR file Path
			( ( $card_width / 2 ) - ( $qr_size + 10 ) ), // x position
			( $card_height - ( $qr_size + 5 ) ), // y position
			$qr_size, $qr_size, 'jpeg' );
	}

	/**
	 * @param Fpdi $pdf
	 * @param int $card_height
	 * @param int|string $total_qty
	 */
	protected static function add_total_qty( Fpdi &$pdf, $card_height, $total_qty = 0 ) {
		$file_path  = QtyCode::get_qty_code_file( $total_qty );
		$size       = getimagesize( $file_path );
		$max_height = 2;
		$pdf->Image(
			$file_path, // QR file Path
			10, // x position
			( $card_height - ( $max_height + 6 ) ), // y position
			( $max_height / $size[1] * $size[0] ), // width
			$max_height, // height
			'png'
		);
	}

	/**
	 * @param Fpdi $pdf
	 * @param OrderItem $order_item
	 * @param $card_width
	 * @param $card_height
	 *
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	private static function _add_message( Fpdi &$pdf, OrderItem $order_item, $card_width, $card_height ): void {
		if ( $order_item->has_inner_message() && static::$print_inner_message ) {
			$info          = $order_item->get_inner_message_info();
			$wc_order_item = $order_item->get_wc_order_item();
			if ( $wc_order_item instanceof WC_Order_Item_Product && count( $info ) > 1 ) {
				$file   = PdfGenerator::get_pdf_for_order_item( $wc_order_item );
				$stream = StreamReader::createByFile( $file );
				$pdf->addPage();
				$totalPagesCount = $pdf->setSourceFile( $stream );
				$pageId          = $pdf->importPage( $totalPagesCount, PageBoundaries::MEDIA_BOX );
				list( $card_width, $card_height ) = $pdf->getImportedPageSize( $pageId );
				$pdf->useImportedPage( $pageId, 0, 0, $card_width, $card_height );
			} else {
				static::add_inner_message( $pdf, $order_item, $card_width, $card_height );
			}
		}
	}

	/**
	 * @param Fpdi $pdf
	 * @param OrderItem $order_item
	 *
	 * @return mixed
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	private static function import_base_card( Fpdi &$pdf, OrderItem $order_item ) {
		$cardContent = file_get_contents( $order_item->get_pdf_url(), 'rb' );
		$stream      = StreamReader::createByString( $cardContent );
		$pdf->addPage();
		$pdf->setSourceFile( $stream );
		$pageId = $pdf->importPage( 1, PageBoundaries::MEDIA_BOX );
		list( $card_width, $card_height ) = $pdf->getImportedPageSize( $pageId );
		$pdf->useImportedPage( $pageId, 0, 0, $card_width, $card_height );

		return $card_height;
	}

	/**
	 * Get PDf orientation
	 *
	 * @return string
	 */
	public function get_orientation() {
		return $this->orientation;
	}

	/**
	 * Set PDF orientation
	 *
	 * @param string $orientation
	 *
	 * @return self
	 */
	public function set_orientation( $orientation ) {
		$orientation = strtolower( $orientation );
		if ( in_array( $orientation, [ 'p', 'portrait', 'l', 'landscape' ] ) ) {
			$this->orientation = $orientation;
		}

		return $this;
	}

	/**
	 * Get PDF size
	 *
	 * @return string
	 */
	public function get_size() {
		return $this->size;
	}

	/**
	 * Set PDF size
	 *
	 * @param string|array $size
	 *
	 * @return self
	 */
	public function set_size( $size ) {
		if ( is_string( $size ) ) {
			$size = strtolower( $size );
			if ( in_array( $size, [ 'a3', 'a4', 'a5', 'letter', 'legal' ] ) ) {
				$this->size = $size;
			}
		}

		if ( is_array( $size ) && isset( $size[1], $size[0] ) ) {
			$this->size = $size;
		}

		return $this;
	}

	/**
	 * Get unit
	 *
	 * @return string
	 */
	public function get_unit() {
		return $this->unit;
	}

	/**
	 * Set unit
	 *
	 * @param string $unit
	 *
	 * @return self
	 */
	public function set_unit( $unit ) {
		if ( in_array( $unit, [ 'pt', 'mm', 'cm', 'in' ] ) ) {
			$this->unit = $unit;
		}

		return $this;
	}

	/**
	 * Get formatted files
	 * Get formatted files
	 *
	 * @return array
	 */
	public function get_formatted_files() {
		$_files = [];
		foreach ( $this->get_files() as $index => $file ) {
			if ( $index % 2 === 0 ) {
				$_files[ $index ][] = $file;
			} else {
				$_files[ $index - 1 ][] = $file;
			}
		}

		return $_files;
	}

	/**
	 * Get files
	 *
	 * @return array
	 */
	public function get_files() {
		return $this->files;
	}

	/**
	 * Set files
	 *
	 * @param array $files
	 *
	 * @return self
	 */
	public function set_files( array $files ) {
		$this->files = $files;

		return $this;
	}

	/**
	 * Get card width
	 *
	 * @return float|int
	 */
	public function get_card_width() {
		return $this->card_width;
	}

	/**
	 * Set card width
	 *
	 * @param float|int $card_width
	 *
	 * @return self
	 */
	public function set_card_width( $card_width ) {
		$this->card_width = $card_width;

		return $this;
	}

	/**
	 * Get card height
	 *
	 * @return float|int
	 */
	public function get_card_height() {
		return $this->card_height;
	}

	/**
	 * Set card height
	 *
	 * @param float|int $card_height
	 *
	 * @return self
	 */
	public function set_card_height( $card_height ) {
		$this->card_height = $card_height;

		return $this;
	}

	/**
	 * Get top padding
	 *
	 * @return float|int
	 */
	public function get_top_padding() {
		return $this->top_padding;
	}

	/**
	 * Set top padding
	 *
	 * @param float|int $top_padding
	 *
	 * @return self
	 */
	public function set_top_padding( $top_padding ) {
		$this->top_padding = $top_padding;

		return $this;
	}

	/**
	 * Get right padding
	 *
	 * @return float|int
	 */
	public function get_right_padding() {
		return $this->right_padding;
	}

	/**
	 * Set right padding
	 *
	 * @param float|int $right_padding
	 *
	 * @return self
	 */
	public function set_right_padding( $right_padding ) {
		$this->right_padding = $right_padding;

		return $this;
	}

	/**
	 * Get bottom padding
	 *
	 * @return float|int
	 */
	public function get_bottom_padding() {
		return $this->bottom_padding;
	}

	/**
	 * Set bottom padding
	 *
	 * @param float|int $bottom_padding
	 *
	 * @return self
	 */
	public function set_bottom_padding( $bottom_padding ) {
		$this->bottom_padding = $bottom_padding;

		return $this;
	}

	/**
	 * Get PDF width and height
	 *
	 * @return mixed
	 */
	public function get_pdf_width_and_height() {
		$stdPageSizes = [
			'a3'     => [ 841.89, 1190.55 ],
			'a4'     => [ 595.28, 841.89 ],
			'a5'     => [ 420.94, 595.28 ],
			'letter' => [ 612, 792 ],
			'legal'  => [ 612, 1008 ]
		];
		$size         = $this->get_size();
		if ( is_array( $size ) ) {
			return $size;
		}

		return $stdPageSizes[ $size ];
	}

	/**
	 * Get PDF width
	 *
	 * @return float|int
	 */
	public function get_pdf_width() {
		$pdf_sizes = $this->get_pdf_width_and_height();

		return $pdf_sizes[0];
	}

	/**
	 * Get PDf height
	 *
	 * @return float|int
	 */
	public function get_pdf_height() {
		$pdf_sizes = $this->get_pdf_width_and_height();

		return $pdf_sizes[1];
	}

	/**
	 * Get x point for PDF items
	 * New PDF Width - (Padding Right + PDF1 Width)
	 *
	 * @return float|int
	 */
	public function get_x_point() {
		$pdf_sizes = $this->get_pdf_width_and_height();

		return $pdf_sizes[0] - ( $this->get_right_padding() + $this->get_card_width() );
	}

	/**
	 * Get y point for first PDF
	 *
	 * @return float
	 */
	public function get_first_y_point() {
		return $this->get_top_padding();
	}

	/**
	 * Get y point for second PDF
	 * New PDF Height - (Second PDf Height + Bottom Padding)
	 *
	 * @return float|int
	 */
	public function get_second_y_point() {
		$pdf_sizes = $this->get_pdf_width_and_height();

		return $pdf_sizes[1] - ( $this->get_card_height() + $this->get_bottom_padding() );
	}

	/**
	 * Get new PDF based on cards
	 *
	 * @return Fpdi
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfTypeException
	 * @throws PdfReaderException
	 */
	public function get_pdf() {
		$pdf   = $this->get_fpdi_instance();
		$files = $this->get_formatted_files();

		foreach ( $files as $index => $file ) {

			$pdf1_path = isset( $file[0] ) ? $file[0] : null;
			$pdf2_path = isset( $file[1] ) ? $file[1] : null;

			$pdf->addPage();

			if ( ! empty( $pdf1_path ) ) {
				$file1Content = file_get_contents( $pdf1_path, 'rb' );
				$stream1      = StreamReader::createByString( $file1Content );
				$pdf->setSourceFile( $stream1 );
				$page1Id = $pdf->importPage( 1, PageBoundaries::MEDIA_BOX );
				$pdf->useImportedPage( $page1Id, self::get_x_point(), self::get_first_y_point(), $this->get_card_width() );
			}

			if ( ! empty( $pdf2_path ) ) {
				$file2Content = file_get_contents( $pdf2_path, 'rb' );
				$stream2      = StreamReader::createByString( $file2Content );
				$pdf->setSourceFile( $stream2 );
				$page2Id = $pdf->importPage( 1, PageBoundaries::MEDIA_BOX );
				$pdf->useImportedPage( $page2Id, self::get_x_point(), self::get_second_y_point(), $this->get_card_width() );
			}
		}

		return $pdf;
	}

	/**
	 * Output PDF
	 *
	 * @param string $destination
	 * @param string $name
	 */
	public function output( $destination = 'D', $name = 'doc.pdf' ) {
		try {
			$pdf = $this->get_pdf();
			$pdf->Output( $destination, $name );
		} catch ( CrossReferenceException $e ) {
		} catch ( FilterException $e ) {
		} catch ( PdfTypeException $e ) {
		} catch ( PdfParserException $e ) {
		} catch ( PdfReaderException $e ) {
		}
	}

	/**
	 * Get Fpdi instance
	 *
	 * @return Fpdi
	 */
	public function get_fpdi_instance(): Fpdi {
		return new Fpdi( $this->get_orientation(), $this->get_unit(), $this->get_size() );
	}
}
