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
	 * @return self - Main instance
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'admin_menu', [ self::$instance, 'add_menu' ] );
			add_action( 'admin_enqueue_scripts', [ self::$instance, 'admin_scripts' ] );
			add_action( 'admin_notices', [ self::$instance, 'admin_notices' ] );
		}

		return self::$instance;
	}

	/**
	 * Show directory error if not available
	 *
	 * @return void
	 */
	public function admin_notices() {
		$web_fonts_dir = join( '/', [ WP_CONTENT_DIR, 'uploads', 'yousaidit-web-fonts' ] );
		$envelop_dir   = join( '/', [ WP_CONTENT_DIR, 'uploads', 'envelope-colours' ] );
		$emoji_dir     = join( '/', [ WP_CONTENT_DIR, 'uploads', 'emoji-assets-6.0.0' ] );

		$message = '';
		foreach ( [ $web_fonts_dir, $envelop_dir, $emoji_dir ] as $item ) {
			if ( ! file_exists( $item ) ) {
				$message .= sprintf( "<li>%s</li>", $item );
			}
		}

		if ( strlen( $message ) ) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>The following directories are required to work Yousaidit Toolkit properly.</strong></p>';
			echo '<ul>' . $message . '</ul>';
			echo '</div>';
		}
	}

	public function admin_scripts() {
		global $hook_suffix;
		$data = array(
			'root'    => esc_url_raw( rest_url( 'stackonet/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'stackonet-toolkit-admin', 'Stackonet', $data );
		if ( 'settings_page_stackonet-toolkit' === $hook_suffix ) {
			wp_enqueue_style( 'stackonet-toolkit-admin' );
		}
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
		wp_enqueue_style( 'stackonet-toolkit-admin' );
		wp_enqueue_script( 'stackonet-toolkit-admin' );
	}
}
