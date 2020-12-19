<?php

namespace YouSaidItCards\Modules\Customer\Models;

use ArrayObject;
use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Supports\Sanitize;
use WC_Customer;
use WP_User;

class Customer extends Data {
	/**
	 * @var array
	 */
	protected static $meta_fields = [];

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Customer constructor.
	 *
	 * @param int|WP_User $user
	 */
	public function __construct( $user ) {
		$this->user = new WP_User( $user );
		$this->set_data( $this->get_user()->to_array() );
	}

	public function to_array(): array {
		$user = $this->get_user();

		$data = [
			'id'                => $user->ID,
			'email'             => $user->user_email,
			'display_name'      => $user->display_name,
			'avatar_url'        => $this->get_avatar_url(),
			'default_addresses' => [
				'billing'  => new ArrayObject(),
				'shipping' => new ArrayObject(),
			],
		];

		if ( class_exists( WC_Customer::class ) ) {
			$customer                = new WC_Customer( $user->ID );
			$data['default_addresses'] = [
				'billing'  => $customer->get_billing(),
				'shipping' => $customer->get_shipping(),
			];
		}

		$data['addresses'] = Address::get_user_address( $user->ID );

		return $data;
	}

	/**
	 * Get avatar id
	 *
	 * @return int
	 */
	public function get_avatar_id(): int {
		return (int) get_user_meta( $this->get_user()->ID, '_avatar_id', true );
	}

	/**
	 * Get avatar url
	 *
	 * @return string
	 */
	public function get_avatar_url(): string {
		$id = $this->get_avatar_id();
		if ( $id ) {

			$src = wp_get_attachment_image_src( $id, 'medium-large' );

			if ( isset( $src[0] ) && filter_var( $src[0], FILTER_VALIDATE_URL ) ) {
				return $src[0];
			}
		}

		return get_avatar_url( $this->get_user()->user_email );
	}

	/**
	 * Get user
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	public static function update_avatar_id( int $user_id, int $avatar_id ): bool {
		$current_avatar_id = (int) get_user_meta( $user_id, '_avatar_id', true );
		if ( $current_avatar_id ) {
			wp_delete_attachment( $current_avatar_id, true );
		}

		return (bool) update_user_meta( $user_id, '_avatar_id', $avatar_id );
	}

	/**
	 * @param int   $user_id
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function update( int $user_id, array $params ): bool {
		$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		if ( ! empty( $name ) ) {
			$names = static::build_first_and_last_name( $name );
			wp_update_user( [
				'ID'           => $user_id,
				'first_name'   => $names['first_name'],
				'last_name'    => $names['last_name'],
				'display_name' => $name,
			] );
		}

		// Update additional data
		if ( count( static::$meta_fields ) ) {
			foreach ( static::$meta_fields as $field ) {
				$rest_param = is_array( $field ) ? $field['rest_param'] : $field;
				if ( ! isset( $params[ $rest_param ] ) ) {
					continue;
				}
				$meta_key          = isset( $field['meta_key'] ) ? $field['meta_key'] : '_' . $rest_param;
				$sanitize_callback = isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ?
					$field['sanitize_callback'] : [ Sanitize::class, 'deep' ];
				$value             = call_user_func( $sanitize_callback, $params[ $rest_param ] );
				update_user_meta( $user_id, $meta_key, $value );
			}
		}

		return true;
	}

	/**
	 * Build first and last name
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	public static function build_first_and_last_name( string $name ): array {
		$name_parts = explode( " ", trim( $name ) );
		$last_name  = array_pop( $name_parts );
		$first_name = count( $name_parts ) > 0 ? implode( " ", $name_parts ) : "";

		return [ 'first_name' => $first_name, 'last_name' => $last_name, ];
	}
}
