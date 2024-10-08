<?php

namespace YouSaidItCards\Modules\ArtWork;

/**
 * ArtWorkManager class
 */
class ArtWorkManager {


	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 * @return self - Main instance
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'admin_menu', [ self::$instance, 'add_menu' ] );
		}

		return self::$instance;
	}

	/**
	 * Add admin menu
	 */
	public function add_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'stackonet-art-work';
		$hook       = add_menu_page( 'ArtWork', 'ArtWork',
			$capability, $slug, [ self::$instance, 'menu_page_callback' ], 'dashicons-admin-post', 6 );
		$menus      = [
			[ 'title' => __( 'Products', 'vue-wp-starter' ), 'slug' => '#/' ],
			[ 'title' => __( 'Orders', 'vue-wp-starter' ), 'slug' => '#/orders' ],
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
	public function menu_page_callback() {
		echo '<div id="stackonet_toolkit_admin"></div>';
	}

	/**
	 * Menu page scripts
	 */
	public function init_hooks() {
		wp_enqueue_media();
		wp_enqueue_style( 'yousaidit-toolkit-admin' );
		wp_enqueue_script( 'yousaidit-toolkit-admin' );
	}
}