<?php

namespace YouSaidItCards\Modules\LocalDevelopmentHelper;

class Manager {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->init_hook();
		}

		return self::$instance;
	}

	public function init_hook() {
		if ( 'production' === wp_get_environment_type() ) {
			return;
		}
		add_action( 'wp_footer', [ self::$instance, 'add_footer_content' ], 999 );
	}

	/**
	 * Footer content
	 *
	 * @return void
	 */
	public function add_footer_content() {
		$html = '<div class="bg-white bottom-4 fixed left-0 border border-solid border-grey-300 flex justify-center items-center" style="z-index: 99">';
		$html .= '<label>';
		$html .= '<input type="checkbox" class="mb-0" />';
		$html .= 'Customizable product';
		$html .= '</label>';
		$html .= '</div>';

		echo $html;
	}
}