<?php

namespace YouSaidItCards\Modules\CardPopup;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

/**
 * Wishlist
 */
class Wishlist extends DatabaseModel {
	protected $table = 'yith_wcwl';

	public static function get_wishlist_list( int $user_id = 0 ): array {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		global $wpdb;
		$self  = new static();
		$table = $self->get_table_name( 'yith_wcwl_lists' );

		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id );
		$result = $wpdb->get_row( $sql, ARRAY_A );

		return is_array( $result ) ? $result : [];
	}

	public static function add_to_list( int $product_id, int $user_id = 0 ): bool {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$ids = static::get_wishlist_items( $user_id );
		if ( in_array( $product_id, $ids, true ) ) {
			return true;
		}

		$list = static::get_wishlist_list( $user_id );

		$record_id = static::create( [
			'prod_id'     => $product_id,
			'user_id'     => $user_id,
			'quantity'    => 1,
			'position'    => 0,
			'dateadded'   => current_time( 'mysql' ),
			'wishlist_id' => $list['ID'] ?? 0,
		] );

		return ! ! $record_id;
	}

	public static function remove_from_list( int $product_id, int $user_id = 0 ): bool {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$ids = static::get_wishlist_items( $user_id );
		if ( ! in_array( $product_id, $ids, true ) ) {
			return true;
		}
		global $wpdb;
		$self  = new static();
		$table = $self->get_table_name();

		$sql = $wpdb->prepare( "DELETE FROM $table WHERE user_id = %d AND prod_id = %d", $user_id, $product_id );

		return ! ! $wpdb->query( $sql );
	}

	/**
	 * Get wishlist items
	 *
	 * @param  int  $user_id
	 *
	 * @return array
	 */
	public static function get_wishlist_items( int $user_id = 0 ): array {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$self  = new static();
		$query = $self->get_query_builder();
		$query->where( 'user_id', $user_id );
		$items = $query->get();

		$data = [];
		foreach ( $items as $item ) {
			$data[] = intval( $item['prod_id'] );
		}

		return $data;
	}

	public static function is_product_wishlist( int $product_id, int $user_id = 0 ): bool {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$ids = static::get_wishlist_items( $user_id );

		return in_array( $product_id, $ids, true );
	}

	/**
	 * @param  int  $product_id
	 * @param  int  $user_id
	 *
	 * @return string
	 */
	public static function get_wishlist_button( int $product_id, int $user_id = 0 ): string {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$is_in_wishlist = static::is_product_wishlist( $product_id, $user_id );
		$class          = [ 'yousaidit_wishlist' ];
		if ( $is_in_wishlist ) {
			$action_text = 'Remove from Wishlist';
			$class[]     = 'remove-from-wishlist is-in-list';
			$action_url  = static::get_wishlist_ajax_url( $product_id, 'remove_from_wishlist' );
		} else {
			$action_text = 'Add to Wishlist';
			$class[]     = 'add-to-wishlist';
			$action_url  = static::get_wishlist_ajax_url( $product_id, 'add_to_wishlist' );
		}


		return sprintf(
			'<a class="%3$s" href="%1$s" title="%2$s" rel="nofollow"><span class="screen-reader-text">%2$s</span></a>',
			esc_attr( $action_url ),
			esc_attr( $action_text ),
			esc_attr( implode( ' ', $class ) )
		);
	}

	public static function get_wishlist_ajax_url( int $product_id, string $task ): string {
		$task = in_array( $task, [ 'add_to_wishlist', 'remove_from_wishlist' ] ) ? $task : 'toggle_wishlist';
		$url  = add_query_arg( [
			'action'     => 'yousaidit_wishlist',
			'task'       => $task,
			'product_id' => $product_id,
		], admin_url( 'admin-ajax.php' ) );

		return wp_nonce_url( $url, 'yousaidit_wishlist_nonce' );
	}
}