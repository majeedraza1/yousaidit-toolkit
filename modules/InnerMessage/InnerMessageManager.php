<?php

namespace YouSaidItCards\Modules\InnerMessage;

use Exception;
use Stackonet\WP\Framework\Supports\Sanitize;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\FontManager\Font;
use YouSaidItCards\Modules\InnerMessage\Models\Video;
use YouSaidItCards\Modules\InnerMessage\REST\Controller;
use YouSaidItCards\Modules\OrderDispatcher\QrCode;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Providers\AWSElementalMediaConvert;
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

			add_action( 'wp_footer', [ self::$instance, 'add_editor' ], 2 );
			add_action( 'wp_footer', [ Font::class, 'print_font_face_rules' ], 5 );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'load_scripts' ] );

			add_action( 'wp_ajax_inner_message_preview_test', [ self::$instance, 'inner_message_preview_test' ] );

			add_action( 'wp_ajax_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_get_cart_item_info', [ self::$instance, 'get_cart_item_info' ] );

			add_action( 'wp_ajax_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_set_cart_item_info', [ self::$instance, 'set_cart_item_info' ] );

			add_action( 'wp_ajax_update_cart_item_info', [ self::$instance, 'update_cart_item_info' ] );
			add_action( 'wp_ajax_nopriv_update_cart_item_info', [ self::$instance, 'update_cart_item_info' ] );

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
			BackgroundCopyVideoToServer::init();
			Controller::init();
			// Step 5: Add background task to generate dynamic card pdf
			add_action( 'woocommerce_checkout_order_created',
				[ self::$instance, 'generate_inner_message_pdf' ], 10 );

			add_filter( 'woocommerce_order_actions', [ self::$instance, 'add_custom_order_action' ], 99 );
			add_action( 'woocommerce_order_action_generate_inner_message_pdf',
				[ self::$instance, 'process_custom_order_action' ] );

			add_filter( 'yousaidit_toolkit/settings/panels', [ self::$instance, 'add_settings_panels' ] );
			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_settings_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'add_custom_endpoint' ] );
			add_action( 'init', [ self::$instance, 'add_custom_endpoint' ] );
			add_filter( 'query_vars', array( self::$instance, 'query_vars' ), 0 );
			add_action( 'template_redirect', array( self::$instance, 'redirect_to_url' ) );

			add_action( 'wp_ajax_video_message_qr_code', [ self::$instance, 'video_message_qr_code' ] );
			add_action( 'wp_ajax_video_message_copy_to_server', [ self::$instance, 'video_message_copy_to_server' ] );
			add_filter( 'manage_media_columns', [ self::$instance, 'manage_media_columns' ] );
			add_action( 'manage_media_custom_column', [ self::$instance, 'manage_media_custom_column' ], 10, 2 );
			add_action( 'woocommerce_checkout_order_created', [ self::$instance, 'order_created' ], 10, 2 );
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
	 * @param  array  $vars
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
		$url     = Utils::get_video_message_url( $meta['video_id'] );
		$qr_code = QrCode::generate_video_message( $url );
		wp_redirect( $qr_code['url'] );
		die;
	}

	public function video_message_copy_to_server() {
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
		$job_id = isset( $_REQUEST['job_id'] ) ? sanitize_text_field( $_REQUEST['job_id'] ) : '';
		if ( is_numeric( $job_id ) ) {
			wp_die( 'Video already sync! You can close this tab now.' );
		}
		try {
			$job = AWSElementalMediaConvert::get_job( $job_id );
			if ( is_array( $job ) ) {
				$data     = AWSElementalMediaConvert::format_job_result( $job );
				$video_id = VideoEditor::copy_video( $data['output'], $job_id );

				$meta['video_id'] = $video_id;
				wc_update_order_item_meta( $item_id, '_video_inner_message', $meta );
			}
		} catch ( Exception $e ) {
			var_dump( 'Could not copy video for job: ' . $job_id );
			var_dump( $e );
		}
		die;
	}

	public function order_created( \WC_Order $order ) {
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$meta = $item->get_meta( '_video_inner_message', true );
			if ( is_array( $meta ) && isset( $meta['type'] ) && 'video' === $meta['type'] ) {
				update_post_meta( $meta['video_id'], '_wc_order_id', $item->get_order_id() );
				update_post_meta( $meta['video_id'], '_wc_order_item_id', $item->get_id() );
			}
		}
	}

	public function manage_media_columns( $columns ) {
		$columns['video_message'] = _x( 'Video Message', 'column name', 'yousaidit-toolkit' );

		return $columns;
	}

	public function manage_media_custom_column( $column_name, $post_id ) {
		if ( 'video_message' === $column_name ) {
			$delete_after = (int) get_post_meta( $post_id, '_should_delete_after_time', true );
			$order_id     = get_post_meta( $post_id, '_wc_order_id', true );
			$lines        = [];
			if ( $delete_after ) {
				$lines[] = [
					'label' => 'Will be deleted (after)',
					'value' => gmdate( sprintf( "%s %s", get_option( 'date_format' ), get_option( 'time_format' ) ),
						$delete_after ),
				];
			}
			if ( $order_id ) {
				$url     = add_query_arg( [ 'action' => 'edit', 'post' => $order_id ], admin_url( 'post.php' ) );
				$lines[] = [
					'label' => 'Related to order',
					'value' => '<a href="' . esc_url( $url ) . '" target="_blank">#' . esc_html( $order_id ) . '</a>',
				];
			}

			foreach ( $lines as $index => $line ) {
				if ( $index > 0 ) {
					echo '<br>';
				}
				echo '<strong>' . $line['label'] . ': </strong>' . $line['value'];
			}
		}
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
	 * Add settings panels
	 *
	 * @param  array  $panels  The panels.
	 *
	 * @return array
	 */
	public function add_settings_panels( array $panels ): array {
		$panels[] = [
			'id'       => 'inner_message',
			'title'    => 'Inner Message',
			'priority' => 31
		];

		return $panels;
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
			'id'       => 'section_inner_message_settings',
			'title'    => __( 'Inner Message Settings', 'yousaidit-toolkit' ),
			'panel'    => 'inner_message',
			'priority' => 6,
		];
		$sections[] = [
			'id'       => 'section_ai_content_writer',
			'title'    => __( 'AI content writer', 'yousaidit-toolkit' ),
			'panel'    => 'inner_message',
			'priority' => 10,
		];

		return $sections;
	}

	/**
	 * Add settings fields
	 *
	 * @param  array  $fields  Array of fields.
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
			'id'                => 'max_upload_limit_text',
			'type'              => 'text',
			'title'             => __( 'Max Upload Limit Text' ),
			'default'           => 'Maximum upload file size: 2MB',
			'priority'          => 11,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'file_uploader_terms_and_condition',
			'type'              => 'textarea',
			'title'             => __( 'File Uploader terms and condition.' ),
			'default'           => 'By uploading a video you are consenting to the You Said It’s Term of Use.',
			'priority'          => 11,
			'sanitize_callback' => [ Sanitize::class, 'html' ],
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'video_message_qr_code_info_for_customer',
			'type'              => 'textarea',
			'title'             => __( 'How will play after scanning qr code' ),
			'default'           => 'Your video will play when they scan the QR code printed on the inside page.',
			'priority'          => 12,
			'sanitize_callback' => [ Sanitize::class, 'html' ],
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'number_of_reminders_for_free_video_message',
			'type'              => 'number',
			'title'             => __( 'Number of reminders for free video message.' ),
			'default'           => 5,
			'priority'          => 11,
			'sanitize_callback' => [ Sanitize::class, 'number' ],
			'section'           => 'section_inner_message_settings',
		];

		$fields[] = [
			'id'                => 'video_converter',
			'type'              => 'select',
			'title'             => __( 'Video converter' ),
			'description'       => __( 'Choose video converter to convert unsupported video. To user server, FFMpeg and FFProbe need to be installed on server.' ),
			'default'           => 'none',
			'priority'          => 12,
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_inner_message_settings',
			'options'           => [
				'none'   => 'Don\'t convert',
				'aws'    => 'Amazon Web Service',
				'server' => 'Server',
			],
		];

		$fields[] = [
			'id'                => 'show_recording_option_for_video_message',
			'type'              => 'checkbox',
			'title'             => __( 'Show recording option for video message', 'yousaidit-toolkit' ),
			'default'           => 1,
			'priority'          => 11,
			'sanitize_callback' => [ Sanitize::class, 'checked' ],
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

		$fields[] = [
			'id'       => 'ai_content_writer_occasion',
			'type'     => 'html',
			'title'    => __( 'Occasion' ),
			'priority' => 5,
			'section'  => 'section_ai_content_writer',
			'html'     => '<div id="ai_content_writer_occasion">Loading via javaScript...</div>',
		];

		$fields[] = [
			'id'       => 'ai_content_writer_recipient',
			'type'     => 'html',
			'title'    => __( 'Recipient' ),
			'priority' => 10,
			'section'  => 'section_ai_content_writer',
			'html'     => '<div id="ai_content_writer_recipient">Loading via javaScript...</div>',
		];

		$fields[] = [
			'id'       => 'ai_content_writer_topic',
			'type'     => 'html',
			'title'    => __( 'Topic' ),
			'priority' => 15,
			'section'  => 'section_ai_content_writer',
			'html'     => '<div id="ai_content_writer_topic">Loading via javaScript...</div>',
		];

		return $fields;
	}

	public function add_custom_order_action( $actions ) {
		$actions['generate_inner_message_pdf'] = 'Generate Inner Message PDF';

		return $actions;
	}

	/**
	 * @param  WC_Order  $order
	 */
	public function process_custom_order_action( $order ) {
		BackgroundInnerMessagePdfGenerator::generate_for_order( $order, true );
	}

	public function load_scripts() {
		if ( is_single() || is_cart() || is_shop() || is_product_category() ) {
			wp_enqueue_script( 'yousaidit-inner-message' );
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
	 * @param  array  $cart_item_data
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
	 * @param  WC_Cart  $cart
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
	 * @param  WC_Cart  $cart
	 */
	public function add_video_message_extra_cost( WC_Cart $cart ) {
		$options = (array) get_option( '_stackonet_toolkit' );
		$price   = isset( $options['video_inner_message_price'] ) ? floatval( $options['video_inner_message_price'] ) : 0;
		// If price is zero, exit here
		if ( $price <= 0 ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$num_of_reminders_for_free = isset( $options['number_of_reminders_for_free_video_message'] ) ?
				intval( $options['number_of_reminders_for_free_video_message'] ) : 0;
			$total_reminders           = ( new Reminder() )->count_records( [ 'user_id' => $user_id ] );

			if ( $num_of_reminders_for_free && $total_reminders && ( $total_reminders >= $num_of_reminders_for_free ) ) {
				return;
			}
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
	 * @param  array  $item_data
	 * @param  array  $cart_item
	 *
	 * @return array
	 */
	public function get_item_data( array $item_data, array $cart_item ): array {
		$has_left_side_message = is_array( $cart_item['_video_inner_message'] ) &&
		                         isset( $cart_item['_video_inner_message']['content'] );
		if ( is_cart() && ( array_key_exists( '_inner_message', $cart_item ) || $has_left_side_message ) ) {
			$im  = $cart_item['_inner_message'] ?? [];
			$im2 = $cart_item['_video_inner_message'] ?? [];
			if ( ! empty( $im['content'] ) || ! empty( $im2['content'] ) ) {
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
		$page_side     = isset( $_REQUEST['page_side'] ) && 'left' === $_REQUEST['page_side'] ? 'left' : 'right';
		$meta_key      = 'left' === $page_side ? '_video_inner_message' : '_inner_message';

		$data_changed  = false;
		$cart          = WC()->cart;
		$cart_contents = $cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_content ) {
			if ( $key == $item_key ) {
				$cart_contents[ $key ][ $meta_key ] = self::sanitize_inner_message_data( $inner_message );
				$data_changed                       = true;
			}
		}

		if ( $data_changed ) {
			$cart->set_cart_contents( $cart_contents );
			$cart->calculate_totals();
		}

		die();
	}

	public function update_cart_item_info() {
		$item_key   = $_REQUEST['item_key'] ?? '';
		$messages   = $_REQUEST['messages'] ?? [];
		$left_data  = $messages['left'] ?? [];
		$right_data = $messages['right'] ?? [];

		$data_changed  = false;
		$cart          = WC()->cart;
		$cart_contents = $cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_content ) {
			if ( $key == $item_key ) {
				$cart_contents[ $key ]['_video_inner_message'] = self::sanitize_inner_message_data( $left_data );
				$cart_contents[ $key ]['_inner_message']       = self::sanitize_inner_message_data( $right_data );
				$data_changed                                  = true;
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
	 * @param  WC_Order_Item_Product  $item
	 * @param  string  $cart_item_key
	 * @param  array  $values
	 * @param  WC_Order  $order
	 */
	public function create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( array_key_exists( '_inner_message', $values ) ) {
			$item->add_meta_data( '_inner_message', static::sanitize_inner_message_data( $values['_inner_message'] ) );
		}
		if ( array_key_exists( '_video_inner_message', $values ) ) {
			$_data = static::sanitize_inner_message_data( $values['_video_inner_message'], true );
			$item->add_meta_data( '_video_inner_message', $_data );
			if ( 'video' == $_data['type'] ) {
				$meta_data = [
					'_should_delete_after_time' => ( time() + ( MONTH_IN_SECONDS * 6 ) ),
					'_wc_order_id'              => $item->get_order_id(),
					'_wc_order_item_id'         => $item->get_id(),
				];
				if ( is_numeric( $_data['video_id'] ) ) {
					foreach ( $meta_data as $meta_key => $meta_value ) {
						update_post_meta( $_data['video_id'], $meta_key, $meta_value );
					}
				} elseif ( isset( $_data['aws_job_id'] ) ) {
					update_option( '_aws_media_convert_' . $_data['aws_job_id'], $meta_data );
				}
			}
		}
	}

	/**
	 * Generate inner message pdf
	 *
	 * @param  WC_Order  $order
	 *
	 * @return void
	 */
	public function generate_inner_message_pdf( \WC_Order $order ) {
		BackgroundInnerMessagePdfGenerator::generate_for_order( $order );
	}

	/**
	 * Display on Order detail page and (Order received / Thank you page)
	 *
	 * @param  array  $formatted_meta
	 * @param  WC_Order_Item_Product  $order_item
	 *
	 * @return mixed
	 */
	public function order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {
		if ( $order_item instanceof WC_Order_Item_Product ) {
			$meta   = Utils::get_formatted_meta_for_video_message( $order_item );
			$r_meta = Utils::get_formatted_meta_for_right_text_message( $order_item );
			foreach ( array_merge( $meta, $r_meta ) as $item ) {
				$formatted_meta[] = $item;
			}

			if ( is_admin() ) {
				$pdf_url = add_query_arg( [
					'action'   => 'yousaidit_single_pdf_card',
					'order_id' => $order_item->get_order_id(),
					'item_id'  => $order_item->get_id(),
					'mode'     => 'pdf'
				], admin_url( 'admin-ajax.php' ) );

				$formatted_meta[] = (object) array(
					'display_key'   => 'Card PDF',
					'display_value' => "<a target='_blank' href='" . esc_url( $pdf_url ) . "'>View PDF</a>",
				);
			}
		}

		return $formatted_meta;
	}

	/**
	 * @param  mixed  $data  The data to be sanitized.
	 * @param  bool  $contains_video_data  Is it contain video data?
	 *
	 * @return array
	 */
	public static function sanitize_inner_message_data( $data, bool $contains_video_data = false ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		if ( isset( $data['message'] ) && ! isset( $data['content'] ) ) {
			$data['content'] = $data['message'];
			unset( $data['message'] );
		}
		if ( isset( $data['font_family'] ) && ! isset( $data['font'] ) ) {
			$data['font'] = $data['font_family'];
			unset( $data['font_family'] );
		}
		if ( isset( $data['font_size'] ) && ! isset( $data['size'] ) ) {
			$data['size'] = $data['font_size'];
			unset( $data['font_size'] );
		}
		if ( isset( $data['alignment'] ) && ! isset( $data['align'] ) ) {
			$data['align'] = $data['alignment'];
			unset( $data['alignment'] );
		}

		$default = [
			'type'     => 'text',
			'content'  => '',
			'font'     => '',
			'size'     => '',
			'align'    => '',
			'color'    => '',
			'video_id' => 0,
		];
		$data    = wp_parse_args( $data, $default );

		$content = stripslashes( wp_filter_post_kses( $data['content'] ) );
		$content = Utils::sanitize_inner_message_text( $content );

		$sanitized_data = [
			'content' => $content,
			'font'    => sanitize_text_field( stripslashes( $data['font'] ) ),
			'align'   => sanitize_text_field( stripslashes( $data['align'] ) ),
			'color'   => sanitize_hex_color( stripslashes( $data['color'] ) ),
			'size'    => intval( $data['size'] ),
		];

		$count_data = array_filter( array_values( $sanitized_data ) );
		if ( ! $contains_video_data && count( $count_data ) < count( array_keys( $sanitized_data ) ) ) {
			return [];
		}

		if ( $contains_video_data ) {
			$sanitized_data['type'] = sanitize_text_field( stripslashes( $data['type'] ) );
			if ( is_numeric( $data['video_id'] ) ) {
				$sanitized_data['video_id'] = intval( $data['video_id'] );
			} else {
				$video_id = AWSElementalMediaConvert::job_id_to_video_id( $data['video_id'] );
				if ( $video_id ) {
					$sanitized_data['video_id'] = $video_id;
				} else {
					$sanitized_data['video_id']   = sanitize_text_field( $data['video_id'] );
					$sanitized_data['aws_job_id'] = sanitize_text_field( $data['video_id'] );
				}
			}
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
