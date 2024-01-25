<?php

namespace YouSaidItCards\Modules\CardPopup;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class WishlistList extends DatabaseModel {
	protected $table = 'yith_wcwl_lists';
	protected $primary_key = 'ID';

	/**
	 * Wishlist products ids
	 *
	 * @var array
	 */
	protected $wishlist_items = [];
	protected $wishlist_items_read = false;

	/**
	 * Get user id
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return (int) $this->get_prop( 'user_id' );
	}

	/**
	 * Get wishlist product ids
	 *
	 * @return int[]
	 */
	public function get_wishlist_items(): array {
		if ( false === $this->wishlist_items_read ) {
			$query = Wishlist::get_query_builder();
			$query->where( 'wishlist_id', $this->get_id() );
			$items = $query->get();
			foreach ( $items as $item ) {
				$this->wishlist_items[] = intval( $item['prod_id'] );
			}
			$this->wishlist_items_read = true;
		}

		return $this->wishlist_items;
	}

	/**
	 * Is product in wishlist
	 *
	 * @param  int  $product_id  Product id.
	 *
	 * @return bool
	 */
	public function is_product_wishlist( int $product_id ): bool {
		return in_array( $product_id, $this->get_wishlist_items(), true );
	}

	/**
	 * Add to wish list
	 *
	 * @param  int  $product_id
	 * @param  int  $user_id
	 *
	 * @return bool
	 */
	public function add_to_list( int $product_id, int $user_id = 0 ): bool {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$wishlist_items = $this->get_wishlist_items();
		if ( in_array( $product_id, $wishlist_items, true ) ) {
			return true;
		}

		$record_id = Wishlist::create( [
			'prod_id'     => $product_id,
			'user_id'     => $user_id ?? null,
			'quantity'    => 1,
			'position'    => 0,
			'dateadded'   => current_time( 'mysql' ),
			'wishlist_id' => $this->get_id(),
		] );

		return ! ! $record_id;
	}

	/**
	 * Remove from wishlist
	 *
	 * @param  int  $product_id
	 *
	 * @return bool
	 */
	public function remove_from_list( int $product_id ): bool {
		$is_removed = Wishlist::remove_by_wishlist_id( $this->get_id(), $product_id );
		if ( $is_removed ) {
			$index = array_search( $product_id, $this->wishlist_items, true );
			if ( false !== $index ) {
				unset( $this->wishlist_items[ $index ] );
			}
		}

		return $is_removed;
	}

	/**
	 * Get current user wishlist list
	 *
	 * @return false|WishlistList
	 */
	public static function get_current_user_wishlist_list() {
		$user_id = get_current_user_id();
		$list    = static::find_by_user_id( $user_id );
		if ( $list instanceof static ) {
			return $list;
		}
		$session    = static::get_session_data();
		$session    = is_array( $session ) ? $session : [];
		$session_id = $session['session_id'] ?? null;
		$expiration = $session['session_expiration'] ?? 0;
		$list       = static::find_by_session_id( $session_id );
		if ( $list instanceof static ) {
			return $list;
		}

		$token = YITH_WCWL()->generate_wishlist_token();

		$data = [
			'user_id'        => $user_id ?? null,
			'wishlist_token' => $token,
			'dateadded'      => current_time( 'mysql' ),
			'is_default'     => 1,
		];

		if ( $user_id ) {
			$data['expiration'] = null;
			$data['session_id'] = null;
		} elseif ( $session_id ) {
			$data['expiration'] = date( 'Y-m-d H:i:s', $expiration );
			$data['session_id'] = $session_id;
		}

		if ( empty( $data['user_id'] ) && empty( $data['session_id'] ) ) {
			return false;
		}

		global $wpdb;
		$table      = static::get_table_name();
		$data['ID'] = $wpdb->insert( $table, $data );

		return new static( $data );
	}

	/**
	 * Find by user id
	 *
	 * @param  int  $user_id  The user id.
	 *
	 * @return false|static
	 */
	private static function find_by_user_id( int $user_id ) {
		if ( $user_id ) {
			global $wpdb;
			$table = static::get_table_name();

			$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id );
			$result = $wpdb->get_row( $sql, ARRAY_A );
			if ( $result ) {
				return new static( $result );
			}
		}

		return false;
	}

	/**
	 * Find by session id
	 *
	 * @param  null|string  $session_id  The session id.
	 *
	 * @return false|static
	 */
	private static function find_by_session_id( ?string $session_id ) {
		if ( is_string( $session_id ) && ! empty( $session_id ) ) {
			global $wpdb;
			$table = static::get_table_name();

			$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE session_id = %s", $session_id );
			$result = $wpdb->get_row( $sql, ARRAY_A );

			if ( $result ) {
				return new static( $result );
			}
		}

		return false;
	}

	/**
	 * Get session data
	 *
	 * @return array|bool|false
	 */
	public static function get_session_data() {
		if ( function_exists( 'YITH_WCWL_Session' ) ) {
			$cookie_instance = YITH_WCWL_Session();
			$cookie          = $cookie_instance->get_session_cookie();
			if ( empty( $cookie ) ) {
				$cookie_instance->init_session_cookie();
				$cookie = $cookie_instance->get_session_cookie();
			}
			if ( is_array( $cookie ) ) {
				return $cookie;
			}
		}
		$cookies = $_COOKIE;
		$default = [ 'session_id' => '', 'session_expiration' => 0, 'session_expiring' => 0, 'cookie_hash' => '' ];
		$cookie  = null;
		foreach ( $cookies as $cookie_key => $cookie_value ) {
			if ( false !== strpos( $cookie_key, 'yith_wcwl_session_' ) ) {
				$cookie = json_decode( stripslashes( $cookie_value ) );
			}
		}

		return is_array( $cookie ) ? wp_parse_args( $cookie, $default ) : $default;
	}

	/**
	 * @param  int  $product_id
	 *
	 * @return string
	 */
	public static function get_wishlist_button( int $product_id ): string {
		$list  = static::get_current_user_wishlist_list();
		$class = [ 'yousaidit_wishlist' ];
		if ( $list instanceof static && $list->is_product_wishlist( $product_id ) ) {
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

	/**
	 * Get wishlist ajax url
	 *
	 * @param  int  $product_id
	 * @param  string  $task
	 *
	 * @return string
	 */
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