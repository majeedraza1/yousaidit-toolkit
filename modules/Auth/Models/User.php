<?php

namespace YouSaidItCards\Modules\Auth\Models;

use WP_User;

class User implements \JsonSerializable {
	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * User constructor.
	 *
	 * @param int|string|\stdClass|WP_User $user
	 */
	public function __construct( $user = null ) {
		$this->user = new WP_User( $user );
	}

	public function to_array(): array {
		$user = $this->get_user();

		return [
			'id'           => $user->ID,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'avatar_url'   => get_avatar_url( $user, [ 'default' => 'mm' ] ),
			'is_verified'  => $this->is_registration_verified(),
		];
	}

	/**
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * Check if registration verified
	 *
	 * @return bool
	 */
	public function is_registration_verified(): bool {
		$is_verified = get_user_meta( $this->get_user()->ID, '_is_registration_verified', true );

		return 'yes' == $is_verified;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
