<?php

namespace Yousaidit\Modules\Designers\Emails;

use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use Yousaidit\Modules\Designers\Models\CardDesigner;
use Yousaidit\Modules\Designers\Models\DesignerCard;

defined( 'ABSPATH' ) || exit;

class CardAcceptedEmail extends Mailer {

	/**
	 * @var CardDesigner
	 */
	private $designer;

	/**
	 * @var DesignerCard
	 */
	private $card;

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
	 * Send email
	 */
	public function send_email() {
		$user      = $this->designer->get_user();
		$site_name = get_bloginfo( 'name' );

		$commission        = $this->card->get_commission_data();
		$commission_amount = isset( $commission['commission_amount'] ) ? $commission['commission_amount'] : [];

		$amount = '';
		foreach ( $commission_amount as $size => $_amount ) {
			$amount .= '<div><strong>' . $_amount . '</strong> <small>for size: ' . $size . '</small></div>';
		}

		$table_data = [
			[ 'label' => 'Commission Type', 'value' => 'Fix' ],
			[ 'label' => 'Commission Amount', 'value' => $amount ],
		];

		try {
			$this->set_intro_lines( 'You card has been approved. You will get commission as described below:' );
			$this->set_intro_lines( $this->all_fields_table( $table_data ) );
			$this->set_intro_lines( 'Remember, the admin can change commission amount as described on terms of service.' );

			$this->set_greeting( 'Congratulation ' . $user->display_name . '!' );
			$this->setReceiver( $user->user_email );
			$this->setSubject( sprintf( __( 'You card has been approved!', 'ap-toolkit' ), $site_name ) );
			$this->setFrom( 'support@yousaidit.co.uk', $site_name );
			$this->setReplyTo( 'support@yousaidit.co.uk', $site_name );
			$this->send();
		} catch ( Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}
}
