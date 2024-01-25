<?php

namespace YouSaidItCards\Modules\Designers\Emails;

use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;

defined( 'ABSPATH' ) || exit;

class CommissionChangeEmail extends Mailer {
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
	 * @param DesignerCard|int $card
	 */
	public function __construct( $designer, $card ) {
		$this->designer = $designer;
		if ( $card instanceof DesignerCard ) {
			$this->card = $card;
		} elseif ( is_numeric( $card ) ) {
			$this->card = ( new DesignerCard() )->find_by_id( $card );
		}
	}

	/**
	 * Send email
	 */
	public function send_email() {
		$user      = $this->designer->get_user();
		$site_name = get_bloginfo( 'name' );

		$commission        = $this->card->get_commission_data();
		$commission_amount = $commission['commission_amount'] ?? [];

		$amount = '';
		foreach ( $commission_amount as $size => $_amount ) {
			$amount .= '<div><strong>' . $_amount . '</strong> <small>for size: ' . $size . '</small></div>';
		}

		$table_data = [
			[ 'label' => 'Commission Type', 'value' => 'Fix' ],
			[ 'label' => 'Commission Amount', 'value' => $amount ],
		];

		try {
			$this->set_intro_lines( 'You card commission has been changed. You will get commission as described below:' );
			$this->set_intro_lines( $this->all_fields_table( $table_data ) );
			$this->set_intro_lines( 'Remember, the admin can change commission amount as described on terms of service.' );

			$this->set_greeting( 'Hello ' . $user->display_name . '!' );
			$this->setReceiver( $user->user_email );
			$this->setSubject( sprintf( __( 'You card commission has been changed!', 'ap-toolkit' ), $site_name ) );
			$this->setFrom( 'support@yousaidit.co.uk', $site_name );
			$this->setReplyTo( 'support@yousaidit.co.uk', $site_name );
			$this->send();
		} catch ( Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}
}
