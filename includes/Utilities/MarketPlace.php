<?php

namespace YouSaidItCards\Utilities;

use YouSaidItCards\Assets;

class MarketPlace {
	/**
	 * Get market places list
	 *
	 * @return array[]
	 */
	public static function all(): array {
		return [
			[
				'key'     => 'yousaidit',
				'label'   => 'You Said It Cards',
				'logo'    => Assets::get_assets_url( '/images/logo-yousaidit.png' ),
				'storeId' => (int) get_option( 'shipstation_yousaidit_store_id' ),
			],
			[
				'key'     => 'yousaidit-trade',
				'label'   => 'You Said It Cards - Trade',
				'logo'    => Assets::get_assets_url( '/images/logo-yousaidit-trade.png' ),
				'storeId' => 0,
			],
			[
				'key'     => 'etsy',
				'label'   => 'Etsy',
				'logo'    => Assets::get_assets_url( '/images/logo-etsy.svg' ),
				'storeId' => (int) get_option( 'shipstation_etsy_store_id' ),
			],
			[
				'key'     => 'amazon',
				'label'   => 'Amazon',
				'logo'    => Assets::get_assets_url( '/images/logo-amazon.png' ),
				'storeId' => (int) get_option( 'shipstation_amazon_store_id' ),
			],
			[
				'key'     => 'ebay',
				'label'   => 'eBay',
				'logo'    => Assets::get_assets_url( '/images/logo-ebay.png' ),
				'storeId' => (int) get_option( 'shipstation_ebay_store_id' ),
			],
		];
	}
}
