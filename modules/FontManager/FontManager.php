<?php

namespace YouSaidItCards\Modules\FontManager;

use YouSaidItCards\Assets;
use YouSaidItCards\Modules\FontManager\Models\DesignerFont;

class FontManager {

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

			add_action( 'admin_menu', [ self::$instance, 'admin_menu' ] );
			add_action( 'admin_init', [ DesignerFont::class, 'create_table' ] );
			AdminFontController::init();
		}

		return self::$instance;
	}

	/**
	 * Add custom admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'font-manager';
		$title      = __( 'Font Manager', 'yousaidit-toolkit' );
		$hook       = add_menu_page( $title, $title, $capability, $slug,
			[ self::$instance, 'menu_page_callback' ], 'dashicons-admin-customizer', 6 );
		$menus      = [
			[ 'title' => __( 'Pre-installed', 'yousaidit-toolkit' ), 'slug' => '#/' ],
			[ 'title' => __( 'Extra fonts', 'yousaidit-toolkit' ), 'slug' => '#/extra' ],
		];
		if ( current_user_can( $capability ) ) {
			foreach ( $menus as $menu ) {
				$submenu[ $slug ][] = [ $menu['title'], $capability, 'admin.php?page=' . $slug . $menu['slug'] ];
			}
		}

		add_action( 'load-' . $hook, [ self::$instance, 'init_hooks' ] );
	}

	public function menu_page_callback() {
		echo '<div id="yousaidit_card_admin_font_manager"></div>';
	}

	public function init_hooks() {
		wp_enqueue_style( 'stackonet-toolkit-admin' );

		$file_url  = Assets::get_assets_url( 'js/font-manager-admin.js' );
		$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file_url );
		if ( file_exists( $file_path ) ) {
			wp_enqueue_script( 'yousaidit-font-manager-admin',
				$file_url, [],
				gmdate( 'Y.m.d.Gi', filemtime( $file_path ) ),
				true
			);
		}
	}
}