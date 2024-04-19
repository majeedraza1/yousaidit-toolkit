<?php

namespace YouSaidItCards\Modules\StabilityAi\Admin;

/**
 * Admin class
 */
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

			add_action( 'admin_menu', [ self::$instance, 'add_menu' ] );
		}

		return self::$instance;
	}

	/**
	 * Add top level menu
	 */
	public function add_menu() {
		$hook = add_submenu_page(
			'options-general.php',
			__( 'Stability AI', 'yousaidit-toolkit' ),
			__( 'Stability AI', 'yousaidit-toolkit' ),
			'manage_options',
			'stability-ai',
			[ self::$instance, 'menu_page_callback' ],
			99.99
		);

		add_action( 'load-' . $hook, [ $this, 'init_hooks' ] );
	}

	/**
	 * Menu page callback
	 */
	public static function menu_page_callback() {
		echo '<div class="wrap border-box-all"><div id="stability-ai-admin"></div></div>';
	}

	/**
	 * Menu page scripts
	 */
	public function init_hooks() {
		wp_enqueue_style( 'yousaidit-toolkit-admin' );
		wp_enqueue_script( 'yousaidit-toolkit-admin' );
	}
}
