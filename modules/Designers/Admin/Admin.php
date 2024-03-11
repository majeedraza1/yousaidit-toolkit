<?php

namespace YouSaidItCards\Modules\Designers\Admin;

use WP_Post;
use YouSaidItCards\Modules\Designers\Frontend\DesignerProfile;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

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

			add_action( 'admin_menu', [ self::$instance, 'designer_admin_menu' ] );
			add_action( 'add_meta_boxes', [ self::$instance, 'add_meta_boxes' ], 30, 2 );
			add_action( 'after_delete_post', [ self::$instance, 'clear_product_id_from_card' ] );
		}

		return self::$instance;
	}

	/**
	 * Add admin menu
	 */
	public function designer_admin_menu() {
		global $submenu;
		$capability = 'manage_options';
		$slug       = 'designers';
		$hook       = add_menu_page( 'Designers', 'Designers',
				$capability, $slug, [ self::$instance, 'designer_menu_page_callback' ], 'dashicons-admin-post', 6 );
		$menus      = [
				[ 'title' => __( 'Designers', 'vue-wp-starter' ), 'slug' => '#/' ],
				[ 'title' => __( 'Cards', 'vue-wp-starter' ), 'slug' => '#/cards' ],
				[ 'title' => __( 'Commissions', 'vue-wp-starter' ), 'slug' => '#/commissions' ],
				[ 'title' => __( 'PayPal Payouts', 'vue-wp-starter' ), 'slug' => '#/payouts' ],
				[ 'title' => __( 'Settings', 'vue-wp-starter' ), 'slug' => '#/settings' ],
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
	public function designer_menu_page_callback() {
		echo '<div id="yousaiditcard_admin_designer"></div>';
		add_action( 'admin_footer', [ DesignerProfile::class, 'add_inline_scripts' ], 5 );
	}

	/**
	 * Menu page scripts
	 */
	public function init_hooks() {
		wp_enqueue_style( 'yousaidit-toolkit-admin-vue3' );
		wp_enqueue_script( 'yousaidit-toolkit-admin-vue3' );
	}

	/**
	 * Add shop order metabox
	 *
	 *
	 * @param string $post_type Post type.
	 * @param WP_Post $post Post object.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		if ( 'product' == $post_type ) {
			$card_id     = (int) get_post_meta( $post->ID, '_card_id', true );
			$designer_id = (int) get_post_meta( $post->ID, '_card_designer_id', true );

			if ( $card_id && $designer_id ) {
				add_meta_box( 'product_card_info', 'Product Card Info', [ $this, 'product_card_info_callback' ],
						$post_type, 'side', 'low' );
			}
		}
	}

	public function product_card_info_callback( $post ) {
		$card_id     = (int) get_post_meta( $post->ID, '_card_id', true );
		$designer_id = (int) get_post_meta( $post->ID, '_card_designer_id', true );
		if ( ! ( $card_id && $designer_id ) ) {
			echo 'Do designer found!';

			return;
		}
		$designer_profile = admin_url( "admin.php?page=designers#/designers/" . $designer_id );
		$card_url         = admin_url( "admin.php?page=designers#/cards/" . $card_id );
		?>
		<p>
			<a class="button" href="<?php echo $designer_profile ?>" target="_blank">View Designer Profile</a>
			<a class="button" href="<?php echo $card_url ?>" target="_blank">View Card</a>
		</p>
		<?php
	}

	/**
	 * @param $post_id
	 */
	public function clear_product_id_from_card( $post_id ) {
		( new DesignerCard() )->reset_product_id( $post_id );
	}
}

