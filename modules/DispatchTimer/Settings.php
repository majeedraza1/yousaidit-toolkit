<?php

namespace YouSaidItCards\Modules\DispatchTimer;

use DateTime;
use Exception;

/**
 * Settings class
 */
class Settings {
	const DAYS_OF_WEEK = [
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		7 => 'Sunday'
	];

	const COMMON_HOLIDAYS = [
		[ 'label' => 'New Year\'s Day', 'date_string' => 'January 1st' ],
		[ 'label' => 'May Day Bank Holiday', 'date_string' => 'first Monday of May' ],
		[ 'label' => 'Spring Bank Holiday', 'date_string' => 'last Monday of May' ],
		[ 'label' => 'Summer Bank Holiday', 'date_string' => 'last Monday of August' ],
		[ 'label' => 'Christmas Day', 'date_string' => 'December 25th' ],
		[ 'label' => 'Boxing Day', 'date_string' => 'December 26th' ],
	];


	/**
	 * Get options
	 *
	 * @return array
	 */
	public static function get_options(): array {
		$defaults = [
			'dispatch_timer_weekly_holiday'   => [ 6, 7 ],
			'dispatch_timer_get_cut_off_time' => '14:00',
		];
		$options  = get_option( '_stackonet_toolkit' );
		$options  = is_array( $options ) ? $options : [];

		return wp_parse_args( $options, $defaults );
	}

	/**
	 * Get option
	 *
	 * @param  string  $key  Option key.
	 * @param  mixed  $default  Default value.
	 *
	 * @return mixed|null
	 */
	public static function get_option( string $key, $default = null ) {
		$options = static::get_options();

		return $options[ $key ] ?? $default;
	}

	/**
	 * Get weekly holiday
	 *
	 * @return string[]
	 */
	public static function get_weekly_holiday(): array {
		$option = static::get_option( 'dispatch_timer_weekly_holiday', [ 6, 7 ] );
		$day    = [];
		foreach ( $option as $day_id ) {
			$day[] = static::DAYS_OF_WEEK[ $day_id ] ?? '';
		}

		return $day;
	}

	/**
	 * Get common public holiday
	 *
	 * @return array[]
	 */
	public static function get_common_public_holidays(): array {
		$holidays = get_option( 'dispatch_timer_common_holidays', static::COMMON_HOLIDAYS );

		return is_array( $holidays ) ? $holidays : [];
	}

	/**
	 * Get special holiday group by year
	 *
	 * @return array[]
	 */
	public static function get_special_holidays(): array {
		$holidays = get_option( 'dispatch_timer_special_holidays', [] );

		return is_array( $holidays ) ? $holidays : [];
	}

	/**
	 * Get cut off time
	 *
	 * @return string
	 */
	public static function get_cut_off_time(): string {
		return static::get_option( 'dispatch_timer_get_cut_off_time', '14:00' );
	}

	/**
	 * Get weekly holiday dates
	 *
	 * @param  string|null  $start_date  Start date with format YYYY-MM-DD
	 * @param  int  $num_of_days  Total number of day to check.
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_weekly_holiday_dates( ?string $start_date = null, int $num_of_days = 30 ): array {
		if ( empty( $start_date ) ) {
			$datetime = new DateTime( 'now', wp_timezone() );
		} else {
			$datetime = DateTime::createFromFormat( 'Y-m-d', $start_date, wp_timezone() );
		}

		$holidays = static::get_weekly_holiday();
		// Create an array to store the Saturdays and Sundays
		$weekends = [];

		// Loop through all the days of the year
		foreach ( range( 1, $num_of_days ) as $ignored ) {

			// Check if the current day is a Saturday or Sunday
			if ( in_array( $datetime->format( "l" ), $holidays, true ) ) {
				$weekends[] = $datetime->format( "Y-m-d" );
			}

			// Move to the next day
			$datetime->modify( "+1 day" );
		}

		return $weekends;
	}

	/**
	 * Get common holiday for a year
	 *
	 * @param  int  $year  The year.
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_common_holiday_for_year( int $year = 0 ): array {
		$datetime = new DateTime( 'now', wp_timezone() );
		$datetime->modify( '1st day of the year' );
		if ( $year && strlen( strval( $year ) ) === 4 ) {
			$datetime->setDate( $year, $datetime->format( 'm' ), $datetime->format( 'd' ) );
		}

		$common_holidays = [];
		foreach ( Settings::get_common_public_holidays() as $holiday ) {
			$_datetime = clone $datetime;
			$_datetime->modify( $holiday['date_string'] );
			$common_holidays[] = $_datetime->format( 'Y-m-d' );
		}

		return $common_holidays;
	}

	/**
	 * Get special holidays
	 *
	 * @return array
	 */
	public static function get_special_holidays_dates(): array {
		$special_holidays = static::get_special_holidays();
		$holidays         = [];
		foreach ( $special_holidays as $special_holiday ) {
			foreach ( $special_holiday as $holiday ) {
				$holidays[] = $holiday['date'];
			}
		}
		if ( count( $holidays ) > 1 ) {
			asort( $holidays );
		}

		return $holidays;
	}

