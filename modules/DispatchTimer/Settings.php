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

	/**
	 * Get weekly holiday
	 *
	 * @return string[]
	 */
	public static function get_weekly_holiday(): array {
		return [ 'Saturday', 'Sunday' ];
	}

	/**
	 * Get common public holiday
	 *
	 * @return array[]
	 */
	public static function get_common_public_holidays(): array {
		return [
			[ 'label' => 'New Year\'s Day', 'date_string' => 'January 1st' ],
			[ 'label' => 'May Day Bank Holiday', 'date_string' => 'first Monday of May' ],
			[ 'label' => 'Spring Bank Holiday', 'date_string' => 'last Monday of May' ],
			[ 'label' => 'Summer Bank Holiday', 'date_string' => 'last Monday of August' ],
			[ 'label' => 'Christmas Day', 'date_string' => 'December 25th' ],
			[ 'label' => 'Boxing Day', 'date_string' => 'December 26th' ],
		];
	}

	/**
	 * Get special holiday group by year
	 *
	 * @return array[]
	 */
	public static function get_special_holidays(): array {
		return [
			'2023' => [
				[ 'label' => 'Boxing Day', 'date' => '2023-08-31' ],
				[ 'label' => 'Boxing Day', 'date' => '2023-12-26' ],
				[ 'label' => 'Christmas Day', 'date' => '2023-12-25' ],
			],
			'2024' => [
				[ 'label' => 'Christmas Day', 'date' => '2024-12-25' ],
			],
		];
	}

	/**
	 * Get cut off time
	 *
	 * @return string
	 */
	public static function get_cut_off_time(): string {
		return '16:00';
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
	 * @param  bool  $force
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_holidays_for_date(
		?string $start_date = null,
		int $num_of_days = 30,
		bool $force = false
	): array {
		$num_of_days = min( 90, max( 7, $num_of_days ) );
		if ( empty( $start_date ) ) {
			$start_datetime = new DateTime( 'now', wp_timezone() );
		} else {
			$start_datetime = DateTime::createFromFormat( 'Y-m-d', $start_date, wp_timezone() );
		}
		$cache_key      = sprintf( 'holidays_for_date_%s_%s', $start_datetime->format( 'Y_m_d' ), $num_of_days );
		$final_holidays = get_transient( $cache_key );
		if ( ! is_array( $final_holidays ) || $force ) {
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

			set_transient( $cache_key, $final_holidays, DAY_IN_SECONDS );
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
}
