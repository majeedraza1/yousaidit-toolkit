<?php

namespace YouSaidItCards\Modules\DispatchTimer;

use Stackonet\WP\Framework\Supports\Validate;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use YouSaidItCards\REST\ApiController;

class AdminRestController extends ApiController {

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'dispatch-timer/settings', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_setting_item' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'common_holidays'  => [
				],
				'weekly_holiday'   => [
				],
				'special_holidays' => [
				],
				'cut_off_time'     => [
				],
			],
		] );
		register_rest_route( $this->namespace, 'dispatch-timer/string-to-date', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'string_to_date' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'string' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
			],
		] );
	}

	public function create_setting_item( WP_REST_Request $request ): WP_REST_Response {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$response = [];

		$common_holidays = $request->get_param( 'common_holidays' );
		if ( $common_holidays ) {
			$common_holidays = static::sanitize_common_holidays( $common_holidays );
			update_option( 'dispatch_timer_common_holidays', $common_holidays, true );

			$response['common_holidays'] = $common_holidays;
		}

		$special_holidays = $request->get_param( 'special_holidays' );
		if ( $special_holidays ) {
			$special_holidays = static::sanitize_special_holidays( $special_holidays );
			update_option( 'dispatch_timer_special_holidays', $special_holidays, true );
			$response['special_holidays'] = $special_holidays;
		}

		return $this->respondOK( $response );
	}

	public function string_to_date( WP_REST_Request $request ): WP_REST_Response {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->respondUnauthorized();
		}

		$string = trim( $request->get_param( 'string' ) );
		$string = sanitize_text_field( $string );

		$datetime = strtotime( $string );
		if ( false === $datetime ) {
			$date = 'invalid date string';
		} else {
			$date = gmdate( 'l, d-M-Y', $datetime );
		}

		return $this->respondOK(
			[
				'date'   => $date,
				'string' => $string,
			]
		);
	}

	/**
	 * Sanitized common holidays
	 *
	 * @param  mixed  $holidays  Array of holidays.
	 *
	 * @return array
	 */
	private static function sanitize_common_holidays( $holidays ): array {
		$sanitized = [];
		if ( ! is_array( $holidays ) ) {
			return $sanitized;
		}
		foreach ( $holidays as $holiday ) {
			if ( ! isset( $holiday['date_string'] ) ) {
				continue;
			}
			$datetime = strtotime( $holiday['date_string'] );
			if ( false === $datetime ) {
				continue;
			}
			$sanitized[] = [
				'label'       => $holiday['label'] ? sanitize_text_field( $holiday['label'] ) : '',
				'date_string' => sanitize_text_field( $holiday['date_string'] ),
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitized special holidays
	 *
	 * @param  mixed  $holidays  Array of holidays.
	 *
	 * @return array
	 */
	private static function sanitize_special_holidays( $holidays ): array {
		$sanitized = [];
		if ( ! is_array( $holidays ) ) {
			return $sanitized;
		}

		foreach ( $holidays as $holiday ) {
			if ( ! isset( $holiday['date'] ) ) {
				continue;
			}
			if ( ! Validate::date( $holiday['date'] ) ) {
				continue;
			}
			$datetime = \DateTime::createFromFormat( 'Y-m-d', $holiday['date'] );

			$sanitized[ $datetime->format( 'Y' ) ][] = [
				'label' => $holiday['label'],
				'date'  => $holiday['date'],
			];
		}

		return $sanitized;
	}
}
