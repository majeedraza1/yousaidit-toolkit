<?php

namespace YouSaidItCards\Modules\CardPopup;

use YouSaidItCards\Modules\DynamicCard\DynamicCardManager;
use YouSaidItCards\Modules\InnerMessage\InnerMessageManager;

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

			add_action( 'wp_ajax_yousaidit_add_to_basket', [ self::$instance, 'add_to_basket' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_add_to_basket', [ self::$instance, 'add_to_basket' ] );

			add_action( 'wp_ajax_yousaidit_loop_product_popup', [ self::$instance, 'loop_item_popup' ] );
			add_action( 'wp_ajax_nopriv_yousaidit_loop_product_popup', [ self::$instance, 'loop_item_popup' ] );

			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_settings_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'woocommerce_before_shop_loop_item', [ self::$instance, 'add_popup_markup' ] );

			add_action( 'wp_footer', [ self::$instance, 'add_card_category_popup_container' ], 1 );
		}

		return self::$instance;
	}

	public function add_card_category_popup_container() {
		echo '<div id="card-category-popup-container"></div>';
	}

	public function add_to_basket() {
		if (
			isset( $_REQUEST['_wpnonce'] ) &&
			wp_verify_nonce( $_REQUEST['_wpnonce'], 'yousaidit_add_to_basket_nonce' )
		) {
			$product_id          = isset( $_REQUEST['product_id'] ) ? intval( $_REQUEST['product_id'] ) : 0;
			$product_qty         = isset( $_REQUEST['product_qty'] ) ? intval( $_REQUEST['product_qty'] ) : 1;
			$variation_id        = isset( $_REQUEST['variation_id'] ) ? intval( $_REQUEST['variation_id'] ) : 0;
			$envelope_colour     = isset( $_REQUEST['envelope_colour'] ) ? sanitize_text_field( $_REQUEST['envelope_colour'] ) : '';
			$inner_message       = $_REQUEST['_inner_message'] ?? [];
			$video_inner_message = $_REQUEST['_video_inner_message'] ?? [];
			$dynamic_card        = $_REQUEST['_dynamic_card'] ?? [];

			$cart = WC()->cart;
			try {
				$cart_item_data = [
					'thwepo_options' => [
						'envelope_colour' => [
							'field_type'     => 'select',
							'name'           => 'envelope_colour',
							'label'          => 'Envelope Colour',
							'value'          => $envelope_colour,
							'price'          => '',
							'price_type'     => '',
							'price_unit'     => 0,
							'price_min_unit' => '',
							'quantity'       => false,
							'price_field'    => false,
						]
					],
				];

				if ( ! empty( $inner_message['content'] ) ) {
					$inner_message = InnerMessageManager::sanitize_inner_message_data( $inner_message );

					$cart_item_data['_inner_message'] = $inner_message;
				}

				if ( ! empty( $video_inner_message['content'] ) || ! empty( $video_inner_message['video_id'] ) ) {
					$video_inner_message = InnerMessageManager::sanitize_inner_message_data( $video_inner_message,
						true );

					$cart_item_data['_video_inner_message'] = $video_inner_message;
				}

				if ( ! empty( $dynamic_card ) ) {
					$cart_item_data['_dynamic_card'] = DynamicCardManager::sanitize_dynamic_card( $dynamic_card );
				}

				$hash = $cart->add_to_cart( $product_id, max( 1, $product_qty ), $variation_id, [], $cart_item_data );
				wp_send_json_success( $hash, 200 );
			} catch ( \Exception $e ) {
				wp_send_json_error( $e->getMessage(), 422 );
			}
		}
		wp_send_json_error( '', 400 );
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
			if ( ! $wishlist instanceof WishlistList ) {
				wp_send_json_error( [ 'Wishlist session is not found.' ], 500 );
			}

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
			$extra_fields = [];
			if ( class_exists( \THWEPO_Utils_Section::class ) ) {
				$options_extra = \THWEPO_Utils_Section::get_product_sections_and_fields( $product );
				if ( isset( $options_extra['default'] ) && $options_extra['default'] instanceof \WEPO_Product_Page_Section ) {
					$extra_fields = $options_extra['default']->fields;
				}
			}
			include_once dirname( __FILE__ ) . '/template-popup.php';
			$popup = ob_get_clean();

			wp_send_json( [
				'product_id' => $product->get_id(),
				'popup'      => $popup,
			], 200 );
		}
		wp_send_json_error( [ 'message' => 'Product not found for that id.' ], 404 );
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
