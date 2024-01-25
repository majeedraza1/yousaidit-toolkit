<?php

namespace YouSaidItCards\Modules\CardPopup;

use YouSaidItCards\Admin\SettingPage;

/**
 * Settings class
 */
class Settings {
	/**
	 * Get popup categories
	 *
	 * @return array
	 */
	public static function get_popup_categories(): array {
		$categories_ids = get_transient( 'card_popup_categories' );
		if ( is_array( $categories_ids ) ) {
			return $categories_ids;
		}
		$categories_ids = SettingPage::get_option( 'card_popup_categories' );
		$categories_ids = is_array( $categories_ids ) ? array_filter( array_map( 'intval', $categories_ids ) ) : [];

		$children = [];
		foreach ( $categories_ids as $categories_id ) {
			$children = array_merge( $children, get_term_children( $categories_id, 'product_cat' ) );
		}

		$terms_ids = array_merge( $categories_ids, $children );
		set_transient( 'card_popup_categories', $terms_ids, HOUR_IN_SECONDS );

		return $terms_ids;
	}

	/**
	 * Should show popup
	 *
	 * @param  \WC_Product  $product
	 *
	 * @return bool
	 */
	public static function should_show_popup( \WC_Product $product ): bool {
		$categories   = static::get_popup_categories();
		$category_ids = $product->get_category_ids();
		$category     = array_intersect( $categories, $category_ids );

		return count( $category ) > 0;
	}
}