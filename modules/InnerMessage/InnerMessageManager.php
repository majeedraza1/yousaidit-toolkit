<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Stackonet\WP\Framework\Supports\Logger;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;

defined( 'ABSPATH' ) || die;

class InnerMessageManager {

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

			add_action( 'wp_footer', [ self::$instance, 'add_editor' ], 5 );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'load_scripts' ] );

			add_action( 'wp_ajax_inner_message_preview_test', [ self::$instance, 'inner_message_preview_test' ] );

			add_action( 'wp_ajax_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );

			add_action( 'wp_ajax_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );

			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ] );
			add_action( 'woocommerce_before_calculate_totals', [ self::$instance, 'before_calculate_totals' ], 1, 1 );

			// Step 3: Display Details as Meta in Cart
			add_filter( 'woocommerce_get_item_data', [ self::$instance, 'get_item_data' ], 99, 2 );

			// Step 4: Add Custom Details as Order Line Items
			add_action( 'woocommerce_checkout_create_order_line_item',
				[ self::$instance, 'create_order_line_item' ], 10, 4 );

			// Step 5: Display on Order detail page and (Order received / Thank you page) and Order Emails
			add_filter( 'woocommerce_order_item_get_formatted_meta_data',
				[ self::$instance, 'order_item_get_formatted_meta_data' ], 10, 2 );

			BackgroundInnerMessagePdfGenerator::init();
			add_action( 'woocommerce_checkout_order_processed',
				[ self::$instance, 'generate_inner_message_pdf' ] );

			add_filter( 'woocommerce_order_actions', [ self::$instance, 'add_custom_order_action' ], 99 );
			add_action( 'woocommerce_order_action_generate_inner_message_pdf',
				[ self::$instance, 'process_custom_order_action' ] );
		}

		return self::$instance;
	}

	public function add_custom_order_action( $actions ) {
		$actions['generate_inner_message_pdf'] = 'Generate Inner Message PDF';

		return $actions;
	}

	/**
	 * @param WC_Order $order
	 */
	public function process_custom_order_action( $order ) {
		BackgroundInnerMessagePdfGenerator::generate_for_order( $order, true );
	}

	public function load_scripts() {
		if ( is_single() || is_cart() ) {
			wp_enqueue_script( 'stackonet-inner-message' );
			wp_enqueue_style( 'stackonet-inner-message' );
		}
	}

	/**
	 * Content
	 */
	public function add_editor() {
		echo '<div id="inner-message"></div>';
	}

	/**
	 * Add fields
	 */
	public function add_fields() {
		echo '';
	}

	/**
	 * Add custom data to cart
	 *
	 * @param array $cart_item_data
	 *
	 * @return array
	 */
	public function add_cart_item_data( array $cart_item_data ): array {
		if ( isset( $_REQUEST['_inner_message'] ) ) {
			$cart_item_data['_inner_message'] = static::sanitize_inner_message_data( $_REQUEST['_inner_message'] );
		}

		return $cart_item_data;
	}

	/**
	 * Before calculate totals
	 *
	 * @param WC_Cart $cart
	 */
	public function before_calculate_totals( WC_Cart $cart ) {
		$options = (array) get_option( '_stackonet_toolkit' );
		$price   = isset( $options['inner_message_price'] ) ? floatval( $options['inner_message_price'] ) : 0;
		// If price is zero, exit here
		if ( $price <= 0 ) {
			return;
		}

		foreach ( $cart->get_cart_contents() as $key => &$value ) {
			$inner_message     = $value['_inner_message'];
			$has_inner_message = is_array( $inner_message ) && isset( $inner_message['content'] ) &&
			                     ! empty( $inner_message['content'] );
			// If there is no inner message, exit here
			if ( ! $has_inner_message ) {
				continue;
			}
			$orgPrice = floatval( $value['data']->get_price( '' ) );
			if ( $price ) {
				$extra_price = $price;
				$value['data']->set_price( $orgPrice + $extra_price );
			}
		}
	}

	/**
	 * Display information as Meta on Cart & Checkout page
	 *
	 * @param array $item_data
	 * @param array $cart_item
	 *
	 * @return array
	 */
	public function get_item_data( array $item_data, array $cart_item ): array {
		if ( is_cart() && array_key_exists( '_inner_message', $cart_item ) ) {
			$im = $cart_item['_inner_message'] ?? [];
			if ( ! empty( $im['content'] ) ) {
				$item_data[] = [
					'key'   => '<a data-mode="view" data-cart-item-key="' . esc_attr( $cart_item['key'] ) . '" class="inline-block border border-solid border-gray-700 p-1 sm:w-full">View Message</a>',
					'value' => '<a data-mode="edit" data-cart-item-key="' . esc_attr( $cart_item['key'] ) . '" class="inline-block border border-solid border-primary p-1 sm:w-full">Edit Message</a>'
				];
			}
		}

		return $item_data;
	}

	public function get_cart_item_info() {
		$item_key = $_REQUEST['item_key'] ?? '';

		$data = WC()->cart->get_cart_item( $item_key );

		wp_send_json( $data, 200 );
	}

	public function set_cart_item_info() {
		$item_key      = $_REQUEST['item_key'] ?? '';
		$inner_message = $_REQUEST['inner_message'] ?? [];

		$data_changed  = false;
		$cart          = WC()->cart;
		$cart_contents = $cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_content ) {
			if ( $key == $item_key ) {
				$cart_contents[ $key ]['_inner_message'] = self::sanitize_inner_message_data( $inner_message );
				$data_changed                            = true;
			}
		}

		if ( $data_changed ) {
			$cart->set_cart_contents( $cart_contents );
			$cart->calculate_totals();
		}

		die();
	}

	/**
	 * Add custom data to order line item
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string $cart_item_key
	 * @param array $values
	 * @param WC_Order $order
	 */
	public function create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( array_key_exists( '_inner_message', $values ) ) {
			$item->add_meta_data( '_inner_message', static::sanitize_inner_message_data( $values['_inner_message'] ) );
		}
	}

	public function generate_inner_message_pdf( $order_id ) {
		try {
			$order = wc_get_order( $order_id );
			BackgroundInnerMessagePdfGenerator::generate_for_order( $order );
		} catch ( \Exception $exception ) {
			Logger::log( $exception );
		}
	}

	/**
	 * Display on Order detail page and (Order received / Thank you page)
	 *
	 * @param array $formatted_meta
	 * @param WC_Order_Item_Product $order_item
	 *
	 * @return mixed
	 */
	public function order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {
		$data = $order_item->get_meta( '_inner_message', true );
		if ( ! empty( $data ) ) {
			$formatted_meta[] = (object) array(
				'display_key'   => 'Inner Message',
				'display_value' => $data['content'],
			);

			if ( is_admin() ) {
				$args = [
					'order_id' => $order_item->get_order_id(),
					'item_id'  => $order_item->get_id(),
					'mode'     => 'pdf'
				];

				$url1    = add_query_arg( $args + [ 'action' => 'yousaidit_single_im_card' ], admin_url( 'admin-ajax.php' ) );
				$url2    = add_query_arg( $args + [ 'action' => 'yousaidit_single_pdf_card' ], admin_url( 'admin-ajax.php' ) );
				$display = sprintf( "%s | %s",
					"<a target='_blank' href='" . esc_url( $url2 ) . "'>View PDF</a>",
					"<a target='_blank' href='" . esc_url( $url1 ) . "'>View Inner Message PDF</a>"
				);

				$formatted_meta[] = (object) [ 'display_key' => '', 'display_value' => $display, ];
			}
		}


		return $formatted_meta;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public static function sanitize_inner_message_data( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$default = [ 'content' => '', 'font' => '', 'size' => '', 'align' => '', 'color' => '' ];
		$data    = wp_parse_args( $data, $default );

		return [
			'content' => stripslashes( wp_filter_post_kses( $data['content'] ) ),
			'font'    => sanitize_text_field( stripslashes( $data['font'] ) ),
			'align'   => sanitize_text_field( stripslashes( $data['align'] ) ),
			'color'   => sanitize_hex_color( stripslashes( $data['color'] ) ),
			'size'    => intval( $data['size'] ),
		];
	}

	public function inner_message_preview_test() {
		$pdf = new PdfGeneratorBase();
		$pdf->set_page_size( 300, 150 );
		$pdf->set_right_column_bg( '#f1f1f1' );
		$pdf->set_text_data( [
			'content' => 'Add a very very long line of text and it should go to.',
			'font'    => '"Indie Flower", cursive',
			'align'   => 'center',
			'color'   => '#000',
			'size'    => 40,
		] );
		$pdf->get_pdf( 'pdf' );
	}
}
