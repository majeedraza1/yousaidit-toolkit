<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Stackonet\WP\Framework\Media\UploadedFile;
use Stackonet\WP\Framework\Media\Uploader;
use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

class OrderPostcardController extends ApiController {
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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'me/postcard-orders', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
			]
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$files = UploadedFile::getUploadedFiles();
		$card  = isset( $files['card'] ) ? $files['card'] : false;
		if ( ! $card instanceof UploadedFile ) {
			return $this->respondUnprocessableEntity();
		}

		if ( ! $card->isPdf() ) {
			return $this->respondUnprocessableEntity( 'invalid_file', 'Only PDF is allowed.' );
		}

		$card_width  = 0;
		$card_height = 0;
		$stream      = StreamReader::createByFile( $card->getFile() );
		$pdf         = new Fpdi();
		try {
			$totalPagesCount = $pdf->setSourceFile( $stream );
			$pageId          = $pdf->importPage( $totalPagesCount, PageBoundaries::MEDIA_BOX );
			list( $card_width, $card_height ) = $pdf->getImportedPageSize( $pageId );
		} catch ( PdfParserException $e ) {
		} catch ( PdfReaderException $e ) {
		}

		$card_size      = $request->get_param( 'card_size' );
		$available_size = [
			'square:1' => [ 300, 150 ],
			'square:2' => [ 306, 156 ],// for color card
			'a4:1'     => [ 426, 303 ],
			'a5:1'     => [ 303, 216 ],
			'a6:1'     => [ 216, 154 ],
		];

		$card_key = null;
		foreach ( $available_size as $key => $size ) {
			if ( round( $card_width ) == $size[0] && round( $card_height ) == $size[1] ) {
				$card_key = $key;
			}
		}

		if ( empty( $card_key ) ) {
			return $this->respondUnprocessableEntity( 'unsupported_card_size', 'Unsupported card size' );
		}

		$pdf_id = Uploader::uploadSingleFile( $card );

		return $this->respondOK( [ $pdf_id ] );
	}
}
