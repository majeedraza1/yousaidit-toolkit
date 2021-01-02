<?php

namespace YouSaidItCards\Modules\Designers\Emails;

use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use WP_User;

defined( 'ABSPATH' ) || exit;

class NewDesignerEmail extends Mailer {

	/**
	 * User Id
	 *
	 * @var int
	 */
	private $user_id = 0;

	/**
	 * @var WP_User
	 */
	protected $user;

	public function __construct( $user_id ) {
		$this->user_id = $user_id;
		$this->user    = get_user_by( 'id', $user_id );
	}

	/**
	 * Send mail to new user
	 */
	public function send_mail() {
		$user      = $this->user;
		$site_name = get_bloginfo( 'name' );

		$args = add_query_arg( [
			'action' => 'rp',
			'key'    => get_password_reset_key( $user ),
			'login'  => $user->user_email,
		], site_url( 'wp-login.php' ) );

		ob_start();
		$intro_lines = array(
			sprintf( __( 'Welcome to %s! Thank you so much for joining us. Your account username and the email you provided us are listed below:' ), get_bloginfo( 'name' ) ),
			sprintf( __( '%1$sUsername:%2$s %4$s%3$s%1$sEmail:%2$s %5$s' ),
				'<strong>', '</strong>', '<br>',
				$user->user_login, $user->user_email ),
			__( 'To set your password, please click on the following button:' ),
		);
		$this->set_intro_lines( $intro_lines );
		$this->set_action( 'Set Password', esc_url( $args ) );
		$this->set_greeting( $user->display_name . '!' );

		try {
			$this->setReceiver( $user->user_email );
			$this->setSubject( sprintf( __( 'Welcome to %s', 'ap-toolkit' ), $site_name ) );
			$this->setFrom( 'support@yousaidit.co.uk', $site_name );
			$this->setReplyTo( 'support@yousaidit.co.uk', $site_name );
			$this->send();
		} catch ( Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}
}
