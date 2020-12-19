<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use Exception;
use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Supports\Sanitize;
use WC_Customer;
use WP_REST_Server;
use YouSaidItCards\Modules\Customer\Models\Address;
use YouSaidItCards\Modules\WooCommerce\WcRestClient;
use YouSaidItCards\REST\ApiController;

class OrderController extends ApiController {

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

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'me/orders', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
				'args'                => $this->get_collection_params(),
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'is_logged_in' ],
				'args'                => $this->get_create_item_params(),
			]
		] );
		register_rest_route( $this->namespace, 'me/orders/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_item' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
			'args'                => $this->get_collection_params(),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$rest_client = new WcRestClient();
		$args        = [
			'customer' => get_current_user_id(),
			'page'     => (int) $request->get_param( 'page' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
			'search'   => $request->get_param( 'search' ),
		];
		$orders      = $rest_client->list_orders( $args );
		if ( is_wp_error( $orders ) ) {
			return $this->respondWithError( $orders );
		}

		return $this->respondOK( [ 'items' => $orders ] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return $this->respondUnauthorized();
		}

		$line_items = $request->get_param( 'line_items' );
		if ( count( $line_items ) < 1 ) {
			return $this->respondUnprocessableEntity( null, 'No product item found.' );
		}


		$order = wc_create_order();
		$order->set_customer_id( $user->ID );

		$billing  = $request->get_param( 'billing' );
		$shipping = $request->get_param( 'shipping' );

		if ( is_numeric( $billing ) ) {
			$address = ( new Address )->find_single( intval( $billing ) );
			if ( $address instanceof Data ) {
				$billing          = $address->to_array();
				$billing['email'] = $user->user_email;
			}
		}

		try {
			$customer = new WC_Customer( $user->ID );
			if ( empty( $billing ) ) {
				$billing = $customer->get_billing();
			}
			if ( empty( $shipping ) ) {
				$shipping = $customer->get_shipping();
			}
		} catch ( Exception $e ) {
			Logger::log( $e );
		}

		if ( is_array( $billing ) ) {
			$order->set_address( $billing, 'billing' );
		}

		if ( is_array( $shipping ) ) {
			$order->set_address( $shipping, 'shipping' );
		}

		foreach ( $line_items as $line_item ) {
			$product_id    = isset( $line_item['product_id'] ) ? intval( $line_item['product_id'] ) : 0;
			$quantity      = isset( $line_item['quantity'] ) ? intval( $line_item['quantity'] ) : 0;
			$variation_id  = isset( $line_item['variation_id'] ) ? intval( $line_item['variation_id'] ) : 0;
			$inner_message = isset( $line_item['inner_message'] ) ? Sanitize::deep( $line_item['inner_message'] ) : [];

			if ( empty( $product_id ) || empty( $quantity ) ) {
				continue;
			}
			$product = wc_get_product( $variation_id ? $variation_id : $product_id );
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$order_item = new \WC_Order_Item_Product();
			$order_item->set_product( $product );
			$order_item->set_quantity( $quantity );
			$order_item->set_subtotal( wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) ) );
			$order_item->set_total( wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) ) );

			if ( ! empty( $inner_message ) ) {
				$order_item->add_meta_data( '_inner_message', $inner_message, true );
			}

			$order->add_item( $order_item );
		}

		// Set payment gateway
		$order->set_payment_method( $request->get_param( 'payment_method' ) );
		$order->set_payment_method_title( $request->get_param( 'payment_method_title' ) );

		$order->set_created_via( 'rest-api' );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );


		$item_sent_to = $request->get_param( 'item_sent_to' );
		$sent_to      = in_array( $item_sent_to, [ 'me', 'them' ] ) ? $item_sent_to : '';
		$order->add_meta_data( '_item_sent_to', $sent_to );

		$order->calculate_totals();
		$order->save();

		return $this->respondCreated( [ 'id' => $order->get_id(), ] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		$rest_client = new WcRestClient();
		$order       = $rest_client->list_order( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $order ) ) {
			return $this->respondWithError( $order );
		}

		return $this->respondOK( $order );
	}

	public function get_create_item_params(): array {
		return [
			'item_sent_to'         => [
				'type'        => 'string',
				'description' => __( 'Straight to customer door delivery' ),
				'enum'        => [ 'me', 'them' ]
			],
			'billing'              => [
				'type'        => [ 'integer', 'array' ],
				'description' => __( 'Customer billing address.' ),
			],
			'shipping'             => [
				'type'        => [ 'integer', 'array' ],
				'description' => __( 'Customer shipping address.' ),
			],
			'customer_note'        => [
				'type'        => 'string',
				'description' => __( 'Note left by customer during checkout.' )
			],
			'transaction_id'       => [
				'type'        => 'string',
				'description' => __( 'Unique transaction ID.' )
			],
			'payment_method'       => [
				'type'        => 'string',
				'required'    => true,
				'description' => __( 'Payment method ID.' )
			],
			'payment_method_title' => [
				'type'        => 'string',
				'description' => __( 'Payment method title.' )
			],
			'line_items'           => [
				'description' => __( 'Line items data.' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'product_id'    => [
							'type'        => 'integer',
							'required'    => true,
							'description' => __( 'Product ID.' ),
						],
						'quantity'      => [
							'type'        => 'integer',
							'required'    => true,
							'description' => __( 'Quantity ordered.' )
						],
						'variation_id'  => [
							'type'        => 'integer',
							'description' => __( 'Variation ID, if applicable.' )
						],
						'inner_message' => [
							'type'       => 'object',
							'properties' => [
								'content' => [ 'type' => 'string' ],
								'font'    => [ 'type' => 'string' ],
								'size'    => [ 'type' => 'integer' ],
								'align'   => [ 'type' => 'string' ],
								'color'   => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
		];
	}
}
