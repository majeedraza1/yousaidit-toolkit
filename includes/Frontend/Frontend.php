<?php

namespace YouSaidItCards\Frontend;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class Frontend {

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

			add_action( 'wp_enqueue_scripts', [ self::$instance, 'frontend_scripts' ] );
			add_shortcode( 'you_said_it_how_it_works', [ self::$instance, 'how_it_works' ] );
		}

		return self::$instance;
	}

	/**
	 * How it works
	 *
	 * @return string
	 */
	public function how_it_works() {
		return '<div id="you_said_it_how_it_works"></div>';
	}

	/**
	 * Load frontend scripts
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'stackonet-toolkit-frontend' );
		wp_enqueue_script( 'stackonet-toolkit-frontend' );
	}
}
