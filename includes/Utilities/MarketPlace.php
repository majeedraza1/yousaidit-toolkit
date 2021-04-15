<?php

namespace YouSaidItCards\Utilities;

use YouSaidItCards\Admin\SettingPage;
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
				'storeId' => (int) SettingPage::get_option( 'shipstation_yousaidit_store_id' ),
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
				'storeId' => (int) SettingPage::get_option( 'shipstation_etsy_store_id' ),
			],
			[
				'key'     => 'amazon',
				'label'   => 'Amazon',
				'logo'    => Assets::get_assets_url( '/images/logo-amazon.png' ),
				'storeId' => (int) SettingPage::get_option( 'shipstation_amazon_store_id' ),
			],
			[
				'key'     => 'ebay',
				'label'   => 'eBay',
				'logo'    => Assets::get_assets_url( '/images/logo-ebay.png' ),
				'storeId' => (int) SettingPage::get_option( 'shipstation_ebay_store_id' ),
			],
		];
	}

	/**
	 * @param int $store_id
	 *
	 * @return array|false
	 */
	public static function get( int $store_id ) {
		$stores = static::all();
		foreach ( $stores as $store ) {
			if ( $store['storeId'] == $store_id ) {
				return $store;
			}
		}

		return false;
	}

	public static function get_shipstation_order_status(): array {
		return [
			'awaiting_payment'    => __( 'Awaiting Payment' ),
			'awaiting_shipment'   => __( 'Awaiting Shipment' ),
			'pending_fulfillment' => __( 'Pending Fulfillment' ),
			'shipped'             => __( 'Shipped' ),
			'on_hold'             => __( 'On Hold' ),
			'cancelled'           => __( 'Cancelled' )
		];
	}
}
