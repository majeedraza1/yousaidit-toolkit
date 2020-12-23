<?php

namespace YouSaidItCards;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_ajax_yousaidit_test', [ self::$instance, 'stackonet_test' ] );
		}

		return self::$instance;
	}

	/**
	 * A AJAX method just to test some data
	 */
	public function stackonet_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ) );
		}

		$shipping_zone = \WC_Shipping_Zones::get_zone_matching_package( [
			"destination" => [
				"country"  => "GB",
				"state"    => "",
				"postcode" => "CA2 5JL",
			]
		] );


		/** @var \WC_Shipping_Method[] $methods */
		$methods = $shipping_zone->get_shipping_methods( true );

		$first_method = current( $methods );
		$class_cost   = $first_method->evaluate_cost(
			$first_method->cost,
			array(
				'qty'  => 1,
				'cost' => 20.50,
			)
		);

		$shipping_rate = new \WC_Shipping_Rate();
		$shipping_rate->set_id( $first_method->get_rate_id() );
		$shipping_rate->set_label( $first_method->title );
		$shipping_rate->set_cost( $first_method->cost );
		$shipping_rate->set_instance_id( $first_method->get_instance_id() );
		$shipping_rate->set_method_id( $first_method->id );


		var_dump( [
			'$class_cost'   => $class_cost,
			'shipping_rate' => $shipping_rate,
//			'methods' => $first_method,
		] );
		die();
	}
}
