<?php

namespace YouSaidItCards;

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

		$items = [
			[
				'label'        => 'Section 1',
				'section_type' => 'static-text',
				'position'     => [ 'top' => 30, 'left' => 10 ],
				'text'         => 'Hello',
				'textOptions'  => [
					'fontFamily' => 'Arial',
					'size'       => 96,
					'align'      => 'center',
					'color'      => '#00ff00'
				]
			],
			[
				'label'        => 'Section 1',
				'section_type' => 'static-image',
				'position'     => [ 'top' => 80, 'left' => 10 ],
				'imageOptions' => [
					'img'    => [ 'id' => 37494 ],
					'width'  => 101,
					'height' => 'auto',
					'align'  => 'center'
				]
			]
		];

		$pdf = new FreePdf();
		$pdf->generate( 'square', $items );
		die();
	}

	public function generate_preview_card() {
		$card_size       = $_POST['card_size'] ?? 'square';
		$card_background = $_POST['card_background'] ?? [];
		if ( is_string( $card_background ) ) {
			$card_background = json_decode( stripslashes( $card_background ), true );
		}
		$card_items = $_POST['card_items'] ?? [];
		if ( is_string( $card_items ) ) {
			$card_items = json_decode( stripslashes( $card_items ), true );
		}
		$data = [ 'size' => $card_size, 'background' => $card_background, 'items' => $card_items ];
		set_transient( 'yousaidit_preview_card_options', $data, HOUR_IN_SECONDS );
		$url = add_query_arg( [ 'action' => 'yousaidit_preview_card' ], admin_url( 'admin-ajax.php' ) );
		wp_send_json_success( [ 'redirect' => $url, 'request_data' => $data ] );
	}

	public function yousaidit_preview_card() {
		$transient = get_transient( 'yousaidit_preview_card_options' );
		if ( false === $transient ) {
			die( 'No valid options' );
		}


		$pdf = new FreePdf();
		$pdf->generate( $transient['size'], $transient['items'] );
		die();
	}
}
