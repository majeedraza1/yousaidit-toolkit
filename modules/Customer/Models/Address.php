<?php

namespace YouSaidItCards\Modules\Customer\Models;

class Address extends BaseAddress {

	/**
	 * @return string[]
	 */
	public function to_array(): array {
		return [
			'id'            => $this->get_id(),
			'first_name'    => $this->get_first_name(),
			'last_name'     => $this->get_last_name(),
			'address_1'     => $this->get_address_line1(),
			'address_2'     => $this->get_address_line2(),
			'city'          => $this->get_city(),
			'postcode'      => $this->get_postal_code(),
			'country'       => $this->get_country_code(),
			'state'         => $this->get_state(),
			'phone'         => $this->get_phone_number(),
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
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name();
		$sql   = "SELECT COUNT(*) AS total_records FROM {$table} WHERE 1 = 1";
		if ( isset( $args['user_id'] ) ) {
			$sql .= $wpdb->prepare( " AND user_id = %d", intval( $args['user_id'] ) );
		}
		$row = $wpdb->get_row( $sql, ARRAY_A );

		return isset( $row['total_records'] ) ? intval( $row['total_records'] ) : 0;
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
