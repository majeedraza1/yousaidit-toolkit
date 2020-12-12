<?php

namespace YouSaidItCards\Admin;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class Admin {

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

			// add_action( 'admin_menu', [ self::$instance, 'add_menu' ] );
		}

		return self::$instance;
	}

	/**
	 * Add top level menu
	 */
	public static function add_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'yousaidit-toolkit';

		$hook = add_menu_page( __( 'Yousaidit', 'yousaidit-toolkit' ), __( 'Yousaidit', 'yousaidit-toolkit' ),
			$capability, $slug, [ self::$instance, 'menu_page_callback' ], 'dashicons-update', 6 );

		$menus = [
			[ 'title' => __( 'Yousaidit', 'yousaidit-toolkit' ), 'slug' => '#/' ],
			[ 'title' => __( 'Settings', 'yousaidit-toolkit' ), 'slug' => '#/settings' ],
		];

		if ( current_user_can( $capability ) ) {
			foreach ( $menus as $menu ) {
				$submenu[ $slug ][] = [ $menu['title'], $capability, 'admin.php?page=' . $slug . $menu['slug'] ];
			}
		}

		add_action( 'load-' . $hook, [ self::$instance, 'init_hooks' ] );
	}

	/**
	 * Menu page callback
	 */
	public static function menu_page_callback() {
		echo '<div class="wrap"><div id="yousaidit-toolkit-admin"></div></div>';
	}

	/**
	 * Load required styles and scripts
	 */
	public static function init_hooks() {
		wp_enqueue_style( YOUSAIDIT_TOOLKIT . '-admin' );
		wp_enqueue_script( YOUSAIDIT_TOOLKIT . '-admin' );
	}
}
