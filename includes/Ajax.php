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
		$pdf->generate( 'a5', $items );
		die();
	}
}
