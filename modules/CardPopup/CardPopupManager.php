<?php

namespace YouSaidItCards\Modules\CardPopup;

/**
 * CardPopupManager class
 */
class CardPopupManager {
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return static|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();

			add_action( 'wp_ajax_yousaidit_wishlist', [ self::$instance, 'toggle_wishlist' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_wishlist', [ self::$instance, 'toggle_wishlist' ] );

			add_action( 'wp_ajax_yousaidit_loop_product_popup', [ self::$instance, 'loop_item_popup' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_loop_product_popup', [ self::$instance, 'loop_item_popup' ] );

			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_settings_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'woocommerce_before_shop_loop_item', [ self::$instance, 'add_popup_markup' ] );
		}

		return self::$instance;
	}

	/**
	 * Toggle wishlist AJAX action
	 *
	 * @return void
	 */
	public function toggle_wishlist() {
		if (
			isset( $_REQUEST['_wpnonce'] ) &&
			wp_verify_nonce( $_REQUEST['_wpnonce'], 'yousaidit_wishlist_nonce' )
		) {
			$task       = isset( $_REQUEST['task'] ) ? sanitize_text_field( $_REQUEST['task'] ) : '';
			$task       = in_array( $task, [ 'remove_from_wishlist', 'add_to_wishlist' ], true ) ? $task : '';
			$product_id = isset( $_REQUEST['product_id'] ) ? intval( $_REQUEST['product_id'] ) : 0;
			$wishlist   = WishlistList::get_current_user_wishlist_list();

			if ( 'remove_from_wishlist' === $task ) {
				$wishlist->remove_from_list( $product_id );
				wp_send_json_success( [
					'href'     => rawurlencode( WishlistList::get_wishlist_ajax_url( $product_id, 'add_to_wishlist' ) ),
					'cssClass' => [ 'yousaidit_wishlist' ],
					'title'    => 'Add to wishlist'
				] );
			}
			if ( 'add_to_wishlist' === $task ) {
				$wishlist->add_to_list( $product_id );
				wp_send_json_success( [
					'href'     => rawurlencode( WishlistList::get_wishlist_ajax_url( $product_id,
						'remove_from_wishlist' ) ),
					'cssClass' => [ 'yousaidit_wishlist', 'remove-from-wishlist', 'is-in-list' ],
					'title'    => 'Remove from wishlist'
				] );
			}
		}

		wp_send_json_error( null, 422 );
	}

	/**
	 * Add loop item
	 *
	 * @return void
	 */
	public function loop_item_popup() {
		$id      = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;
		$product = wc_get_product( $id );
		if ( $product instanceof \WC_Product ) {
			ob_start();
			include_once dirname( __FILE__ ) . '/template-popup.php';
			$popup = ob_get_clean();

			wp_send_json( [
				'product_id' => $product->get_id(),
				'popup'      => $popup,
			] );
		}
		wp_send_json_error();
	}

	/**
	 * Add popup markup
	 *
	 * @return void
	 */
	public function add_popup_markup() {
		global $product;
		if ( $product instanceof \WC_Product ) {
			$should_show = Settings::should_show_popup( $product );
			if ( $should_show ) {
				echo '<div class="card-category-popup" data-product-id="' . esc_attr( $product->get_id() ) . '"></div>';
			}
		}
	}

	/**
	 * Add settings sections
	 *
	 * @param  array  $sections  Array of sections.
	 *
	 * @return array
	 */
	public function add_settings_section( array $sections ): array {
		$sections[] = [
			'id'       => 'section_card_popup',
			'title'    => __( 'Card Popup' ),
			'panel'    => 'general',
			'priority' => 200,
		];

		return $sections;
	}

	public function add_settings_fields( array $fields ): array {
		$fields[] = [
			'id'                => 'card_popup_categories',
			'type'              => 'select',
			'title'             => __( 'Card popup category' ),
			'description'       => __( 'Set product categories for popup.' ),
			'default'           => '',
			'priority'          => 20,
			'multiple'          => true,
			'sanitize_callback' => function ( $value ) {
				return $value ? array_map( 'intval', $value ) : '';
			},
			'section'           => 'section_card_popup',
			'options'           => self::get_product_categories_for_select_field(),
		];

		return $fields;
	}

	/**
	 * Woocommerce product categories
	 *
	 * @return string[]
	 */
	public static function get_product_categories_for_select_field(): array {
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );
		$items = [ "" => "-- Select Category --" ];
		foreach ( $terms as $term ) {
			$items[ $term->term_id ] = $term->name;
		}

		return $items;
	}
}
