<?php

namespace YouSaidItCards\Modules\WooCommerce;

use Stackonet\WP\Framework\Supports\RestClient;
use YouSaidItCards\AdminUser;

class WcRestClient extends RestClient {
	public function __construct() {
		$api_base_url = esc_url_raw( rest_url( 'wc/v3' ) );
		$this->add_headers( 'X-Auth-Token', AdminUser::get_admin_auth_token() );
		parent::__construct( $api_base_url );
	}

	/**
	 * List collection of products
	 *
	 * @param array $args
	 *
	 * @return array|\WP_Error
	 */
	public function list_products( array $args = [] ) {
		return $this->get( 'products', $args );
	}

	/**
	 * List single product
	 *
	 * @param int $product_id
	 *
	 * @return array|\WP_Error
	 */
	public function list_product( int $product_id ) {
		return $this->get( 'products/' . $product_id );
	}

	/**
	 * List collection of orders
	 *
	 * @param array $args
	 *
	 * @return array|\WP_Error
	 */
	public function list_orders( array $args = [] ) {
		return $this->get( 'orders', $args );
	}

	/**
	 * List collection of orders
	 *
	 * @param array $args
	 *
	 * @return array|\WP_Error
	 */
	public function create_order( array $args = [] ) {
		return $this->post( 'orders', $args );
	}

	/**
	 * List single order
	 *
	 * @param int $order_id
	 *
	 * @return array|\WP_Error
	 */
	public function list_order( int $order_id ) {
		return $this->get( 'orders/' . $order_id );
	}

	/**
	 * List general data
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	public function list_general_data( bool $force = false ): array {
		$data = get_transient( 'wc_general_data' );
		if ( ! is_array( $data ) || $force ) {
			$gateways = $this->get( 'payment_gateways' );

			$data = [
				'taxes'            => $this->get( 'taxes' ),
				'taxes_classes'    => $this->get( 'taxes/classes' ),
				'payment_gateways' => $gateways,
				'shipping_zones'   => $this->get( 'shipping/zones' ),
				'shipping_methods' => $this->get( 'shipping_methods' ),
				'countries'        => $this->get( 'data/countries' ),
				'currencies'       => $this->get( 'data/currencies' ),
				'store_currency'   => $this->get( 'data/currencies/current' ),
			];
			set_transient( 'wc_general_data', $data, HOUR_IN_SECONDS );
		}

		return $data;
	}
}
