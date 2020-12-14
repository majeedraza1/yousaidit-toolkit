<?php

namespace YouSaidItCards\Modules\Customer\Models;

class Address extends BaseAddress {

	/**
	 * @return string[]
	 */
	public function to_array(): array {
		return [
			'name'          => $this->get_name(),
			'phone_number'  => $this->get_phone_number(),
			'address_line1' => $this->get_address_line1(),
			'address_line2' => $this->get_address_line2(),
			'city'          => $this->get_city(),
			'state'         => $this->get_state(),
			'country_code'  => $this->get_country_code(),
			'country_name'  => $this->get_country_name(),
			'postal_code'   => $this->get_postal_code(),
			'landmark'      => $this->get_landmark(),
			'address_type'  => $this->get_address_type(),
			'address_label' => $this->get_address_label(),
		];
	}

	public function get_city(): string {
		return $this->get_address_level2();
	}

	public function get_state(): string {
		return $this->get_address_level1();
	}

	/**
	 * @param int $user_id
	 *
	 * @return array|static[]
	 */
	public static function get_user_address( $user_id = 0 ): array {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( ! ( is_numeric( $user_id ) && $user_id ) ) {
			return [];
		}
		global $wpdb;
		$self    = new static;
		$table   = $self->get_table_name();
		$sql     = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$data    = [];
		foreach ( $results as $result ) {
			$data[] = new static( $result );
		}

		return $data;
	}
}
