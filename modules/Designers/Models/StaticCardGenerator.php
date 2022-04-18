<?php

namespace YouSaidItCards\Modules\Designers\Models;

use Stackonet\WP\Framework\Supports\Validate;
use tFPDF;

class StaticCardGenerator {
	protected $orientation = 'L';
	protected $unit = 'mm';
	protected $size = [ 306, 156 ];
	protected $background_image = null;
	protected $background_image_ext = 'jpeg';
	protected $qrcode_image = null;
	protected $qrcode_image_ext = 'jpeg';
	protected $product_sku = 'test-sku';
	protected $designer_logo = null;
	protected $designer_logo_ext = 'jpeg';
	protected $designer_logo_width = 0;
	protected $designer_logo_height = 0;
	protected $order_id = 0;
	protected $order_qty = 0;
	protected $pdf_id = 0;

	/**
	 * Set background image
	 *
	 * @param string $path
	 * @param string|null $ext
	 */
	public function set_background_image( string $path, ?string $ext = '' ) {
		$this->background_image = $path;
		if ( empty( $ext ) ) {
			$file_ext = explode( '.', basename( $path ) );
			$ext      = end( $file_ext );
		}
		$this->background_image_ext = $ext;
	}

	public function set_designer_logo( string $url_or_path, int $width, int $height, ?string $ext = '' ) {
		$this->designer_logo        = $url_or_path;
		$this->designer_logo_width  = $width;
		$this->designer_logo_height = $height;
		if ( empty( $ext ) ) {
			$file_ext = explode( '.', basename( $url_or_path ) );
			$ext      = end( $file_ext );
		}
		$this->designer_logo_ext = $ext;
	}

	/**
	 * @param string $sku
	 */
	public function set_product_sku( string $sku ) {
		$this->product_sku = $sku;
	}

	public function can_generate_pdf(): bool {
		return file_exists( $this->background_image ) || Validate::url( $this->background_image );
	}

	public function pdf( array $args = [] ) {
		if ( ! $this->can_generate_pdf() ) {
			return new \WP_Error( 'incomplete_data', 'Incomplete data' );
		}
		$fpd = new tFPDF( $this->orientation, $this->unit, $this->size );
		// Add page
		$fpd->AddPage();

		// Add background
		$this->addBackground( $fpd );

		// Add company logo
		$this->addCompanyLogo( $fpd );

		// Add product sku
		$this->addProductSku( $fpd );

		// Add developer logo
		$this->addDesignerLogo( $fpd );

		// Add total qty
//		$this->addTotalQty( $fpd );

		// Add qr code
//		$this->addQrCode( $fpd );

		$fpd->Output( $args['dest'] ?? '', $args['name'] ?? '' );

		if ( 'F' === $args['dest'] ) {
			// Set correct file permissions.
			$stat  = stat( dirname( $args['name'] ) );
			$perms = $stat['mode'] & 0000666;
			@chmod( $args['name'], $perms );
			$this->pdf_id = $this->add_attachment_data( $args['name'] );
		}
	}

	/**
	 * Add company logo
	 *
	 * @param tFPDF $fpd
	 */
	private function addCompanyLogo( tFPDF &$fpd ) {
		$logo_path  = YOUSAIDIT_TOOLKIT_PATH . '/assets/static-images/logo-yousaidit.png';
		$image_info = [ 342, 142 ];
		$width      = ( $fpd->GetPageWidth() / 2 ) / 3;
		$height     = $image_info[1] / $image_info[0] * $width;
		$x_pos      = ( $fpd->GetPageWidth() / 4 ) - ( $width / 2 );
		$y_pos      = ( $fpd->GetPageHeight() - $height ) - ( 20 );
		$fpd->Image( $logo_path, $x_pos, $y_pos, $width, $height );
	}

	/**
	 * @param tFPDF $fpd
	 *
	 * @return void
	 */
	private function addProductSku( tFPDF &$fpd ) {
		$fpd->SetFont( 'arial', '', 10 );
		$fpd->SetTextColor( 0, 0, 0 );
		$text  = "Code: " . $this->product_sku;
		$x_pos = ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 );
		$y_pos = $fpd->GetPageHeight() - 10;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @return int
	 */
	public function get_pdf_id(): int {
		return $this->pdf_id;
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addTotalQty( tFPDF &$fpd ): void {
		$text = sprintf( "%s - %s", $this->order_qty, $this->order_id );
		$fpd->Text( 10, $fpd->GetPageHeight() - 10, $text );
	}

	/**
	 * @param tFPDF $pdf
	 */
	private function addQrCode( tFPDF &$pdf ) {
		if ( ! $this->qrcode_image ) {
			return;
		}
		$qr_size = 10;

		$pdf->Image(
			$this->qrcode_image, // QR file Path
			( ( $this->size[0] / 2 ) - ( $qr_size + 10 ) ), // x position
			( $this->size[1] - ( $qr_size + 5 ) ), // y position
			$qr_size, $qr_size, $this->qrcode_image_ext );
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addDesignerLogo( tFPDF &$fpd ) {
		if ( ! ( $this->designer_logo && $this->designer_logo_width && $this->designer_logo_height ) ) {
			return;
		}
		$logo_size   = 40;
		$logo_height = $this->designer_logo_height / $this->designer_logo_width * $logo_size;
		$x_position  = ( $fpd->GetPageWidth() / 4 ) - ( $logo_size / 2 );
		$y_position  = ( $fpd->GetPageHeight() / 4 ) - ( $logo_height / 2 );
		$fpd->Image( $this->designer_logo, $x_position, $y_position, $logo_size, $logo_height, $this->designer_logo_ext );

		$text = "Designed by";
		$fpd->SetFont( 'arial', '', 11 );
		$fpd->SetTextColor( 0, 0, 0 );
		$x_pos = ( $fpd->GetPageWidth() / 4 ) - ( $fpd->GetStringWidth( $text ) / 2 );
		$y_pos = $y_position - 5;
		$fpd->Text( $x_pos, $y_pos, $text );
	}

	/**
	 * @param tFPDF $fpd
	 */
	private function addBackground( tFPDF &$fpd ): void {
		$fpd->Image( $this->background_image, $fpd->GetPageWidth() / 2, 0,
			$fpd->GetPageWidth() / 2, $fpd->GetPageHeight(), $this->background_image_ext );
	}

	public function get_pdf_path(): string {
		$dir        = date( 'Y/m', time() );
		$upload_dir = wp_upload_dir();
		$media_dir  = join( DIRECTORY_SEPARATOR, array( $upload_dir['basedir'], $dir ) );
		$filename   = wp_unique_filename( $media_dir, sprintf( "%s.pdf", strtoupper( $this->product_sku ) ) );

		return $media_dir . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Add attachment data
	 *
	 * @param string|null $file_path
	 *
	 * @return int|WP_Error
	 */
	public function add_attachment_data( ?string $file_path = null ) {
		if ( empty( $file_path ) ) {
			$file_path = $this->get_pdf_path();
		}
		$upload_dir = wp_upload_dir();
		$data       = [
			'guid'           => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path ),
			'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_text_field( $this->product_sku ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => 'application/pdf',
		];

		$attachment_id = wp_insert_attachment( $data, $file_path );

		if ( ! is_wp_error( $attachment_id ) ) {
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		}

		return $attachment_id;
	}
}
