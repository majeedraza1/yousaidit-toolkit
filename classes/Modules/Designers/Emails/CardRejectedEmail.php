<?php

namespace Yousaidit\Modules\Designers\Emails;

use Stackonet\WP\Framework\Supports\Logger;
use Yousaidit\Modules\Designers\Models\CardDesigner;
use Yousaidit\Modules\Designers\Models\DesignerCard;

defined( 'ABSPATH' ) || exit;

class CardRejectedEmail extends Mailer {

	/**
	 * @var CardDesigner
	 */
	private $designer;

	/**
	 * @var DesignerCard
	 */
	private $card;

	/**
	 * @var string
	 */
	private $reject_reason = '';

	/**
	 * CardRemoveRequestEmail constructor.
	 *
	 * @param CardDesigner $designer
	 * @param DesignerCard $card
	 */
	public function __construct( $designer, $card ) {
		$this->designer = $designer;
		$this->card     = $card;
	}

	/**
	 * @return string
	 */
	public function get_reject_reason() {
		return $this->reject_reason;
	}

	/**
	 * @param string $reject_reason
	 *
	 * @return self
	 */
	public function set_reject_reason( $reject_reason ) {
		$this->reject_reason = $reject_reason;

		return $this;
	}

	/**
	 * Send mail
	 */
	public function send_email() {
		$current_user = $this->designer->get_user();

		$table_data = [
			[ 'label' => 'Message from Admin', 'value' => $this->get_reject_reason() ],
		];

		try {
			$this->set_intro_lines( 'You card has been rejected. Check the message from admin.' );
			$this->set_intro_lines( $this->all_fields_table( $table_data ) );
			$this->setSubject( 'Card #' . $this->card->get( 'id' ) . ' has been rejected.' );
			$this->setFrom( get_option( 'admin_email' ), get_bloginfo( 'name' ) );
			$this->setTo( $current_user->user_email, $current_user->display_name );
			$this->send();
		} catch ( \Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}
}
