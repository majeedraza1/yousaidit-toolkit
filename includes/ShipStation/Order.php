<?php

namespace YouSaidItCards\ShipStation;

use ArrayObject;
use DateTime;
use Exception;
use JsonSerializable;
use Stackonet\WP\Framework\Supports\Validate;
use WC_Abstract_Order;
use WC_Product;
use WP_Error;

class Order implements JsonSerializable {

	/**
	 * ShipStation order data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Array of WooCommerce products
	 *
	 * @var WC_Product[]
	 */
	protected $products = [];

	/**
	 * Customer shipping address
	 *
	 * @var array
	 */
	protected $shipping_address = [];

	/**
	 * Order items
	 *
	 * @var OrderItem[]
	 */
	protected $order_items = [];

	/**
	 * @var array
	 */
	protected static $shop_address = [];

	/**
	 * @var bool
	 */
	protected $has_inner_message = false;

	/**
	 * Order items with inner message
	 *
	 * @var OrderItem[]
	 */
	protected $order_items_with_inner_message = [];

	/**
	 * Supported filters
	 *
	 * @var array
	 */
	protected static $filters = [ 'square-no-message', 'square-with-message' ];

	/**
	 * Get all card sizes
	 *
	 * @var array
	 */
	private $card_sizes = [];

	protected $is_ordered_from_website = false;

	protected $store_id = 0;
	protected $store_name = '';
	protected $wc_order_id = 0;

	/**
	 * Order constructor.
	 *
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->data          = $data;
		$this->store_id      = $this->data['advancedOptions']['storeId'] ?
			intval( $this->data['advancedOptions']['storeId'] ) : 0;
		$this->store_name    = ShipStationApi::get_store_name( $this->store_id );
		$items               = $this->data['items'] ?? [];
		$quantities_in_order = array_sum( wp_list_pluck( $items, 'quantity' ) );
		foreach ( $items as $item ) {
			$this->order_items[] = new OrderItem( $item, $this->get_id(), $quantities_in_order, $this->store_id );
			$sku                 = $item['sku'] ?? '';
			$product_id          = wc_get_product_id_by_sku( $sku );
			if ( $product_id ) {
				$this->products[] = wc_get_product( $product_id );
			}
		}

		$this->check_if_it_contains_inner_message();
		$this->check_if_it_ordered_from_website();
		$this->get_all_card_sizes();
	}

	/**
	 * Get array re-presentation of this class
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'orderId'                  => $this->get_id(),
			'storeId'                  => $this->get_store_id(),
			'storeName'                => $this->get_store_name(),
			'orderStatus'              => $this->get_order_status(),
			'order_date'               => $this->get_order_date(),
			'customer_full_name'       => $this->get_customer_full_name(),
			'customer_email'           => $this->get_customer_email(),
			'customer_phone'           => $this->get_customer_phone(),
			'customer_notes'           => $this->get_customer_notes(),
			'internal_notes'           => $this->get_internal_notes(),
			'shipping_address'         => $this->get_formatted_shipping_address(),
			'shipping_service'         => $this->requested_shipping_service(),
			'products'                 => $this->get_order_items(),
			'has_inner_message'        => $this->has_inner_message(),
			'contain_mixed_items'      => $this->is_contain_mixed_items(),
			'card_sizes'               => $this->get_card_sizes(),
			'contain_mixed_card_sizes' => $this->is_contain_mixed_card_sizes(),
			'custom_info'              => $this->stackonet_custom_info(),
			'door_delivery'            => $this->straight_to_door_delivery(),
		];
	}

	/**
	 * @return bool
	 */
	public function has_product(): bool {
		return count( $this->products ) > 0;
	}

	public function get_shop_name() {
		return get_option( 'blogname' );
	}

	/**
	 * Get shop address
	 *
	 * @return array
	 */
	public function get_shop_address(): array {
		if ( empty( self::$shop_address ) ) {
			self::$shop_address = array(
				'company'   => get_option( 'blogname' ),
				'address_1' => WC()->countries->get_base_address(),
				'address_2' => WC()->countries->get_base_address_2(),
				'city'      => WC()->countries->get_base_city(),
				'state'     => WC()->countries->get_base_state(),
				'postcode'  => WC()->countries->get_base_postcode(),
				'country'   => WC()->countries->get_base_country(),
			);
		}

		return self::$shop_address;
	}

