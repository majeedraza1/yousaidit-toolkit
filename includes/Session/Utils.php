<?php

namespace YouSaidItCards\Session;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

/**
 * Utility class for session utilities
 * This class should never be instantiated
 */
class Utils {

	/**
	 * @var string
	 */
	private static $table = 'sessions';


	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	public static function generate_id() {
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$hash = new \PasswordHash( 8, false );

		return md5( $hash->get_random_bytes( 32 ) );
	}

	/**
	 * Count the total sessions in the database.
	 *
	 * @return int
	 * @global \wpdb $wpdb
	 *
	 */
	public static function count_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		$sessions = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		return absint( $sessions );
	}

	/**
	 * Test whether or not a session exists
	 *
	 * @param string $session_id The session ID to retrieve
	 *
	 * @return bool
	 */
	public static function session_exists( $session_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE session_key = %s", $session_id )
		);

		return $exists > 0;
	}

	/**
	 * Get session from database.
	 *
	 * @param string $session_id The session ID to retrieve
	 * @param array $default The default value to return if the option does not exist.
	 *
	 * @return array Session data
	 */
	public static function get_session( $session_id, $default = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		$session = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_key = %s", esc_sql( $session_id ) ),
			ARRAY_A
		);

		if ( $session === null ) {
			return $default;
		}

		return unserialize( $session['session_value'] );
	}

	/**
	 * Create a new, random session in the database.
	 *
	 * @param null|string $date
	 */
	public static function create_dummy_session( $date = null ) {
		// Generate our date
		if ( null !== $date ) {
			$time = strtotime( $date );

			if ( false === $time ) {
				$date = null;
			} else {
				$expires = date( 'U', strtotime( $date ) );
			}
		}

		// If null was passed, or if the string parsing failed, fall back on a default
		if ( null === $date ) {
			/**
			 * Filter the expiration of the session in the database
			 *
			 * @param int
			 */
			$expires = time() + (int) apply_filters( 'wp_session_expiration', 30 * 60 );
		}

		$session_id = self::generate_id();

		// Store the session
		self::add_session( array(
			'session_key'    => $session_id,
			'session_value'  => array(),
			'session_expiry' => $expires
		) );
	}

	/**
	 * Add session in database.
	 *
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                    This means that if you are using GET or POST data you may need to use stripslashes() to avoid slashes ending up in the database.
	 *
	 * @return false|int false if the row could not be inserted or the number of affected rows (which will always be 1).
	 */
	public static function add_session( $data = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		if ( empty( $data ) ) {
			return false;
		}

		return $wpdb->insert( $table, $data );
	}

	/**
	 * Update session in database.
	 *
	 * @param int $session_id The session ID to update
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                    This means that if you are using GET or POST data you may need to use stripslashes() to avoid slashes ending up in the database.
	 *
	 * @return bool|int the number of rows updated, or false if there is an error.
	 *                  Keep in mind that if the $data matches what is already in the database, no rows will be updated, so 0 will be returned.
	 *                  Because of this, you should probably check the return with false === $result
	 */
	public static function update_session( $session_id = '', $data = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		if ( $session_id == '' || empty( $data ) ) {
			return false;
		}

		return $wpdb->update( $table, $data, array( 'session_key' => $session_id ) );
	}

	/**
	 * @param string $session_id
	 * @param string $value
	 * @param int $expiry
	 *
	 * @return bool
	 */
	public static function add_or_update_session( $session_id, $value, $expiry ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		$sql = "INSERT INTO `{$table}` (`session_key`, `session_value`, `session_expiry`) VALUES";
		$sql .= $wpdb->prepare( " (%s, %s, %d)", $session_id, $value, $expiry );
		$sql .= $wpdb->prepare( " ON DUPLICATE KEY UPDATE session_value=%s, session_expiry=%d", $value, $expiry );

		return (bool) $wpdb->query( $sql );
	}

	/**
	 * Delete session in database.
	 *
	 * @param string $session_id The session ID to update
	 *
	 * @return bool
	 */
	public static function delete_session( $session_id = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		if ( $session_id == '' ) {
			return false;
		}

		return (bool) $wpdb->delete( $table, array( 'session_key' => $session_id ) );
	}

	/**
	 * Delete old sessions from the database.
	 *
	 * @param int $limit Maximum number of sessions to delete.
	 *
	 * @return int Sessions deleted.
	 * @global \wpdb $wpdb
	 *
	 */
	public static function delete_old_sessions( $limit = 1000 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		$sql = $wpdb->prepare( "DELETE FROM `{$table}` WHERE session_expiry < %s LIMIT % d", time(), absint( $limit ) );

		return $wpdb->query( $sql );
	}

	/**
	 * Remove all sessions from the database, regardless of expiration.
	 *
	 * @return int Sessions deleted
	 * @global \wpdb $wpdb
	 *
	 */
	public static function delete_all_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;

		return $wpdb->query( "TRUNCATE TABLE `{$table}`" );
	}

	/**
	 * Create database for session
	 */
	public static function create_database_table() {
		global $wpdb;
		$tableName = $wpdb->prefix . self::$table;
		$collate   = $wpdb->get_charset_collate();

		$tableSchema = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
			session_id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
		  	session_key char( 32 ) NOT null,
		  	session_value LONGTEXT NOT null,
		  	session_expiry BIGINT( 20 ) UNSIGNED NOT null,
		  	PRIMARY KEY( session_key ),
		  	UNIQUE KEY session_id( session_id )
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tableSchema );
	}
}
