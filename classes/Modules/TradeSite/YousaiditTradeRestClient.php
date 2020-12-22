<?php

namespace Yousaidit\Modules\TradeSite;

use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;
use YouSaidItCards\Admin\SettingPage;

class YousaiditTradeRestClient extends RestClient {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$url           = rtrim( SettingPage::get_option( 'trade_site_url' ), '/' );
		$namespace     = trim( SettingPage::get_option( 'trade_site_rest_namespace' ), '/' );
		$base_endpoint = sprintf( "%s/%s", $url, $namespace );

		$this->add_headers( 'X-Auth-Token', SettingPage::get_option( 'trade_site_auth_token' ) );

		parent::__construct( $base_endpoint );
	}

	public function get_profile() {
		return $this->get( 'me' );
	}

	/**
	 * Create product from card info
	 *
	 * @param array $card
	 *
	 * @return array|WP_Error
	 */
	public function create_product( array $card ) {
		$endpoint = SettingPage::get_option( 'trade_site_card_to_product_endpoint' );

		return $this->post( $endpoint, [ 'card' => $card ] );
	}
}