	/**
	 * Unique ShipStation order key
	 *
	 * @return string
	 */
	public function get_order_key() {
		return $this->data['orderKey'];
	}

	/**
	 * @return bool
	 */
	public function has_customer_notes(): bool {
		return ! empty( $this->data['customerNotes'] );
	}

	/**
	 * Get customer notes
	 *
	 * @return mixed|null
	 */
	public function get_customer_notes() {
		return $this->has_customer_notes() ? $this->data['customerNotes'] : null;
	}

	/**
	 * Get internal notes
	 *
	 * @return bool
	 */
	public function has_internal_notes(): bool {
		return ! empty( $this->data['internalNotes'] );
	}

	/**
	 * Get internal notes
	 *
	 * @return mixed|null
	 */
	public function get_internal_notes() {
		return $this->has_internal_notes() ? $this->data['internalNotes'] : null;
	}

	/**
	 * Get formatted shop address
	 *
	 * @param string $separator
	 *
	 * @return string
	 */
	public function get_formatted_shop_address( string $separator = '<br/>' ): string {
		$shipping_address = $this->get_shop_address();

		return WC()->countries->get_formatted_address( $shipping_address, $separator );
	}

	/**
	 * Get ShipStation order ID
	 *
	 * @return int
	 */
	public function get_id(): int {
		return intval( $this->data['orderId'] );
	}

	/**
	 * Get ShipStation order status
	 *
	 * @return string
	 */
	public function get_order_status(): string {
		return $this->data['orderStatus'];
	}

