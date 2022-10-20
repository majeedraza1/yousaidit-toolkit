<?php

namespace YouSaidItCards\Modules\Reminders\Admin;

/**
 * Class Admin
 * @package YouSaidItCards\Modules\Reminders\Admin
 */
class Admin {
	/**
	 * The instance of the class
	 *
	 * @var Admin
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
		$slug       = 'reminders';
		$title      = __( 'Reminders', 'yousaidit-toolkit' );
		$hook       = add_menu_page( $title, $title, $capability, $slug,
			[ self::$instance, 'reminders_page_callback' ], 'dashicons-bell', 6 );
		$menus      = [
			[ 'title' => __( 'Reminders', 'yousaidit-toolkit' ), 'slug' => '#/' ],
			[ 'title' => __( 'Reminders Groups', 'yousaidit-toolkit' ), 'slug' => '#/groups' ],
			[ 'title' => __( 'Reminders Queue', 'yousaidit-toolkit' ), 'slug' => '#/queue' ],
		];
		if ( current_user_can( $capability ) ) {
			foreach ( $menus as $menu ) {
				$submenu[ $slug ][] = [ $menu['title'], $capability, 'admin.php?page=' . $slug . $menu['slug'] ];
			}
		}

		add_action( 'load-' . $hook, [ self::$instance, 'init_hooks' ] );
	}

	public function reminders_page_callback() {
		echo '<div id="yousaiditcard_admin_reminders"></div>';
	}

	public function init_hooks() {
		wp_enqueue_style( 'stackonet-toolkit-admin' );
		wp_enqueue_script( 'stackonet-toolkit-admin' );
	}
}
