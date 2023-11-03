<?php

namespace YouSaidItCards\Modules\CardPopup;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

/**
 * Wishlist
 */
class Wishlist extends DatabaseModel {
	protected $table = 'yith_wcwl';

	public static function remove_by_wishlist_id( int $wishlist_id, int $product_id ): bool {
		global $wpdb;
		$table = static::get_table_name();

		$sql = $wpdb->prepare(
			"DELETE FROM $table WHERE wishlist_id = %d AND prod_id = %d",
			$wishlist_id,
			$product_id
		);

		return ! ! $wpdb->query( $sql );
	}
}