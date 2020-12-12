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
		];
	}

	/**
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
