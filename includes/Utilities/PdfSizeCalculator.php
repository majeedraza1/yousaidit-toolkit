<?php

namespace YouSaidItCards\Utilities;

use Exception;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PageBoundaries;
use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\Validate;

class PdfSizeCalculator extends BackgroundProcess {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	public static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_pdf_size_calculator';

	/**
	 * Calculate pdf width and height
	 *
	 * @param int $pdf_id The pdf id.
	 *
	 * @return bool
	 */
	public static function calculate_pdf_width_and_height( int $pdf_id ): bool {
		$pdf_url = wp_get_attachment_url( $pdf_id );
		if ( ! Validate::url( $pdf_url ) ) {
			Logger::log( 'PDF url is not valid for id #' . $pdf_id );

			return false;
		}
		$cardContent = file_get_contents( $pdf_url, 'rb' );
		$stream      = StreamReader::createByString( $cardContent );
		$pdf         = new Fpdi();
		try {
			$totalPagesCount = $pdf->setSourceFile( $stream );
			$pageId          = $pdf->importPage( $totalPagesCount, PageBoundaries::MEDIA_BOX );
			list( $card_width, $card_height ) = $pdf->getImportedPageSize( $pageId );
			update_post_meta( $pdf_id, '_pdf_width_millimeter', round( $card_width ) );
			update_post_meta( $pdf_id, '_pdf_height_millimeter', round( $card_height ) );

			$total = (int) get_option( '_pdf_size_calculator_total_items', 0 );
			if ( $total > 0 ) {
				update_option( '_pdf_size_calculator_total_items', ( $total - 1 ), false );
			} else {
				delete_option( '_pdf_size_calculator_total_items' );
			}

			return false;
		} catch ( Exception $e ) {
			Logger::log( $e );

			return false;
		}
	}

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'dispatch_data' ] );

			add_action( 'admin_notices', [ self::$instance, 'add_admin_upgrade_status_notice' ] );
			add_action( 'wp_ajax_pdf_size_calculator_upgrade', [ self::$instance, 'upgrade_database' ] );
		}

		return self::$instance;
	}

	/**
	 * Save and run background on shutdown of all code
	 */
	public function dispatch_data() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	/**
	 * Add upgrade notice
	 */
	public function add_admin_upgrade_status_notice() {
		if ( 'yes' != get_option( '_pdf_size_calculator_started' ) ) {
			$url = add_query_arg( [ 'action' => 'pdf_size_calculator_upgrade', ], admin_url( 'admin-ajax.php' ) );
			$url = wp_nonce_url( $url, 'pdf_size_calculator_upgrade', '_token' );

			$html = '<div>';
			$html .= '<span>Stackonet Yousaidit Toolkit need to upgrade database.</span><br>';
			$html .= '<span><a class="button" target="_blank" href="' . $url . '">Update Now</a></span>';
			$html .= '</div>';
			echo $this->add_admin_notice( $html );
		}

		$total = (int) get_option( '_pdf_size_calculator_total_items', 0 );
		if ( ! empty( $total ) ) {
			$html = '<div>';
			$html .= '<span>Stackonet Yousaidit Toolkit performing background task. </span>';
			$html .= '<span>' . $total . ' items need to process. This may take some time based on your server capacity.</span>';
			$html .= '</div>';
			echo $this->add_admin_notice( $html );
		}
	}

	/**
	 * Add admin notice
	 *
	 * @param string $content
	 * @param bool $dismissible
	 *
	 * @return string
	 */
	public static function add_admin_notice( $content, $dismissible = true ) {
		$html = '<div id="message" class="notice notice-info is-dismissible">';
		$html .= '<p>' . ( $content ) . '</p>';
		if ( $dismissible ) {
			$html .= '<button type="button" class="notice-dismiss">';
			$html .= '<span class="screen-reader-text">Dismiss this notice.</span>';
			$html .= '</button>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Handle upgrade database
	 */
	public function upgrade_database() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sorry only admin can perform upgrade.' );
		}

		$nonce = $_REQUEST['_token'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'pdf_size_calculator_upgrade' ) ) {
			wp_die( 'Sorry, Invalid URL.' );
		}

		if ( 'yes' != get_option( '_pdf_size_calculator_started' ) ) {
			static::run_background_task();
		}

		wp_die( 'Upgrade has been set.', 'Upgrade Status', [
			'link_url'  => admin_url( 'admin.php?page=stackonet-support-ticket' ),
			'link_text' => 'Back to dashboard',
			'back_link' => true,
		] );
	}

	/**
	 * Get PDF ids
	 *
	 * @return array
	 */
	public static function get_pdf_ids() {
		global $wpdb;
		$sql = "SELECT pm.meta_value FROM {$wpdb->postmeta} AS pm";
		$sql .= " WHERE pm.meta_key = '_pdf_id'";

		$results = $wpdb->get_results( $sql, ARRAY_A );
		$items   = [];
		foreach ( $results as $result ) {
			if ( empty( $result['meta_value'] ) ) {
				continue;
			}
			$items[] = intval( $result['meta_value'] );
		}

		return $items;
	}

	/**
	 * Run background task
	 */
	public static function run_background_task() {
		$ids = static::get_pdf_ids();
		update_option( '_pdf_size_calculator_total_items', count( $ids ), false );
		update_option( '_pdf_size_calculator_started', 'yes', false );

		foreach ( $ids as $pdf_id ) {
			if ( $pdf_id ) {
				static::init()->push_to_queue( [ 'pdf_id' => $pdf_id ] );
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$pdf_id = isset( $item['pdf_id'] ) ? intval( $item['pdf_id'] ) : 0;
		if ( $pdf_id ) {
			self::calculate_pdf_width_and_height( $pdf_id );
		}

		return false;
	}
}
