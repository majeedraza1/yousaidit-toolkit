<?php

namespace YouSaidItCards\Modules\WooCommerce;

use WC_Customer;
use WC_Data_Exception;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Shipping_Method;
use WC_Shipping_Rate;
use WC_Shipping_Zones;
use WC_Tax;

class ShippingCalculator {

	/**
	 * @var WC_Customer
	 */
	protected $customer;

	protected $cart_subtotal = 0;

	/**
	 * @var array
	 */
	protected $shipping_address = [];

	/**
	 * @var array [['product_id'=>'','quantity'=>'','variation_id'=>'']]
	 */
	protected $line_items = [];

	/**
	 * @var array
	 */
	protected $applied_coupons = [];

	/**
	 * @var string
	 */
	protected $shipping_method_id = '';
	protected $shipping_method_instance_id = '';

	public function __set( $name, $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->{$name} = $value;
		}
	}

	/**
	 * @param string $country
	 * @param string|null $state
	 * @param string|null $postcode
	 * @param string $context
	 *
	 * @return WC_Shipping_Method[]
	 */
	public static function get_shipping_methods( string $country, ?string $state, ?string $postcode, string $context = 'admin' ): array {
		$shipping_zone = WC_Shipping_Zones::get_zone_matching_package( [
			"destination" => [ "country" => $country, "state" => $state, "postcode" => $postcode, ]
		] );
		/** @var WC_Shipping_Method[] $methods */
		$methods = $shipping_zone->get_shipping_methods( true, $context );

		return array_values( $methods );
	}

	/**
	 * Get chosen shipping method
	 *
	 * @return false|WC_Shipping_Method
	 */
	public function get_chosen_shipping_method() {
		$methods       = static::get_shipping_methods(
			$this->get_customer_prop( 'country' ),
			$this->get_customer_prop( 'state' ),
			$this->get_customer_prop( 'postcode' )
		);
		$chosen_method = isset( $methods[0] ) ? $methods[0] : false;
		foreach ( $methods as $method ) {
			if ( $method->id == $this->get_shipping_method_id() &&
			     $method->instance_id == $this->get_shipping_method_instance_id() ) {
				$chosen_method = $method;
			}
		}

		return $chosen_method;
	}

	/**
	 * Get shipping rate
	 *
	 * @param WC_Shipping_Method|null $shipping_method
	 *
	 * @return WC_Shipping_Rate
	 */
	public function get_shipping_rate( ?WC_Shipping_Method $shipping_method = null ): WC_Shipping_Rate {
		if ( ! $shipping_method instanceof WC_Shipping_Method ) {
			$shipping_method = $this->get_chosen_shipping_method();
		}
		$shipping_method->calculate_shipping( $this->get_shipping_packages() );

		return current( $shipping_method->rates );
	}

	/**
	 * @param string $shipping_method_id
	 *
	 * @return WC_Order_Item_Shipping
	 * @throws WC_Data_Exception
	 */
	public function get_order_item_shipping( string $shipping_method_id ): WC_Order_Item_Shipping {
		$chosen_method = $this->get_chosen_shipping_method( $shipping_method_id );
		$shipping_rate = $this->get_shipping_rate( $chosen_method );

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_method_title( $chosen_method->get_title() );
		$shipping_item->set_method_id( $chosen_method->id ); // set an existing Shipping method rate ID
		$shipping_item->set_shipping_rate( $shipping_rate );

		// $shipping_item->set_total( 10 ); // (optional)
		// $shipping_item->calculate_taxes( $order->get_address( 'shipping' ) );
		// $shipping_item->set_order_id( $order->get_id() );
		return $shipping_item;
	}

	/**
	 * @return array
	 */
	public function get_shipping_address(): array {
		return $this->shipping_address;
	}

	/**
	 * @param array $shipping_address
	 *
	 * @return ShippingCalculator
	 */
	public function set_shipping_address( array $shipping_address ): ShippingCalculator {
		$this->shipping_address = $shipping_address;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_line_items(): array {
		return $this->line_items;
	}

	/**
	 * @param array $line_items
	 *
	 * @return ShippingCalculator
	 */
	public function set_line_items( array $line_items ): ShippingCalculator {
		$this->line_items = $line_items;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_shipping_method_id(): string {
		return $this->shipping_method_id;
	}

	/**
	 * @param string $shipping_method_id
	 *
	 * @return ShippingCalculator
	 */
	public function set_shipping_method_id( string $shipping_method_id ): ShippingCalculator {
		$this->shipping_method_id = $shipping_method_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_shipping_method_instance_id(): string {
		return $this->shipping_method_instance_id;
	}

	/**
	 * @param string $shipping_method_instance_id
	 *
	 * @return ShippingCalculator
	 */
	public function set_shipping_method_instance_id( string $shipping_method_instance_id ): ShippingCalculator {
		$this->shipping_method_instance_id = $shipping_method_instance_id;

		return $this;
	}

	/**
	 * Given a set of packages with rates, get the chosen ones only.
	 *
	 * @param array $calculated_shipping_packages Array of packages.
	 *
	 * @return array
	 */
	protected function get_chosen_shipping_methods( $calculated_shipping_packages = array() ) {
		$chosen_methods = array();
		// Get chosen methods for each package to get our totals.
		foreach ( $calculated_shipping_packages as $key => $package ) {
			$chosen_method = wc_get_chosen_shipping_method_for_package( $key, $package );
			if ( $chosen_method ) {
				$chosen_methods[ $key ] = $package['rates'][ $chosen_method ];
			}
		}

		return $chosen_methods;
	}

	public function calculate_shipping(): array {
		return WC()->shipping()->calculate_shipping( $this->get_shipping_packages() );
	}

	public function get_shipping_packages(): array {
		return [
			'contents'        => $this->get_items_needing_shipping(),
			'contents_cost'   => array_sum( wp_list_pluck( $this->get_items_needing_shipping(), 'line_total' ) ),
			'applied_coupons' => $this->get_applied_coupons(),
			'user'            => [
				'ID' => get_current_user_id(),
			],
			'destination'     => [
				'country'   => $this->get_customer_prop( 'country' ),
				'state'     => $this->get_customer_prop( 'state' ),
				'postcode'  => $this->get_customer_prop( 'postcode' ),
				'city'      => $this->get_customer_prop( 'city' ),
				'address'   => $this->get_customer_prop( 'address' ),
				'address_1' => $this->get_customer_prop( 'address_1' ),
				// Provide both address and address_1 for backwards compatibility.
				'address_2' => $this->get_customer_prop( 'address_2' ),
			],
			'cart_subtotal'   => $this->get_displayed_subtotal(),
		];
	}

	private function get_displayed_subtotal(): int {
		return $this->cart_subtotal;
	}

	public function get_items_needing_shipping(): array {
		$customer = new WC_Customer( get_current_user_id() );
		foreach ( $this->shipping_address as $key => $shipping_address ) {
			$method = 'set_shipping_' . $key;
			if ( method_exists( $customer, $method ) ) {
				$customer->{$method}( $shipping_address );
			}
		}

		$items         = [];
		$cart_subtotal = 0;
		foreach ( $this->line_items as $line_item ) {
			$data_hash    = md5( wp_json_encode( $line_item ) );
			$product_id   = isset( $line_item['product_id'] ) ? intval( $line_item['product_id'] ) : 0;
			$variation_id = isset( $line_item['variation_id'] ) ? intval( $line_item['variation_id'] ) : 0;
			$quantity     = isset( $line_item['quantity'] ) ? intval( $line_item['quantity'] ) : 0;
			$product      = wc_get_product( $variation_id ? $variation_id : $product_id );
			$tax_class    = $product->get_tax_class();
			$price        = ( (float) $product->get_price() * $quantity );
			$taxable      = $product->get_tax_status() == 'taxable';
			$item_tax     = 0;
			if ( $taxable ) {
				$tax_rates = WC_Tax::find_rates( [
					'country'   => $this->get_customer_prop( 'country' ),
					'state'     => $this->get_customer_prop( 'state' ),
					'postcode'  => $this->get_customer_prop( 'postcode' ),
					'city'      => $this->get_customer_prop( 'city' ),
					'tax_class' => $tax_class,
				] );
				$item_tax  = WC_Tax::calc_tax( $price, $tax_rates, wc_prices_include_tax() );
			}

			if ( $product instanceof WC_Product && $product->needs_shipping() ) {
				$items[] = [
					'key'               => $data_hash,
					'data_hash'         => $data_hash,
					'product_id'        => $product_id,
					'variation_id'      => $variation_id,
					'quantity'          => $quantity,
					'line_tax_data'     => [],
					'line_subtotal'     => $price,
					'line_subtotal_tax' => 0,
					'line_total'        => 0,
					'line_tax'          => $item_tax,
					'data'              => $product,
				];
			}
			$cart_subtotal += $price;
		}

		$this->cart_subtotal = $cart_subtotal;

		return $items;
	}

	/**
	 * Gets the array of applied coupon codes.
	 *
	 * @return array of applied coupons
	 */
	public function get_applied_coupons(): array {
		return (array) $this->applied_coupons;
	}

	/**
	 * Get customer props
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get_customer_prop( string $key = '', $default = '' ): string {
		return isset( $this->shipping_address[ $key ] ) ? $this->shipping_address[ $key ] : $default;
	}
}
