<?php

namespace YouSaidItCards\Utilities;

use YouSaidItCards\Assets;

class MarketPlace {
	/**
	 * Get market places list
	 *
	 * @return array[]
	 */
	public static function all() {
		return [
			[
				'key'   => 'yousaidit',
				'label' => 'You Said It Cards',
				'logo'  => Assets::get_assets_url( '/images/logo-yousaidit.png' )
			],
			[
				'key'   => 'yousaidit-trade',
				'label' => 'You Said It Cards - Trade',
				'logo'  => Assets::get_assets_url( '/images/logo-yousaidit-trade.png' )
			],
			[
				'key'   => 'etsy',
				'label' => 'Etsy',
				'logo'  => Assets::get_assets_url( '/images/logo-etsy.svg' )
			],
			[
				'key'   => 'amazon',
				'label' => 'Amazon',
				'logo'  => Assets::get_assets_url( '/images/logo-amazon.png' )
			],
			[
				'key'   => 'ebay',
				'label' => 'eBay',
				'logo'  => Assets::get_assets_url( '/images/logo-ebay.png' )
			],
		];
	}
}
