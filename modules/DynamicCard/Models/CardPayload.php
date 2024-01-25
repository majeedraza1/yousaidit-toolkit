<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use Stackonet\WP\Framework\Abstracts\Data;

class CardPayload extends Data {
	public function __construct( array $data = [], array $modified_data = [] ) {
		$data['card_items'] = $this->merge( $modified_data, (array) $data['card_items'] );
		parent::__construct( $data );
	}

	/**
	 * @param array $modified_data
	 * @param array $card_items
	 *
	 * @return array
	 */
	protected function merge( array $modified_data, array $card_items ): array {
		$changed_data = [];
		foreach ( $modified_data as $item ) {
			if ( ! isset( $item['value'] ) ) {
				continue;
			}
			$changed_data[] = is_numeric( $item['value'] ) ?
				intval( $item['value'] ) :
				sanitize_text_field( $item['value'] );
		}
		if ( count( $changed_data ) < 1 ) {
			return $card_items;
		}
		$sections = [];
		foreach ( $card_items as $index => $item ) {
			$item['dirty'] = false;
			$changed_value = $changed_data[ $index ] ?? '';
			if ( in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				if ( ! empty( $changed_value ) && $changed_value != $item['text'] ) {
					$item['text']  = $changed_value;
					$item['dirty'] = true;
				}
				$sections[ $index ] = $item;
			}
			if ( in_array( $item['section_type'], [ 'static-image', 'input-image' ] ) ) {
				$current_value = $item['imageOptions']['img']['id'];
				if ( ! empty( $changed_value ) && $changed_value != $current_value ) {
					$src = wp_get_attachment_image_src( $changed_value, 'full' );
					if ( is_array( $src ) ) {
						$item['dirty']                      = true;
						$item['imageOptions']['img']['id']  = $changed_value;
						$item['imageOptions']['img']['src'] = $src[0];
					}
				}
				$sections[ $index ] = $item;
			}
		}

		return $sections;
	}
}
