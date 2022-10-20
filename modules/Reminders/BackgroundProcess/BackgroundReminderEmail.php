<?php

namespace YouSaidItCards\Modules\Reminders\BackgroundProcess;

use Exception;
use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\Modules\Reminders\Mailer;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;
use YouSaidItCards\Modules\Reminders\Models\ReminderQueue;
use YouSaidItCards\Modules\Reminders\ReminderEmailTemplate;

class BackgroundReminderEmail extends BackgroundProcess {

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
	protected $action = 'background_reminder_email';

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
		$order_id = isset( $item['queue_id'] ) ? intval( $item['queue_id'] ) : 0;
		$item     = ( new ReminderQueue() )->find_single( $order_id );
		if ( $item instanceof ReminderQueue ) {
			$reminder = new Reminder( $item->get( 'reminder_id' ) );
			// Get instance of email template and send email
			$email_template = new ReminderEmailTemplate(
				new ReminderGroup( $item->get( 'reminder_group_id' ) ),
				$reminder
			);

			try {
				$mailer = new Mailer();
				$mailer->use_default_mail_from();
				$mailer->set_content_type( 'html' );
				$mailer->set_subject( 'A Friendly Reminder' );
				$mailer->set_message( $email_template->get_content_html() );
				$mailer->set_receiver( $reminder->get_user()->user_email, $reminder->get_user()->display_name );
				$mailer->send();

				// Mark reminder queue as sent
				$item->update( [ 'id' => $item->get( 'id' ), 'status' => ReminderQueue::STATUS_SENT ] );
			} catch ( Exception $e ) {
				$item->update( [ 'id' => $item->get( 'id' ), 'status' => ReminderQueue::STATUS_FAILED ] );
				Logger::log( $e );
			}
		}

		return false;
	}
}
