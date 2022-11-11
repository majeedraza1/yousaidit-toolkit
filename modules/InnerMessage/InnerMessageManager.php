<?php

namespace YouSaidItCards\Modules\InnerMessage;

use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\InnerMessage\Models\Video;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\Utils;

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

			add_action( 'wp_footer', [ self::$instance, 'add_editor' ], 1 );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'load_scripts' ] );

			add_action( 'wp_ajax_inner_message_preview_test', [ self::$instance, 'inner_message_preview_test' ] );

			add_action( 'wp_ajax_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );

			add_action( 'wp_ajax_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );

			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ] );
			add_action( 'woocommerce_before_calculate_totals',
				[ self::$instance, 'add_inner_message_extra_cost' ], 1, 1 );
			add_action( 'woocommerce_before_calculate_totals',
				[ self::$instance, 'add_video_message_extra_cost' ], 1, 1 );

			// Step 3: Display Details as Meta in Cart
			add_filter( 'woocommerce_get_item_data', [ self::$instance, 'get_item_data' ], 99, 2 );

			// Step 4: Add Custom Details as Order Line Items
			add_action( 'woocommerce_checkout_create_order_line_item',
				[ self::$instance, 'create_order_line_item' ], 10, 4 );

			// Step 5: Display on Order detail page and (Order received / Thank you page) and Order Emails
			add_filter( 'woocommerce_order_item_get_formatted_meta_data',
				[ self::$instance, 'order_item_get_formatted_meta_data' ], 10, 2 );

			BackgroundInnerMessagePdfGenerator::init();
			// Step 5: Add background task to generate dynamic card pdf
			add_action( 'woocommerce_checkout_order_created',
				[ self::$instance, 'generate_inner_message_pdf' ], 10 );

			add_filter( 'woocommerce_order_actions', [ self::$instance, 'add_custom_order_action' ], 99 );
			add_action( 'woocommerce_order_action_generate_inner_message_pdf',
				[ self::$instance, 'process_custom_order_action' ] );

			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_settings_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'add_custom_endpoint' ] );
			add_action( 'init', [ self::$instance, 'add_custom_endpoint' ] );
			add_filter( 'query_vars', array( self::$instance, 'query_vars' ), 0 );
			add_action( 'template_redirect', array( self::$instance, 'redirect_to_url' ) );

			add_action( 'wp_ajax_video_message_qr_code', [ self::$instance, 'video_message_qr_code' ] );
		}

		return self::$instance;
	}

	/**
	 * Add custom rewrite endpoint
	 */
	public function add_custom_endpoint() {
		add_rewrite_endpoint( 'video-message', EP_ROOT );
	}

	/**
	 * Add query variable
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'video-message';

		return $vars;
	}

	/**
	 * Redirect to url
	 */
	public function redirect_to_url() {
		global $wp_query;
		$short_code = $wp_query->query_vars['video-message'] ?? null;
		if ( $short_code && strlen( $short_code ) === 64 ) {
			global $wpdb;
			$row     = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'_video_message_filename',
				$short_code
			), ARRAY_A );
			$post_id = isset( $row['post_id'] ) ? intval( $row['post_id'] ) : 0;
			$url     = wp_get_attachment_url( $post_id );
			if ( $url ) {
				wp_redirect( $url );
			} else {
				wp_redirect( home_url() );
			}
			die;
		}
	}

	public function video_message_qr_code() {
		$current_user = wp_get_current_user();
		$order_id     = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		$order        = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			wp_die( 'Order not found.' );
		}
		if ( ! ( $order->get_customer_id() === $current_user->ID || current_user_can( 'manage_options' ) ) ) {
			wp_die( 'Sorry, You are not allowed to view this link.' );
		}
		$item_id = isset( $_REQUEST['item_id'] ) ? intval( $_REQUEST['item_id'] ) : 0;

		$meta = wc_get_order_item_meta( $item_id, '_video_inner_message', true );
		if ( ! ( is_array( $meta ) && isset( $meta['type'], $meta['video_id'] ) ) ) {
			wp_die( 'Invalid request.' );
		}
		$url     = Utils::get_video_message_url( intval( $meta['video_id'] ) );
		$qr_code = QrCode::generate_video_message( $url );
		wp_redirect( $qr_code['url'] );
		die;
	}

	/**
	 * Handle module activation
	 *
	 * @return void
	 */
	public static function activation() {
		Video::create_table();
	}

	/**
	 * Add settings sections
	 *
	 * @param array $sections Array of sections.
	 *
	 * @return array
	 */
	public function add_settings_section( array $sections ): array {
		$sections[] = [
			'id'       => 'section_inner_message_settings',
			'title'    => __( 'Inner Message Settings', 'yousaidit-toolkit' ),
			'panel'    => 'general',
			'priority' => 6,
		];

		return $sections;
	}

	/**
	 * Add settings fields
	 *
	 * @param array $fields Array of fields.
	 *
	 * @return array
	 */
	public function add_settings_fields( array $fields ): array {
		$terms                    = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );
		$product_category_options = [ "" => "-- Select Category --" ];
		foreach ( $terms as $term ) {
			$product_category_options[ $term->term_id ] = $term->name;
		}

		$fields[] = [
			'id'                => 'inner_message_price',
			'type'              => 'text',
			'title'             => __( 'Inner message price' ),
			'description'       => __( 'Enter number or float value' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'video_inner_message_price',
			'type'              => 'text',
			'title'             => __( 'Video Inner message price' ),
			'description'       => __( 'Enter number or float value' ),
			'default'           => '',
			'priority'          => 11,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'inner_message_visible_on_cat',
			'type'              => 'select',
			'title'             => __( 'Inner message visible on' ),
			'description'       => __( 'Choose category where the inner message should be visible.' ),
			'default'           => '',
			'priority'          => 20,
			'multiple'          => true,
			'sanitize_callback' => function ( $value ) {
				return $value ? array_map( 'intval', $value ) : '';
			},
			'section'           => 'section_inner_message_settings',
			'options'           => $product_category_options,
		];

		return $fields;
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
		echo Fonts::get_font_face_rules();
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
		if ( isset( $_REQUEST['_video_inner_message'] ) ) {
			$data = static::sanitize_inner_message_data( $_POST['_video_inner_message'], true );

			$cart_item_data['_video_inner_message'] = $data;
		}

		return $cart_item_data;
	}

	/**
	 * Before calculate totals
	 *
	 * @param WC_Cart $cart
	 */
	public function add_inner_message_extra_cost( WC_Cart $cart ) {
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
	 * Before calculate totals
	 *
	 * @param WC_Cart $cart
	 */
	public function add_video_message_extra_cost( WC_Cart $cart ) {
		$options = (array) get_option( '_stackonet_toolkit' );
		$price   = isset( $options['video_inner_message_price'] ) ? floatval( $options['video_inner_message_price'] ) : 0;
		// If price is zero, exit here
		if ( $price <= 0 ) {
			return;
		}

		foreach ( $cart->get_cart_contents() as $key => &$value ) {
			$inner_message     = $value['_video_inner_message'];
			$has_inner_message = is_array( $inner_message ) && isset( $inner_message['video_id'] ) &&
			                     ! empty( $inner_message['video_id'] );
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
		if ( is_cart() && array_key_exists( '_video_inner_message', $cart_item ) ) {
			$im = $cart_item['_video_inner_message'] ?? [];
			if ( ! empty( $im['video_id'] ) ) {
				$url = Utils::get_video_message_url( $im['video_id'] );
				if ( $url ) {
					$item_data[] = [
						'key'     => 'Video Message',
						'display' => '<a href="' . esc_url( $url ) . '" target="_blank">View</a>',
					];
				}
			}
		}

		return $item_data;
	}

	public function get_cart_item_info() {
		$item_key = $_REQUEST['item_key'] ?? '';

		$data = WC()->cart->get_cart_item( $item_key );
		if ( ! ( is_array( $data ) && isset( $data['product_id'] ) ) ) {
			wp_send_json( [], 404 );
		}
		$product            = wc_get_product( $data['product_id'] );
		$card_size          = $product->get_meta( '_card_size', true );
		$data['_card_size'] = $card_size;

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
		if ( array_key_exists( '_video_inner_message', $values ) ) {
			$_data = static::sanitize_inner_message_data( $values['_video_inner_message'], true );
			$item->add_meta_data( '_video_inner_message', $_data );
			if ( 'video' == $_data['type'] ) {
				delete_post_meta( $_data['video_id'], '_should_delete_after_time' );
			}
		}
	}

	/**
	 * Generate inner message pdf
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function generate_inner_message_pdf( \WC_Order $order ) {
		BackgroundInnerMessagePdfGenerator::generate_for_order( $order );
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
			if ( ! empty( $data['content'] ) ) {
				$formatted_meta[] = (object) array(
					'display_key'   => 'Inner Message',
					'display_value' => $data['content'],
				);
			}

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

		$video_data = $order_item->get_meta( '_video_inner_message', true );
		if ( $video_data ) {
			$video_url = Utils::get_video_message_url( $video_data['video_id'] );
			if ( $video_url ) {
				if ( is_admin() ) {
					$qr_code_url      = add_query_arg( [
						'action'   => 'video_message_qr_code',
						'order_id' => $order_item->get_order_id(),
						'item_id'  => $order_item->get_id(),
					], admin_url( 'admin-ajax.php' ) );
					$formatted_meta[] = (object) [
						'display_key'   => 'Video Message',
						'display_value' => "<a target='_blank' href='" . esc_url( $video_url ) . "'>View</a>" .
						                   " | <a target='_blank' href='" . esc_url( $qr_code_url ) . "'>QR Code</a>",
					];
				} else {
					$formatted_meta[] = (object) [
						'display_key'   => 'Video Message',
						'display_value' => "<a target='_blank' href='" . esc_url( $video_url ) . "'>View</a>",
					];
				}
			}
		}

		return $formatted_meta;
	}

	/**
	 * @param mixed $data The data to be sanitized.
	 * @param bool $contains_video_data Is it contain video data?
	 *
	 * @return array
	 */
	public static function sanitize_inner_message_data( $data, bool $contains_video_data = false ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$default = [ 'content' => '', 'font' => '', 'size' => '', 'align' => '', 'color' => '' ];
		if ( $contains_video_data ) {
			$default['type']     = '';
			$default['video_id'] = 0;
		}
		$data = wp_parse_args( $data, $default );

		$sanitized_data = [
			'content' => stripslashes( wp_filter_post_kses( $data['content'] ) ),
			'font'    => sanitize_text_field( stripslashes( $data['font'] ) ),
			'align'   => sanitize_text_field( stripslashes( $data['align'] ) ),
			'color'   => sanitize_hex_color( stripslashes( $data['color'] ) ),
			'size'    => intval( $data['size'] ),
		];

		if ( $contains_video_data ) {
			$sanitized_data['type']     = sanitize_text_field( stripslashes( $data['type'] ) );
			$sanitized_data['video_id'] = floatval( $data['video_id'] );
		}

		return $sanitized_data;
	}

	public function inner_message_preview_test() {
		$pdf = new PdfGeneratorBase();
		$pdf->set_page_size( 306, 156 );
		$pdf->set_right_column_bg( '#f1f1f1' );
		$pdf->set_text_data( [
			'content' => 'Add a very very long line of text and it should go to.',
			'font'    => '"Indie Flower", cursive',
			'align'   => 'center',
			'color'   => '#000',
			'size'    => 26,
		] );
		$pdf->get_pdf( 'pdf' );
	}
}
