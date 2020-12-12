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

			add_shortcode( 'stackonet_frontend_sample', [ self::$instance, 'frontend_sample' ] );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'frontend_scripts' ] );
		}

		return self::$instance;
	}

	/**
	 * Frontend view sample
	 *
	 * @return string
	 */
	public function frontend_sample() {
		return '<div id="yousaidit-toolkit-frontend"></div>';
	}

	/**
	 * Load frontend scripts
	 */
	public function frontend_scripts() {
		if ( $this->should_load_scripts() ) {
			wp_enqueue_style( YOUSAIDIT_TOOLKIT . '-frontend' );
			wp_enqueue_script( YOUSAIDIT_TOOLKIT . '-frontend' );
		}
	}

	/**
	 * Check if scripts should be loaded
	 *
	 * @return bool
	 */
	public function should_load_scripts() {
		$shortcodes = [ 'stackonet_frontend_sample' ];
		global $post;

		foreach ( $shortcodes as $shortcode ) {
			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}
}
