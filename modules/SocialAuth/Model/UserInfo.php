<?php

namespace YouSaidItCards\Modules\SocialAuth\Model;

use YouSaidItCards\Modules\SocialAuth\Interfaces\UserInfoInterface;

class UserInfo implements UserInfoInterface {
	protected array $data = [
		'provider'    => '',
		'uuid'        => '',
		'name'        => '',
		'email'       => '',
		'picture_url' => '',
	];

	protected ?string $first_name;
	protected ?string $last_name;

	public function __construct( array $data = [] ) {
		foreach ( $data as $key => $value ) {
			$this->data[ $key ] = $value;
			if ( $key === 'name' ) {
				$name_parts       = explode( " ", $value );
				$this->last_name  = array_pop( $name_parts );
				$this->first_name = count( $name_parts ) > 0 ? implode( " ", $name_parts ) : "";
			}
		}
	}

	public function get_provider(): string {
		return $this->data['provider'];
	}

	public function get_provider_uuid(): string {
		return $this->data['uuid'];
	}

	public function get_name(): string {
		return $this->data['name'];
	}

	public function get_email(): string {
		return $this->data['email'];
	}

	public function get_picture_url(): string {
		return $this->data['picture_url'];
	}

	public function get_first_name(): string {
		return (string) $this->first_name;
	}

	public function get_last_name(): string {
		return (string) $this->last_name;
	}
}