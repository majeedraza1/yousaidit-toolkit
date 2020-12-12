<?php

namespace YouSaidItCards;

// If this file is called directly, abort.
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

			add_action( 'wp_ajax_stackonet_test', [ self::$instance, 'stackonet_test' ] );
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

		var_dump( 'Testing some data on AJAX' );
		die();
	}
}
