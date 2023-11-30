<?php

namespace YouSaidItCards\REST;

use WC_Product;
use WC_Product_Data_Store_CPT;
use WC_Product_Query;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\ShipStation\OrderItemPdf;
use YouSaidItCards\Utilities\PdfSizeCalculator;

class ProductController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/products', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( $this->namespace, '/products/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( $this->namespace, '/order-item-pdf', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_order_item_pdf' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Retrieves a collection of devices.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$page   = $request->get_param( 'page' );
		$page   = is_numeric( $page ) ? intval( $page ) : 1;
		$search = $request->get_param( 'search' );

		if ( ! empty( $search ) && is_scalar( $search ) ) {
			$products_ids = ( new WC_Product_Data_Store_CPT )->search_products( $search, '', true );
			$products     = $this->format_for_response( $products_ids );

			return $this->respondOK( [ 'items' => $products, 'total_items' => count( $products ) ] );
		}

		$query = new WC_Product_Query( [
			'status'     => 'publish',
			'return'     => 'ids',
			'limit'      => 50,
			'page'       => $page,
			'visibility' => 'catalog',
			'paginate'   => true,
		] );

		$_products = $query->get_products();

		$total_products = isset( $_products->total ) ? intval( $_products->total ) : 0;
		$products_ids   = isset( $_products->products ) ? $_products->products : [];
		$products       = $this->format_for_response( $products_ids );


		return $this->respondOK( [ 'items' => $products, 'total_items' => $total_products ] );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param  WP_REST_Request  $request  Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$id     = (int) $request->get_param( 'id' );
		$pdf_id = (int) $request->get_param( 'pdf_id' );

		$product = wc_get_product( $id );
		if ( ! $product instanceof WC_Product ) {
			return $this->respondNotFound();
		}

		if ( $pdf_id > 0 ) {
			$product->update_meta_data( '_pdf_id', $pdf_id );
			$product->save_meta_data();

			$art_work = [
				'id'        => $pdf_id,
				'title'     => get_the_title( $pdf_id ),
				'url'       => wp_get_attachment_url( $pdf_id ),
				'thumb_url' => wp_get_attachment_thumb_url( $pdf_id )
			];

			// Update pdf width and height
			PdfSizeCalculator::init()->push_to_queue( [ 'pdf_id' => $pdf_id ] );

		} else {
			$product->update_meta_data( '_pdf_id', '' );
			$product->save_meta_data();

			$art_work = [ 'id' => $pdf_id, ];
		}

		return $this->respondOK( $art_work );
	}

	/**
	 * @param  array  $products_ids
	 *
	 * @return array
	 */
	private function format_for_response( array $products_ids ) {
		$products = [];
		foreach ( $products_ids as $index => $product_id ) {
			/** @var WC_Product|\WC_Product_Variable $_product */
			$_product = wc_get_product( $product_id );
			if ( ! $_product instanceof WC_Product ) {
				continue;
			}
			$products[ $index ] = [
				'id'           => $_product->get_id(),
				'title'        => $_product->get_title(),
				'product_sku'  => $_product->get_sku(),
				'product_type' => $_product->get_type(),
				'attributes'   => $_product->get_attributes(),
				'art_work'     => $this->get_pdf_card_data( $_product ),
			];

			if ( $_product instanceof \WC_Product_Variable ) {
				$child_products = $_product->get_children();
				foreach ( $child_products as $_index => $child_id ) {
					$variation = new \WC_Product_Variation( $child_id );

					$products[ $index ]['variations'][ $_index ] = [
						'id'       => $variation->get_id(),
						'sku'      => $variation->get_sku(),
						'art_work' => $this->get_pdf_card_data( $variation ),
					];
				}

			}
		}

		return $products;
	}

	/**
	 * Get PDF card data
	 *
	 * @param  WC_Product  $product
	 *
	 * @return array
	 */
	private function get_pdf_card_data( $product ) {
		$pdf_id = (int) $product->get_meta( '_pdf_id', true );

		if ( empty( $pdf_id ) ) {
			return [ 'id' => 0 ];
		}

		return [
			'id'        => $pdf_id,
			'title'     => get_the_title( $pdf_id ),
			'url'       => wp_get_attachment_url( $pdf_id ),
			'thumb_url' => wp_get_attachment_thumb_url( $pdf_id ),
			'width'     => (int) get_post_meta( $pdf_id, '_pdf_width_millimeter', true ),
			'height'    => (int) get_post_meta( $pdf_id, '_pdf_height_millimeter', true ),
		];
	}

	public function update_order_item_pdf( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}
		$pdf_id = (int) $request->get_param( 'pdf_id' );
		$post   = get_post( $pdf_id );
		if ( ! $post instanceof \WP_Post ) {
			return $this->respondNotFound();
		}
		list( $pdf_width, $pdf_height ) = PdfSizeCalculator::calculate_pdf_width_and_height( $pdf_id );

		$product_id    = (int) $request->get_param( 'product_id' );
		$order_id      = (int) $request->get_param( 'order_id' );
		$order_item_id = (int) $request->get_param( 'order_item_id' );
		$store_id      = (int) $request->get_param( 'store_id' );
		$product_sku   = $request->get_param( 'product_sku' );
		$card_size     = $request->get_param( 'card_size' );

		$data = OrderItemPdf::create_if_not_exists( [
			'product_id'    => $product_id,
			'product_sku'   => $product_sku,
			'card_size'     => $card_size,
			'order_id'      => $order_id,
			'order_item_id' => $order_item_id,
			'store_id'      => $store_id,
			'pdf_id'        => $pdf_id,
			'pdf_width'     => $pdf_width,
			'pdf_height'    => $pdf_height,
		] );

		return $this->respondCreated( $data );
	}
}
