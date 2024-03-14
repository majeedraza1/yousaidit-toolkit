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
			add_action( 'wp_footer', [ self::$instance, 'frontend_login' ], 5 );
		}

		return self::$instance;
	}

	/**
	 * Load frontend scripts
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'yousaidit-toolkit-frontend' );
		wp_enqueue_script( 'yousaidit-toolkit-frontend' );

		if ( ! wp_script_is( "wc-cart-fragments", "enqueued" ) && wp_script_is( "wc-cart-fragments", "registered" ) ) {
			wp_enqueue_script( "wc-cart-fragments" );
		}
	}

	public function frontend_login() {
		echo '<div id="yousaidit-frontend-login-popup"></div>';
	}
}
