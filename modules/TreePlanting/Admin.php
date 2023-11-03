<?php

namespace YouSaidItCards\Modules\TreePlanting;

/**
 * Admin class
 */
class Admin {
	/**
	 * The instance of the class
	 *
	 * @var self|null
	 */
	private static $instance;

	/**
	 * The instance of the class
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'admin_menu', [ self::$instance, 'admin_menu' ] );
		}

		return self::$instance;
	}

	public function admin_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'tree-planting';
		$title      = __( 'Tree Planting', 'yousaidit-toolkit' );
		$hook       = add_menu_page( $title, $title, $capability, $slug,
			[ self::$instance, 'reminders_page_callback' ], 'dashicons-palmtree', 6 );
		$menus      = [
			[ 'title' => __( 'Purchases', 'yousaidit-toolkit' ), 'slug' => '#/' ],
			[ 'title' => __( 'In Queue', 'yousaidit-toolkit' ), 'slug' => '#/queue' ],
		];
		if ( current_user_can( $capability ) ) {
			foreach ( $menus as $menu ) {
				$submenu[ $slug ][] = [ $menu['title'], $capability, 'admin.php?page=' . $slug . $menu['slug'] ];
			}
		}

		add_action( 'load-' . $hook, [ self::$instance, 'init_hooks' ] );
	}

	public function reminders_page_callback() {
		echo '<div id="yousaidit_admin_tree_planting"></div>';
	}

	public function init_hooks() {
		wp_enqueue_style( 'stackonet-toolkit-admin' );
		wp_enqueue_script( 'stackonet-toolkit-admin' );
	}
}
