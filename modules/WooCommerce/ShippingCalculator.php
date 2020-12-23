<?php

namespace YouSaidItCards\Modules\WooCommerce;

use WC_Customer;
use WC_Product;
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

	public function __set( $name, $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->{$name} = $value;
		}
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

	public function get_shipping_packages() {
		return [
			[
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
			],
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
