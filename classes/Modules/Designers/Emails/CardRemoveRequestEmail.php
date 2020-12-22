<?php

namespace Yousaidit\Modules\Designers\Emails;

use Stackonet\WP\Framework\Supports\Logger;
use Yousaidit\Modules\Designers\Models\CardDesigner;
use Yousaidit\Modules\Designers\Models\DesignerCard;

defined( 'ABSPATH' ) || exit;

class CardRemoveRequestEmail extends Mailer {
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
	private $designer_message = '';

	/**
	 * @var string
	 */
	private $request_for = '';

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
	 *
	 */
	public function send_email() {
		$current_user = $this->designer->get_user();

		$table_data = [
			[ 'label' => 'Request for', 'value' => $this->get_request_for() ],
			[ 'label' => 'Designer ID', 'value' => $this->designer->get_user_id() ],
		];

		$table_data[] = [
			'label' => 'Card Id',
			'value' => '<a href="' . $this->card->get_card_edit_url() . '">' . $this->card->get_id() . '</a>'
		];

		if ( $this->card->get_product_id() ) {
			$table_data[] = [
				'label' => 'Product Id',
				'value' => '<a href="' . $this->card->get_product_edit_url() . '">' . $this->card->get_product_id() . '</a>'
			];
		}

		if ( ! empty( $this->get_designer_message() ) ) {
			$table_data[] = [ 'label' => 'Designer Message', 'value' => $this->get_designer_message() ];
		}

		try {
			$this->set_intro_lines( 'New request from designer.' );
			$this->set_intro_lines( $this->all_fields_table( $table_data ) );
			$this->setSubject( 'Request from Designer for card id: #' . $this->card->get( 'id' ) );
			$this->setFrom( $current_user->user_email, $current_user->display_name );
			$this->setTo( get_option( 'admin_email' ) );
			$this->set_salutation( '&nbsp;' );
			$this->send();
		} catch ( \Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}

	/**
	 * @return string
	 */
	public function get_designer_message() {
		return $this->designer_message;
	}

	/**
	 * @param string $designer_message
	 *
	 * @return self
	 */
	public function set_designer_message( $designer_message ) {
		$this->designer_message = $designer_message;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_request_for() {
		return $this->request_for;
	}

	/**
	 * @param string $request_for
	 *
	 * @return self
	 */
	public function set_request_for( $request_for ) {
		$this->request_for = $request_for;

		return $this;
	}
}
