<?php

namespace YouSaidItCards\Modules\DynamicCard\Models;

use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Utilities\FreePdfBase;

class OrderItemDynamicCard {
	protected $card_id = 0;
	protected $card_size;
	protected $card_width = 0;
	protected $card_height = 0;
	protected $background;
	protected $background_type = 'color';
	protected $background_color = '#ffffff';
	protected $background_image = 0;
	protected $card_sections = [];
	protected $order;
	protected $order_item;
	protected $product;
	protected $card_payload_read = false;

	public function __construct( WC_Order $order, WC_Order_Item_Product $order_item ) {
		$this->order      = $order;
		$this->order_item = $order_item;
		$this->product    = $order_item->get_product();
		$this->card_id    = (int) $this->product->get_meta( '_card_id', true );
		$this->read_card_payload();
	}

	/**
	 * Read card payload
	 */
	public function read_card_payload() {
		if ( $this->card_payload_read ) {
			return;
		}
		$payload_value = (array) $this->order_item->get_meta( '_dynamic_card', true );
		$changed_data  = [];
		foreach ( $payload_value as $item ) {
			if ( ! isset( $item['value'] ) ) {
				continue;
			}
			$changed_data[] = is_numeric( $item['value'] ) ? intval( $item['value'] ) : sanitize_text_field( $item['value'] );
		}
		if ( count( $changed_data ) < 1 ) {
			return;
		}

		$payload               = $this->product->get_meta( '_dynamic_card_payload', true );
		$this->card_size       = $payload['card_size'];
		$this->background_type = $payload['card_bg_type'];
		if ( 'image' == $this->background_type ) {
			$this->background_image = $payload['card_background']['id'];
		} else {
			$this->background_color = $payload['card_bg_color'];
		}

		$sizes = FreePdfBase::get_sizes();
		if ( array_key_exists( $this->card_size, $sizes ) ) {
			list( $this->card_width, $this->card_height ) = $sizes[ $this->card_size ];
		}

		$this->background = new CardBackgroundOption( [
			'type'        => 'color',
			'color'       => $this->background_color,
			'image'       => $this->background_image,
			'card_width'  => $this->card_width,
			'card_height' => $this->card_height,
		] );

		$sections = [];
		foreach ( $payload['card_items'] as $index => $item ) {
			$item['dirty'] = false;
			$changed_value = $changed_data[ $index ] ?? '';
			if ( in_array( $item['section_type'], [ 'static-text', 'input-text' ] ) ) {
				if ( ! empty( $changed_value ) && $changed_value != $item['text'] ) {
					$item['text']  = $changed_value;
					$item['dirty'] = true;
				}
			}
			if ( in_array( $item['section_type'], [ 'static-image', 'input-image' ] ) ) {
				$current_value = $item['imageOptions']['img']['id'];
				if ( ! empty( $changed_value ) && $changed_value != $current_value ) {
					$item['dirty']                     = true;
					$item['imageOptions']['img']['id'] = $changed_value;
				}
			}
			$sections[ $index ] = $item;
		}

		$this->card_sections     = $sections;
		$this->card_payload_read = true;
	}
}
