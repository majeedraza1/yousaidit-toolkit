<?php

namespace YouSaidItCards\Modules\Reminders;

use Exception;
use Stackonet\WP\Framework\Supports\Validate;

class Mailer {
	/**
	 * Email subject
	 *
	 * @var string
	 */
	private $subject = '';

	/**
	 * Email body
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * list of email addresses to send message.
	 *
	 * @var array
	 */
	protected $receivers = [];

	/**
	 * List of headers
	 *
	 * @var array
	 */
	private $headers = [];

	/**
	 * List of attachments file path
	 *
	 * @var array
	 */
	private $attachments = [];

	/**
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function send() {
		if ( empty( $this->receivers ) || empty( $this->subject ) || empty( $this->message ) ) {
			throw new Exception( 'Receiver address, Subject and Message are required.' );
		}

		return wp_mail(
			$this->get_receivers(),
			$this->get_subject(),
			$this->get_message(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Set email content type
	 *
	 * @param string $content_type Email content type
	 *
	 * @return void
	 */
	public function set_content_type( string $content_type = 'plain' ) {
		if ( $content_type === 'html' ) {
			$this->set_header( 'Content-Type', 'text/html; charset=UTF-8', true );
		} else {
			$this->set_header( 'Content-Type', 'text/plain; charset=UTF-8', true );
		}
	}

	/**
	 * Set reply to address
	 *
	 * @param string $email Email address.
	 * @param string|null $name Optional. Name of the recipient.
	 */
	public function set_reply_to( string $email, ?string $name = null ): void {
		if ( Validate::email( $email ) ) {
			$value = $name ? "{$name} <{$email}>" : $email;
			$this->set_header( 'Reply-To', $value, true );
		}
	}

	/**
	 * @return array
	 */
	public function get_attachments(): array {
		return $this->attachments;
	}

	/**
	 * Set sender address
	 *
	 * @param string $email Email address.
	 * @param string|null $name Optional. Name of the recipient.
	 */
	public function set_sender( string $email, ?string $name = null ): void {
		if ( Validate::email( $email ) ) {
			$value = $name ? "{$name} <{$email}>" : $email;
			$this->set_header( 'From', $value, true );
		}
	}

	/**
	 * Set CC (carbon copy) address
	 *
	 * @param string $email Email address.
	 * @param string|null $name Optional. Name of the recipient.
	 */
	public function set_cc_receiver( string $email, ?string $name = null ): void {
		if ( Validate::email( $email ) ) {
			$value = $name ? "{$name} <{$email}>" : $email;
			$this->set_header( 'Cc', $value );
		}
	}

	/**
	 * Set BCC (blind carbon copy) address
	 *
	 * @param string $email Email address.
	 * @param string|null $name Optional. Name of the recipient.
	 */
	public function set_bcc_receiver( string $email, ?string $name = null ): void {
		if ( Validate::email( $email ) ) {
			$value = $name ? "{$name} <{$email}>" : $email;
			$this->set_header( 'Bcc', $value );
		}
	}

	/**
	 * Set attachment file path
	 *
	 * @param string $file_path
	 */
	public function set_attachment( string $file_path ): void {
		if ( file_exists( $file_path ) ) {
			$this->attachments[] = $file_path;
		}
	}

	/**
	 * Converts a number of special characters into their HTML entities.
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function encode_specialchars( string $string ): string {
		return _wp_specialchars( $string, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Converts a number of HTML entities into their special characters.
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function decode_specialchars( string $string ): string {
		return wp_specialchars_decode( $string, ENT_QUOTES );
	}

	/**
	 * Set email receivers address
	 *
	 * @param string $address Email address.
	 * @param string|null $name Optional. Name.
	 *
	 * @return void
	 */
	public function set_receiver( string $address, ?string $name = null ) {
		if ( Validate::email( $address ) ) {
			$this->receivers[] = $name ? "{$name} <{$address}>" : $address;
		}
	}

	/**
	 * Get email receivers address
	 *
	 * @return array
	 */
	public function get_receivers(): array {
		return $this->receivers;
	}

	/**
	 * @return string
	 */
	public function get_subject(): string {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 */
	public function set_subject( string $subject ): void {
		$this->subject = $subject;
	}

	/**
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function set_message( string $message ): void {
		$this->message = $message;
	}

	/**
	 * @return array
	 */
	public function get_headers(): array {
		return $this->headers;
	}

	/**
	 * Set header
	 *
	 * @param string $name Header name.
	 * @param string $value Header value.
	 */
	public function set_header( string $name, string $value, bool $unique = false ): void {
		if ( $unique ) {
			$this->remove_header( $name );
		}
		$this->headers[] = sprintf( '%s: %s', $name, $value );
	}

	/**
	 * Remove header
	 *
	 * @param string $name Header name.
	 *
	 * @return void
	 */
	public function remove_header( string $name ) {
		foreach ( $this->headers as $header_index => $header ) {
			if ( strpos( $header, "$name: " ) !== false ) {
				unset( $this->headers[ $header_index ] );
			}
		}
	}

	/**
	 * Use default mail from address
	 *
	 * @return void
	 */
	public function use_default_mail_from() {
		$from_name = get_option( 'blogname' );
		$site_name = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $site_name, 0, 4 ) == 'www.' ) {
			$site_name = substr( $site_name, 4 );
		}

		$from_address = 'no-reply@' . $site_name;
		$this->set_sender( $from_address, $from_name );
	}
}
