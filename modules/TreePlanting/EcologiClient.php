<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\Supports\RestClient;

/**
 * EcologiClient class
 */
class EcologiClient extends RestClient {
	public function __construct() {
		$this->add_auth_header( Setting::api_key(), 'Bearer' );
		$this->add_headers( 'Content-Type', 'application/json; charset=utf-8' );
		$this->add_headers( 'Referer', site_url() );
		parent::__construct( 'https://public.ecologi.com' );
	}

	/**
	 * Purchase trees
	 *
	 * @param  int  $number  Number of trees to purchase.
	 *
	 * @return array|\WP_Error
	 * @link https://docs.ecologi.com/docs/public-api-docs/004342d262f93-purchase-trees
	 */
	public static function purchase_tree( int $number = 0 ) {
		if ( ! $number ) {
			$number = Setting::number_of_tree_to_purchase();
		}

		return ( new static() )->post( 'impact/trees', wp_json_encode( [
			'number' => $number,
			'name'   => Setting::funded_by(),
			'test'   => Setting::is_test_mode(),
		] ) );
	}
}