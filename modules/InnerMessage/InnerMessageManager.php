<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Stackonet\WP\Framework\Supports\Logger;
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

			add_action( 'woocommerce_before_add_to_cart_button', [ self::$instance, 'add_fields' ], 20 );
			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ], 10, 3 );

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
		if ( is_single() ) {
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
		$html = '<div id="_inner_message_fields" style="visibility: hidden; position: absolute; width: 1px; height: 1px">';
		$html .= '<textarea id="_inner_message_content" name="_inner_message[content]"></textarea>';
		$html .= '<input type="text" id="_inner_message_font" name="_inner_message[font]"/>';
		$html .= '<input type="text" id="_inner_message_size" name="_inner_message[size]"/>';
		$html .= '<input type="text" id="_inner_message_align" name="_inner_message[align]"/>';
		$html .= '<input type="text" id="_inner_message_color" name="_inner_message[color]"/>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * Add custom data to cart
	 *
	 * @param array $cart_item_data
	 * @param int $product_id
	 * @param int $variation_id
	 *
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( isset( $_REQUEST['_inner_message'] ) ) {
			$cart_item_data['_inner_message'] = static::sanitize_inner_message_data( $_REQUEST['_inner_message'] );
		}

		return $cart_item_data;
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
			'content' => wp_filter_post_kses( $data['content'] ),
			'font'    => sanitize_text_field( $data['font'] ),
			'size'    => intval( $data['size'] ),
			'align'   => sanitize_text_field( $data['align'] ),
			'color'   => sanitize_hex_color( $data['color'] ),
		];
	}
}
