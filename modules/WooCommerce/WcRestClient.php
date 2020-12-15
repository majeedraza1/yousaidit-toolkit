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
	 * List order
	 *
	 * @param array $args
	 *
	 * @return array|\WP_Error
	 */
	public function list_orders( array $args = [] ) {
		return $this->get( 'orders', $args );
	}

	public function list_order( $order_id ) {
		return $this->get( 'orders/' . $order_id );
	}
}
