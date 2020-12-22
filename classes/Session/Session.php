<?php

namespace Yousaidit\Session;

use Stackonet\WP\Framework\Supports\Collection;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

class Session extends Collection {

	/**
	 * ID of the current session.
	 *
	 * @var string
	 */
	public $session_id;

	/**
	 * Unix timestamp when session expires.
	 *
	 * @var int
	 */
	protected $expires = 0;

	/**
	 * Unix timestamp indicating when the expiration time needs to be reset.
	 *
	 * @var int
	 */
	protected $exp_variant;

	/**
	 * Singleton instance.
	 *
	 * @var bool|Session
	 */
	private static $instance = null;

	/**
	 * Session cookie name
	 *
	 * @var string
	 */
	private $cookie_name = '_yousaidit_session';

	/**
	 * Retrieve the current session instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			/**
			 * Initialize the session object and wire up any storage.
			 *
			 * Some operations (like database migration) need to be performed
			 * before the session is able to actually be populated with data.
			 * Ensure these operations are finished by wiring them to the
			 * session object's initialization hool.
			 */
			do_action( 'wp_session_init' );
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default constructor.
	 * Will rebuild the session collection from the given session ID if it exists. Otherwise, will
	 * create a new session with that ID.
	 *
	 * @param array $data
	 */
	protected function __construct( $data = [] ) {

		foreach ( $data as $key => $value ) {
			$this->collections[ $key ] = $value;
		}

		if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
			define( 'WP_SESSION_COOKIE', $this->cookie_name );
		}

		if ( isset( $_COOKIE[ WP_SESSION_COOKIE ] ) ) {
			$cookie        = stripslashes( $_COOKIE[ WP_SESSION_COOKIE ] );
			$cookie_crumbs = explode( '||', $cookie );

			$this->session_id  = preg_replace( "/[^A-Za-z0-9_]/", '', $cookie_crumbs[0] );
			$this->expires     = absint( $cookie_crumbs[1] );
			$this->exp_variant = absint( $cookie_crumbs[2] );

			// Update the session expiration if we're past the variant time
			if ( time() > $this->exp_variant ) {
				$this->set_expiration();
				Utils::update_session( $this->session_id, array( 'session_expiry' => $this->expires ) );
			}
		} else {
			$this->session_id = Utils::generate_id();
			$this->set_expiration();
		}

		$this->read_data();

		$this->set_cookie();
	}

	/**
	 * Set both the expiration time and the expiration variant.
	 *
	 * If the current time is below the variant, we don't update the session's expiration time. If it's
	 * greater than the variant, then we update the expiration time in the database.  This prevents
	 * writing to the database on every page load for active sessions and only updates the expiration
	 * time if we're nearing when the session actually expires.
	 *
	 * By default, the expiration time is set to 30 minutes.
	 * By default, the expiration variant is set to 24 minutes.
	 *
	 * As a result, the session expiration time - at a maximum - will only be written to the database once
	 * every 24 minutes.  After 30 minutes, the session will have been expired. No cookie will be sent by
	 * the browser, and the old session will be queued for deletion by the garbage collector.
	 *
	 * @uses apply_filters Calls `wp_session_expiration_variant` to get the max update window for session data.
	 * @uses apply_filters Calls `wp_session_expiration` to get the standard expiration time for sessions.
	 */
	protected function set_expiration() {
		$this->exp_variant = time() + (int) apply_filters( 'wp_session_expiration_variant', ( MINUTE_IN_SECONDS * 15 ) );
		$this->expires     = time() + (int) apply_filters( 'wp_session_expiration', ( MINUTE_IN_SECONDS * 10 ) );
	}

	/**
	 * Set the session cookie
	 * @uses apply_filters Calls `wp_session_cookie_secure` to set the $secure parameter of setcookie()
	 * @uses apply_filters Calls `wp_session_cookie_httponly` to set the $httponly parameter of setcookie()
	 */
	protected function set_cookie() {
		$secure   = apply_filters( 'wp_session_cookie_secure', false );
		$httponly = apply_filters( 'wp_session_cookie_httponly', false );
		setcookie( WP_SESSION_COOKIE,
			$this->session_id . '||' . $this->expires . '||' . $this->exp_variant,
			$this->expires,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure,
			$httponly
		);
	}

	/**
	 * Read data from a transient for the current session.
	 *
	 * Automatically resets the expiration time for the session transient to some time in the future.
	 *
	 * @return array
	 */
	protected function read_data() {
		$this->collections = Utils::get_session( $this->session_id, array() );

		return $this->collections;
	}

	/**
	 * Write the data from the current session to the data storage system.
	 */
	public function write_data() {
		// Nothing has changed, don't update the session
		if ( ! $this->dirty ) {
			return false;
		}

		// Session is dirty, but also empty. Purge it!
		if ( empty( $this->collections ) ) {
			Utils::delete_session( $this->session_id );

			return false;
		}

		// Session is dirty and needs to be updated, do so!
		return Utils::add_or_update_session( $this->session_id, serialize( $this->collections ), $this->expires );
	}

	/**
	 * Regenerate the current session's ID.
	 *
	 * @param bool $delete_old Flag whether or not to delete the old session data from the server.
	 */
	public function regenerate_id( $delete_old = false ) {
		if ( $delete_old ) {
			Utils::delete_session( $this->session_id );
		}

		$this->session_id = Utils::generate_id();

		$this->set_cookie();
	}

	/**
	 * Check if a session has been initialized.
	 *
	 * @return bool
	 */
	public function session_started() {
		return ! ! self::$instance;
	}

	/**
	 * Return the read-only cache expiration value.
	 *
	 * @return int
	 */
	public function cache_expiration() {
		return $this->expires;
	}

	/**
	 * Allow deep copies of objects
	 */
	public function __clone() {
		foreach ( $this->collections as $key => $value ) {
			if ( $value instanceof self ) {
				$this[ $key ] = clone $value;
			} else {
				$this[ $key ] = $value;
			}
		}
	}

	/**
	 * Offset to set
	 *
	 * @param mixed $key The offset to assign the value to.
	 * @param mixed $value The value to set.
	 *
	 * @return void
	 */
	public function add( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * @inheritDoc
	 */
	public function set( $key, $value ) {
		if ( is_array( $value ) ) {
			$value = new self( $value );
		}

		parent::set( $key, $value );
	}

	/**
	 * Output the data container as a multidimensional array.
	 *
	 * @return array
	 */
	public function to_array() {
		$data = $this->collections;
		foreach ( $data as $key => $value ) {
			if ( $value instanceof self ) {
				$data[ $key ] = $value->to_array();
			} else {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}
}
