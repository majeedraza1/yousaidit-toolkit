<?php

namespace YouSaidItCards\Modules\Auth;

use Stackonet\WP\Framework\Emails\ActionEmailTemplate;
use Stackonet\WP\Framework\Emails\Mailer;
use Stackonet\WP\Framework\Supports\Logger;
use WP_User;

class RegistrationConfirmEmail {
	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * RegistrationConfirmEmail constructor.
	 *
	 * @param int|string|WP_User $user
	 */
	public function __construct( $user = null ) {
		$this->user = new WP_User( $user );
	}

	public function send() {
		$code = wp_rand( 111111, 999999 );
		$now  = time();
		$data = [
			'code'       => $code,
			'not_before' => date( 'Y-m-d H:i:s', $now ),
			'not_after'  => date( 'Y-m-d H:i:s', $now ),
		];
		update_user_meta( $this->user->ID, '_registration_verification_code', $data );

		try {
			$mailer = new Mailer();
			$mailer->setReceiver( $this->user->user_email, $this->user->display_name );
			$mailer->setSubject( 'Registration Verification Code.' );
			$mailer->setMessage( static::get_content_html( $code ) );
			$mailer->isHTML( true );
			$mailer->send();
		} catch ( \Exception $e ) {
			Logger::log( $e );
		}
	}

	/**
	 * @param mixed $code
	 *
	 * @return string
	 */
	public static function get_content_html( $code ): string {
		$mailer = new ActionEmailTemplate();
		$mailer->set_box_mode( false );
		$mailer->set_intro_lines( $code . ' is your verification code to verify your registration.' );
		$mailer->set_intro_lines( 'If you did not request for registration, no further action is required.' );

		return $mailer->get_content_html();
	}
}
