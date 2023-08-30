<?php

namespace YouSaidItCards\Modules\DispatchTimer;

class DispatchTimerManager {
	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
			add_action( 'wp_ajax_yousaidit_dispatch_timer_test', [ self::$instance, 'dispatch_timer_test' ] );
		}

		return self::$instance;
	}

	public function dispatch_timer_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ) );
		}

		$datetime = new \DateTime( 'now', wp_timezone() );

		var_dump( [
			'next_time' => Settings::get_next_dispatch_datetime(),
			'date'      => $datetime->format( 'Y-m-d H:i:s' ),
			'holidays'  => Settings::get_holidays_for_date( $datetime->format( 'Y-m-d' ) ),
		] );
		wp_die();
	}
}