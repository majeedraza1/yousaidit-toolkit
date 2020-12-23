<?php

namespace YouSaidItCards\Session;

// If this file is called directly, abort.

defined( 'ABSPATH' ) || die;

class SessionManager {

	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Plugin activation
			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'create_database' ] );
			add_action( 'yousaidit_toolkit/activation', [ self::$instance, 'schedule_event' ] );

			// Plugin deactivation
			add_action( 'yousaidit_toolkit/deactivation', [ self::$instance, 'clear_scheduled' ] );

			add_action( 'plugins_loaded', [ self::$instance, 'start_session' ] );
			add_action( 'shutdown', [ self::$instance, 'write_data' ] );
			add_action( 'wp', [ self::$instance, 'schedule_event' ] );
			add_action( 'wp_session_garbage_collection', [ self::$instance, 'delete_old_sessions' ] );
		}

		return self::$instance;
	}

	/**
	 * Start session on plugin load
	 */
	public static function start_session() {
		Session::get_instance()->session_started();
	}

	/**
	 * Write session data and end session
	 */
	public static function write_data() {
		Session::get_instance()->write_data();
	}

	/**
	 * Register the garbage collector as a twice daily event.
	 */
	public static function schedule_event() {
		if ( ! wp_next_scheduled( 'wp_session_garbage_collection' ) ) {
			wp_schedule_event( time(), 'hourly', 'wp_session_garbage_collection' );
		}
	}

	/**
	 * clean the scheduler on deactivation
	 */
	public static function clear_scheduled() {
		wp_clear_scheduled_hook( 'wp_session_garbage_collection' );
	}

	/**
	 * Delete a batch of old sessions
	 */
	public static function delete_old_sessions() {
		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return;
		}

		if ( ! defined( 'WP_INSTALLING' ) ) {
			// Delete a batch of old sessions
			Utils::delete_old_sessions();
		}
	}

	/**
	 * Create database for session
	 */
	public static function create_database() {
		Utils::create_database_table();
	}
}
