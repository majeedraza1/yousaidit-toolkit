<?php

namespace YouSaidItCards\Modules\Reminders\BackgroundProcess;

use DateTime;
use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use YouSaidItCards\Modules\Reminders\Models\Reminder;

class BackgroundRecurringReminder extends BackgroundProcess {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_recurring_reminder';

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'shutdown', [ self::$instance, 'save_and_dispatch' ] );
		}

		return self::$instance;
	}

	/**
	 * Save and dispatch
	 */
	public function save_and_dispatch() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}

	protected function task( $item ) {
		$reminder_id = isset( $item['reminder_id'] ) ? intval( $item['reminder_id'] ) : 0;
		$reminder    = ( new Reminder() )->find_single( $reminder_id );
		if ( $reminder instanceof Reminder ) {
			$occasion_date = $reminder->get( 'occasion_date' );
			$dateTime1     = DateTime::createFromFormat( 'Y-m-d', $occasion_date );
			$dateTime1->modify( '+ 1 year' );

			$remind_date = $reminder->get( 'remind_date' );
			$dateTime2   = DateTime::createFromFormat( 'Y-m-d', $remind_date );
			$dateTime2->modify( '+ 1 year' );

			$reminder->update( [
				'id'            => $reminder->get( 'id' ),
				'occasion_date' => $dateTime1->format( 'Y-m-d' ),
				'remind_date'   => $dateTime2->format( 'Y-m-d' ),
				'is_in_queue'   => 0,
			] );
		}

		return false;
	}
}
