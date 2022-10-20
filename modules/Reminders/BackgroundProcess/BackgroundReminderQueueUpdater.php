<?php

namespace YouSaidItCards\Modules\Reminders\BackgroundProcess;

use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderQueue;

class BackgroundReminderQueueUpdater extends BackgroundProcess {


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
	protected $action = 'background_reminder_queue_updater';

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
		$reminder    = ( new Reminder )->find_single( $reminder_id );
		if ( $reminder instanceof Reminder ) {
			ReminderQueue::add_to_queue( $reminder );
		}

		return false;
	}
}