	/**
	 * Get custom info
	 *
	 * @return array
	 */
	public function stackonet_custom_info(): array {
		$data = $this->data['advancedOptions']['customField2'] ?? null;
		if ( $data ) {
			$data = json_decode( $data, true );
		}

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Straight to door delivery
	 *
	 * @return string
	 */
	public function straight_to_door_delivery(): string {
		$data = $this->stackonet_custom_info();

		return $data['straight_to_door_delivery'] ?? 'No info';
	}

	/**
	 * Get customer full name
	 *
	 * @return string
	 */
	public function get_customer_full_name(): string {
		return isset( $this->data['shipTo']['name'] ) ? esc_html( $this->data['shipTo']['name'] ) : '';
	}

	/**
	 * Get customer phone number
	 *
	 * @return string
	 */
	public function get_customer_phone(): string {
		return isset( $this->data['shipTo']['phone'] ) ? esc_html( $this->data['shipTo']['phone'] ) : '';
	}

	/**
	 * Get customer email address
	 *
	 * @return string
	 */
	public function get_customer_email(): string {
		return isset( $this->data['customerEmail'] ) ? esc_html( $this->data['customerEmail'] ) : '';
	}

	/**
	 * Get customer shipping address
	 *
	 * @return array
	 */
	public function get_shipping_address(): array {
		if ( empty( $this->shipping_address ) ) {
			$shipTo = $this->data['shipTo'] ?? [];

			$this->shipping_address = [
				'last_name' => $shipTo['name'] ?? '',
				'company'   => $shipTo['company'] ?? '',
				'address_1' => $shipTo['street1'] ?? '',
				'address_2' => $shipTo['street2'] ?? '',
				'city'      => $shipTo['city'] ?? '',
				'state'     => $shipTo['state'] ?? '',
				'postcode'  => $shipTo['postalCode'] ?? '',
				'country'   => $shipTo['country'] ?? '',
			];
		}

		return $this->shipping_address;
	}

	/**
	 * Get customer formatted shipping address
	 *
	 * @param string $separator
	 *
	 * @return string
	 */
	public function get_formatted_shipping_address( string $separator = '<br/>' ): string {
		return WC()->countries->get_formatted_address( $this->get_shipping_address(), $separator );
	}

	/**
	 * Order Date
	 *
	 * @return string
	 */
	public function get_order_date(): string {
		return ! empty( $this->data['orderDate'] ) ? mysql_to_rfc3339( $this->data['orderDate'] ) : '';
	}

	/**
	 * Get formatted date
	 *
	 * @return string
	 * @throws Exception
	 */
	public function get_formatted_date(): string {
		$dateTime = new DateTime( $this->get_order_date() );

		return $dateTime->format( get_option( 'date_format' ) );
	}

	/**
	 * Get customer requested shipping service
	 *
	 * @return string
	 */
	public function requested_shipping_service(): string {
		return ! empty( $this->data['requestedShippingService'] ) ? $this->data['requestedShippingService'] : '';
	}

	/**
	 * Get order items
	 *
	 * @return OrderItem[]
	 */
	public function get_order_items(): array {
		return $this->order_items;
	}

	/**
	 * Get card sizes
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	public static function get_order_items_by_card_sizes( bool $force = false ): array {
		$transient_name = 'order_items_by_card_sizes';
		$items          = get_transient( $transient_name );
		if ( $force ) {
			$items = false;
			delete_transient( $transient_name );
		}
		if ( false === $items ) {
			$items  = [];
			$orders = static::_get_orders( [ 'force' => true ] );
			/** @var Order $order */
			foreach ( $orders['items'] as $order ) {
				$order_items = $order->get_order_items();
				foreach ( $order_items as $order_item ) {
					if ( ! $order_item->get_pdf_width() || ! $order_item->get_pdf_height() ) {
						continue;
					}
					$key = sprintf( "%sx%sx%sx%s",
						$order_item->get_pdf_width(),
						$order_item->get_pdf_height(),
						$order_item->has_inner_message() ? 'i' : 'b',
						$order_item->is_dynamic_card_type() ? 'd' : 's'
					);
					$qty = isset( $items[ $key ]['quantity'] ) ? intval( $items[ $key ]['quantity'] ) : 0;

					$items[ $key ]['store_id']      = $order_item->get_store_id();
					$items[ $key ]['width']         = $order_item->get_pdf_width();
					$items[ $key ]['height']        = $order_item->get_pdf_height();
					$items[ $key ]['inner_message'] = $order_item->has_inner_message();
					$items[ $key ]['card_type']     = $order_item->get_card_type();
					$items[ $key ]['card_size']     = $order_item->get_card_size();
					$items[ $key ]['quantity']      = $order_item->get_quantity() + $qty;

					$items[ $key ]['items'][] = [
						'shipStation_order_id' => $order->get_id(),
						'order_item_id'        => $order_item->get_order_item_id(),
						'wc_order_id'          => $order->get_wc_order_id(),
						'wc_order_item_id'     => $order_item->get_wc_order_item_id(),
						'has_inner_message'    => $order_item->has_inner_message(),
						'pdf'                  => $order_item->get_pdf_info(),
						'inner_message'        => $order_item->get_inner_message_info(),
						'quantity'             => $order_item->get_quantity(),
						'is_pdf_generated'     => ! $order_item->is_dynamic_card_type() || $order_item->is_dynamic_pdf_generated(),
					];
				}
			}

			asort( $items );
			$data = [];
			foreach ( $items as $key => $item ) {
				$data[ $key ]                = $item;
				$data[ $key ]['to_generate'] = [];
				if ( 'dynamic' == $item['card_type'] ) {
					foreach ( $item['items'] as $order_item ) {
						if ( $order_item['is_pdf_generated'] === false ) {
							$data[ $key ]['to_generate'][] = [
								'wc_order_id'      => $order_item['wc_order_id'],
								'wc_order_item_id' => $order_item['wc_order_item_id']
							];
						}
					}
				}
			}
			$items = $data;
			set_transient( $transient_name, $items, MINUTE_IN_SECONDS * 60 );
		}

		return $items;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function _get_orders( array $args = [] ): array {
		$orders = ShipStationApi::init()->get_orders( $args );
		$items  = [];
		foreach ( $orders['orders'] as $order ) {
			$items[] = new self( $order );
		}

		return [
			'total_items'  => $orders['total'] ?? 0,
			'current_page' => $orders['page'] ?? 0,
			'total_pages'  => $orders['pages'] ?? 0,
			'items'        => $items,
		];
	}

	/**
	 * @param array $ids
	 * @param array $args
	 *
	 * @return array|static[]
	 */
	public static function get_orders_by_ids( array $ids, array $args = [] ): array {
		// $args['orderIds'] = $ids;

		$items  = ShipStationApi::init()->get_orders( $args );
		$orders = [];
		if ( isset( $items['orders'] ) && is_array( $items['orders'] ) ) {
			foreach ( $items['orders'] as $order ) {
				if ( ! in_array( $order['orderId'], $ids ) ) {
					continue;
				}
				$orders[] = new self( $order );
			}
		}

		return $orders;
	}

