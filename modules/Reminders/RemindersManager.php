<?php

namespace YouSaidItCards\Modules\Reminders;

use Stackonet\WP\Framework\Supports\Validate;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\Modules\Reminders\Admin\Admin;
use YouSaidItCards\Modules\Reminders\BackgroundProcess\BackgroundRecurringReminder;
use YouSaidItCards\Modules\Reminders\BackgroundProcess\BackgroundReminderEmail;
use YouSaidItCards\Modules\Reminders\BackgroundProcess\BackgroundReminderQueueUpdater;
use YouSaidItCards\Modules\Reminders\Frontend\MyAccount;
use YouSaidItCards\Modules\Reminders\Models\Reminder;
use YouSaidItCards\Modules\Reminders\Models\ReminderGroup;
use YouSaidItCards\Modules\Reminders\Models\ReminderQueue;
use YouSaidItCards\Modules\Reminders\REST\AdminReminderController;
use YouSaidItCards\Modules\Reminders\REST\AdminReminderGroupController;
use YouSaidItCards\Modules\Reminders\REST\AdminReminderQueueController;
use YouSaidItCards\Modules\Reminders\REST\CustomerReminderController;

/**
 * Class RemindersManager
 * @package YouSaidItCards\Modules\Reminders
 */
class RemindersManager {

	/**
	 * The instance of the class
	 *
	 * @var RemindersManager
	 */
	private static $instance;

	/**
	 * The instance of the class
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			Admin::init();
			AdminReminderController::init();
			AdminReminderGroupController::init();
			AdminReminderQueueController::init();
			CustomerReminderController::init();
			MyAccount::init();

			BackgroundReminderEmail::init();
			BackgroundRecurringReminder::init();
			BackgroundReminderQueueUpdater::init();

			add_filter( 'yousaidit_toolkit/settings/panels', [ self::$instance, 'add_settings_panels' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_settings_fields' ] );

			add_action( 'wp_ajax_reminder_email_template', [ self::$instance, 'email_template' ] );
			add_action( 'wp_ajax_reminder_test', [ self::$instance, 'reminder_test' ] );

			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'schedule_cron_event' ] );
			add_action( 'wp', [ self::$instance, 'schedule_cron_event' ] );
			add_action( 'yousaidit_toolkit/check_due_reminders', [ self::$instance, 'check_due_reminders' ] );
			add_action( 'yousaidit_toolkit/check_reminders_queue', [ self::$instance, 'check_reminders_queue' ] );
			add_action( 'yousaidit_toolkit/check_expired_reminders', [ self::$instance, 'check_expired_reminders' ] );
		}

		return self::$instance;
	}

	public function reminder_test() {
		$queue_ids = ReminderQueue::get_pending_queue_ids();
		foreach ( $queue_ids as $id ) {
			BackgroundReminderEmail::init()->push_to_queue( [ 'queue_id' => $id ] );
		}
		var_dump( $queue_ids );
		die;
	}

	/**
	 * Add settings panels
	 *
	 * @param array $sections The sections array
	 *
	 * @return array
	 */
	public function add_settings_panels( array $sections ): array {
		$sections[] = [
			'id'          => 'panel_reminders',
			'title'       => __( 'Reminders', 'yousaidit-toolkit' ),
			'description' => __( 'Settings for reminders', 'yousaidit-toolkit' ),
			'priority'    => 30,
		];

		return $sections;
	}

	/**
	 * Add settings fields
	 *
	 * @param array $fields The fields array
	 *
	 * @return array
	 */
	public function add_settings_fields( array $fields ): array {
		$fields[] = [
			'type'        => 'checkbox',
			'panel'       => 'panel_reminders',
			'id'          => 'disable_check_due_reminders',
			'title'       => __( 'Disable: check due reminders', 'yousaidit-toolkit' ),
			'description' => __( 'Check to disable background task for checking due reminders. This task run two times in a day.', 'yousaidit-toolkit' ),
			'priority'    => 10,
		];
		$fields[] = [
			'type'        => 'checkbox',
			'panel'       => 'panel_reminders',
			'id'          => 'disable_check_reminders_queue',
			'title'       => __( 'Disable: check reminders queue', 'yousaidit-toolkit' ),
			'description' => __( 'Check to disable background task for checking reminders queue and sending email. This task run every hour.', 'yousaidit-toolkit' ),
			'priority'    => 20,
		];
		$fields[] = [
			'type'        => 'checkbox',
			'panel'       => 'panel_reminders',
			'id'          => 'disable_check_expired_reminders',
			'title'       => __( 'Disable: check expired reminders', 'yousaidit-toolkit' ),
			'description' => __( 'Check to disable background task for checking expired reminders and update year to next year for recurring reminders. This task run two times in a day.', 'yousaidit-toolkit' ),
			'priority'    => 30,
		];

		return $fields;
	}

	/**
	 * Schedule cron event
	 */
	public function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'yousaidit_toolkit/check_due_reminders' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'yousaidit_toolkit/check_due_reminders' );
		}
		if ( ! wp_next_scheduled( 'yousaidit_toolkit/check_expired_reminders' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'yousaidit_toolkit/check_expired_reminders' );
		}
		if ( ! wp_next_scheduled( 'yousaidit_toolkit/check_reminders_queue' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'yousaidit_toolkit/check_reminders_queue' );
		}
	}

	/**
	 * Check due reminders
	 *
	 * @return void
	 */
	public function check_due_reminders() {
		$disable = SettingPage::get_option( 'disable_check_due_reminders' );
		if ( Validate::checked( $disable ) ) {
			return;
		}
		$reminders_ids = Reminder::get_due_reminders_ids();
		foreach ( $reminders_ids as $id ) {
			BackgroundReminderQueueUpdater::init()->push_to_queue( [ 'reminder_id' => $id ] );
		}
	}

	/**
	 * Check reminders queue
	 *
	 * @return void
	 */
	public function check_reminders_queue() {
		$disable = SettingPage::get_option( 'disable_check_reminders_queue' );
		if ( Validate::checked( $disable ) ) {
			return;
		}
		$queue_ids = ReminderQueue::get_pending_queue_ids();
		foreach ( $queue_ids as $id ) {
			BackgroundReminderEmail::init()->push_to_queue( [ 'queue_id' => $id ] );
		}
	}

	/**
	 * Check expired reminders
	 *
	 * @return void
	 */
	public function check_expired_reminders() {
		$disable = SettingPage::get_option( 'disable_check_expired_reminders' );
		if ( Validate::checked( $disable ) ) {
			return;
		}
		$reminders_ids = Reminder::get_expired_recurring_reminders_ids();
		foreach ( $reminders_ids as $id ) {
			BackgroundRecurringReminder::init()->push_to_queue( [ 'reminder_id' => $id ] );
		}
	}

	/**
	 * Check email template
	 */
	public function email_template() {
		$reminder_id = isset( $_GET['reminder_id'] ) ? absint( $_GET['reminder_id'] ) : 0;
		$group_id    = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

		if ( $reminder_id ) {
			$reminder       = new Reminder( $reminder_id );
			$reminder_group = $reminder->get_reminder_group();
		} else {
			$reminder       = null;
			$reminder_group = $group_id ? new ReminderGroup( $group_id ) : null;
		}

		if ( $reminder_group instanceof ReminderGroup ) {
			$email = new ReminderEmailTemplate( $reminder_group, $reminder );
			echo $email->get_content_html();
		}
		wp_die();
	}

	/**
	 * Run on plugin activation
	 */
	public static function activation() {
		ReminderGroup::create_table();
		Reminder::create_table();
		ReminderQueue::create_table();
	}
}