	/**
	 * Get holidays from a date
	 *
	 * @param  string|null  $start_date
	 * @param  int  $num_of_days
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_holidays_for_date( ?string $start_date = null, int $num_of_days = 30 ): array {
		$num_of_days = min( 90, max( 7, $num_of_days ) );
		if ( empty( $start_date ) ) {
			$start_datetime = new DateTime( 'now', wp_timezone() );
		} else {
			$start_datetime = DateTime::createFromFormat( 'Y-m-d', $start_date, wp_timezone() );
		}
		$max_datetime = clone $start_datetime;
		$max_datetime->modify( sprintf( '+ %s days', $num_of_days ) );
		$year = (int) $start_datetime->format( 'Y' );

		$special_holidays = static::get_special_holidays_dates();
		$weekly_holiday   = [];
		$common_holidays  = [];

		try {
			$common_holidays = static::get_common_holiday_for_year( $year );
			if ( in_array( $start_datetime->format( 'm' ), [ 12, '12' ], true ) ) {
				$common_holidays = array_merge(
					$common_holidays,
					static::get_common_holiday_for_year( $year + 1 )
				);
			}
		} catch ( Exception $e ) {
		}

		try {
			$weekly_holiday = static::get_weekly_holiday_dates( $start_date, $num_of_days );
		} catch ( Exception $e ) {
		}

		$holidays = array_merge( $common_holidays, $weekly_holiday, $special_holidays );

		if ( count( $holidays ) ) {
			$holidays = array_unique( $holidays );
			asort( $holidays );
			$holidays = array_values( $holidays );
		}

		$final_holidays = [];
		foreach ( $holidays as $holiday ) {
			$_datetime = DateTime::createFromFormat( 'Y-m-d', $holiday, wp_timezone() );
			if ( $_datetime < $start_datetime || $_datetime > $max_datetime ) {
				continue;
			}
			$final_holidays[] = $holiday;
		}

		return $final_holidays;
	}

	/**
	 * Get next dispatch datetime
	 *
	 * @param  DateTime|null  $datetime
	 *
	 * @return DateTime|null
	 * @throws Exception
	 */
	public static function get_next_dispatch_datetime( ?DateTime $datetime = null ): ?DateTime {
		if ( ! $datetime instanceof DateTime ) {
			$datetime = new DateTime( 'now', wp_timezone() );
		}
		$date         = $datetime->format( 'Y-m-d' );
		$holidays     = static::get_holidays_for_date( $date );
		$cut_off_time = static::get_cut_off_time();
		list( $cutoff_hour, $cutoff_minute ) = array_map( 'intval', explode( ":", $cut_off_time ) );

		$dispatch_time = clone $datetime;
		$dispatch_time->setTime( $cutoff_hour, $cutoff_minute );

		// If we can dispatch today.
		if ( ! in_array( $date, $holidays, true ) && $dispatch_time > $datetime ) {
			return $dispatch_time;
		}

		// Find next dispatch day
		$dispatch_time->modify( '+1 day' );

		while ( in_array( $dispatch_time->format( 'Y-m-d' ), $holidays, true ) ) {
			$dispatch_time->modify( '+1 day' );
		}

		return $dispatch_time;
	}

	/**
	 * Get next dispatch timer message
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_next_dispatch_timer_message(): string {
		$datetime      = new \DateTime( 'now', wp_timezone() );
		$next_dispatch = Settings::get_next_dispatch_datetime( $datetime );
		if ( $datetime->format( 'Y-m-d' ) === $next_dispatch->format( 'Y-m-d' ) ) {
			$dif     = $next_dispatch->diff( $datetime );
			$message = sprintf( 'Order within <strong>%s hrs %s mins</strong> for same day dispatch', $dif->h,
				$dif->i );
		} else {
			$message = sprintf( 'Order <strong>today</strong> and it will be dispatched on %s',
				$next_dispatch->format( 'l' ) );
		}

		$html = '<div class="dispatch-timer mb-4">';
		$html .= '<span class="dispatch-timer__icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
					<path d="M0 0h24v24H0V0z" fill="none"/>
					<path d="M12.5 8H11v6l4.75 2.85.75-1.23-4-2.37zm4.837-6.19l4.607 3.845-1.28 1.535-4.61-3.843zm-10.674 0l1.282 1.536L3.337 7.19l-1.28-1.536zM12 4c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z"/>
				</svg></span>';
		$html .= '<span>' . $message . '</span>';
		$html .= '</div>';

		return $html;
	}
}