	/**
	 * Get orders from ShipStation API
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function get_orders( array $args = [] ): array {
		$card_size     = $args['card_size'] ?? null;
		$card_size     = in_array( $card_size, [ 'a4', 'a5', 'a6', 'square' ] ) ? $card_size : 'any';
		$inner_message = $args['inner_message'] ?? null;
		$inner_message = in_array( $inner_message, [ 'yes', 'no' ] ) ? $inner_message : 'any';

		$_orders = ShipStationApi::init()->get_orders( $args );

		$orders = [];
		if ( isset( $_orders['orders'] ) && is_array( $_orders['orders'] ) ) {
			foreach ( $_orders['orders'] as $order ) {
				$_order = new self( $order );

				if ( $card_size == 'any' ) {
					$orders[] = $_order;
					continue;
				}

				if ( in_array( $card_size, $_order->get_card_sizes() ) ) {
					$orders[] = $_order;
				}
			}


			if ( 'any' != $inner_message ) {
				$new_orders = [];
				foreach ( $orders as $order ) {
					if ( Validate::checked( $inner_message ) ) {
						if ( $order->has_inner_message() ) {
							$new_orders[] = $order;
						}
					} else {
						if ( ! $order->has_inner_message() ) {
							$new_orders[] = $order;
						}
					}
				}
				$orders = $new_orders;
			}
		}

		return [
			'items'        => $orders,
			'total_items'  => $_orders['total'] ?? 0,
			'current_page' => $_orders['page'] ?? 0,
			'total_pages'  => $_orders['pages'] ?? 0,
		];
	}

	/**
	 * @param $order_id
	 *
	 * @return ArrayObject|static
	 */
	public static function get_order( $order_id ) {
		$order = ShipStationApi::init()->get_order( $order_id );

		return ! is_wp_error( $order ) ? new self( $order ) : new ArrayObject();
	}

	/**
	 * @param array $data
	 *
	 * @return array|WP_Error
	 */
	public static function mark_as_shipped( array $data ) {
		$status = ShipStationApi::init()->mark_as_shipped( $data );

		$orderId = isset( $data['orderId'] ) ? intval( $data['orderId'] ) : 0;
		$order   = ShipStationApi::init()->get_order( $orderId );
		ShipStationOrder::create_or_update_order( $order );

		return $status;
	}

	/**
	 * @return array|WP_Error
	 */
	public static function get_carriers() {
		return ShipStationApi::init()->get_carriers();
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Check if the order contains inner message
	 */
	public function check_if_it_contains_inner_message() {
		foreach ( $this->order_items as $order_item ) {
			if ( $order_item->has_inner_message() ) {
				$this->order_items_with_inner_message[] = $order_item;
				$this->has_inner_message                = true;
			}
		}
	}

	/**
	 * Check if it ordered from website
	 */
	public function check_if_it_ordered_from_website() {
		if ( isset( $this->data['orderNumber'] ) && is_numeric( $this->data['orderNumber'] ) ) {
			$order = wc_get_order( intval( $this->data['orderNumber'] ) );

			if ( $order instanceof WC_Abstract_Order &&
			     ( $order->get_total() == floatval( $this->data['orderTotal'] ) ) ) {
				$this->is_ordered_from_website = true;
				$this->wc_order_id             = intval( $this->data['orderNumber'] );
			}
		}
	}

	/**
	 * Get all card sizes
	 */
	public function get_all_card_sizes() {
		foreach ( $this->order_items as $order_item ) {
			$this->card_sizes[] = $order_item->get_card_size();
		}
	}

	/**
	 * Check if current order has inner message
	 *
	 * @return bool
	 */
	public function has_inner_message(): bool {
		return $this->has_inner_message;
	}

	/**
	 * Check if contains both items
	 *
	 * @return bool
	 */
	public function is_contain_mixed_items(): bool {
		return $this->has_inner_message() && count( $this->order_items_with_inner_message ) < count( $this->order_items );
	}

	/**
	 * @return array
	 */
	public function get_card_sizes(): array {
		return $this->card_sizes;
	}

	/**
	 * @return bool
	 */
	protected function is_contain_mixed_card_sizes(): bool {
		return count( array_unique( $this->get_card_sizes() ) ) > 1;
	}

	/**
	 * @return array
	 */
	public function get_original_data(): array {
		return $this->data;
	}

	/**
	 * @return int
	 */
	public function get_store_id(): int {
		return $this->store_id;
	}

	/**
	 * @return string
	 */
	public function get_store_name(): string {
		return $this->store_name;
	}

	/**
	 * @return int
	 */
	public function get_wc_order_id(): int {
		return $this->wc_order_id;
	}
}
