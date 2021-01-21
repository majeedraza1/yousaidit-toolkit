<?php

namespace YouSaidItCards\Modules\Designers\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;

defined( 'ABSPATH' ) || exit;

class Payment extends DatabaseModel {

	/**
	 * @inhericDoc
	 */
	protected $table = 'designer_payments';

	/**
	 * @inheritDoc
	 */
	protected $primaryKey = 'payment_id';

	/**
	 * @inheritDoc
	 */
	public function to_array() {
		$data = $this->data;

		$data[ $this->primaryKey ] = intval( $data[ $this->primaryKey ] );

		return $data;
	}

	/**
	 * Get payment batch id
	 *
	 * @return string
	 */
	public function get_payment_batch_id() {
		return $this->get( 'payment_batch_id', '' );
	}

	/**
	 * Get payment items
	 *
	 * @return array|PaymentItem[]
	 */
	public function get_payment_items() {
		return ( new PaymentItem )->find_by_payment_id( $this->get( 'payment_id' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function find( $args = [] ) {
		$_items = parent::find( $args );
		$items  = [];

		if ( count( $_items ) ) {
			foreach ( $_items as $item ) {
				$items[] = new self( $item );
			}
		}

		return $items;
	}

	/**
	 * @inheritDoc
	 */
	public function find_by_id( $id ) {
		$item = parent::find_by_id( $id );
		if ( $item ) {
			return new self( $item );
		}

		return false;
	}

	/**
	 * Create payout data
	 *
	 * @param array $commissions
	 * @param array $data
	 * @param int $min_amount
	 *
	 * @return array
	 */
	public static function create_payout( array $commissions, array $data = [], $min_amount = 0 ) {
		$batch_id = isset( $data['payment_batch_id'] ) ? sanitize_text_field( $data['payment_batch_id'] ) : 'TEMP';
		$status   = isset( $data['payment_status'] ) ? sanitize_text_field( $data['payment_status'] ) : 'TEMP';

		$payment_id = ( new static )->create( [ 'payment_batch_id' => $batch_id, 'payment_status' => $status, ] );

		$items           = [];
		$commissions_ids = [];
		if ( $payment_id ) {
			$items = static::create_payment_items( $payment_id, $commissions, $min_amount );
			foreach ( $items as $item ) {
				$commissions_ids = array_merge( $commissions_ids, $item['commission_ids'] );
			}
		}

		return [
			'payment_id'     => $payment_id,
			'items'          => $items,
			'commission_ids' => $commissions_ids,
		];
	}

	/**
	 * Format commission data
	 *
	 * @param int $payment_id
	 * @param array $commissions
	 * @param int $min_amount
	 *
	 * @return array
	 */
	public static function create_payment_items( $payment_id, array $commissions, $min_amount = 0 ) {
		$items       = [];
		$commissions = static::get_commissions_group_by_designer( $commissions );

		foreach ( $commissions as $designer_id => $_commissions ) {
			$total_commissions = wp_list_pluck( $_commissions, 'total_commission' );
			$total_commissions = array_sum( array_map( 'floatval', $total_commissions ) );

			if ( $min_amount && $total_commissions < $min_amount ) {
				continue;
			}

			$items[] = static::create_payment_item(
				new CardDesigner( $designer_id ),
				$payment_id,
				$_commissions
			);
		}

		return $items;
	}

	protected static function get_commissions_group_by_designer( array $commissions ) {
		$items = [];
		if ( count( $commissions ) ) {
			$designers_ids = wp_list_pluck( $commissions, 'designer_id' );
			$designers_ids = array_unique( array_map( 'intval', $designers_ids ) );
			foreach ( $designers_ids as $designer_id ) {
				foreach ( $commissions as $result ) {
					if ( $result['designer_id'] == $designer_id ) {
						$items[ $designer_id ][] = $result;
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Create payment item
	 *
	 * @param CardDesigner $designer CardDesigner object
	 * @param int $payment_id Payment id
	 * @param array $commissions Array of commissions data
	 *
	 * @return array
	 */
	protected static function create_payment_item( $designer, $payment_id, array $commissions ) {
		$total_commissions = wp_list_pluck( $commissions, 'total_commission' );
		$total_commissions = array_sum( array_map( 'floatval', $total_commissions ) );

		$order_ids = wp_list_pluck( $commissions, 'order_id' );
		$order_ids = array_unique( array_map( 'intval', $order_ids ) );

		$commission_ids = wp_list_pluck( $commissions, 'commission_id' );
		$commission_ids = array_unique( array_map( 'intval', $commission_ids ) );

		$note = "Email: " . $designer->get_paypal_email();
		$note .= "; order ids: #" . implode( ', #', $order_ids );
		$note .= "; commission ids: #" . implode( ', #', $commission_ids );

		$data = [
			'payment_id'        => $payment_id,
			'designer_id'       => $designer->get_user_id(),
			'paypal_email'      => $designer->get_paypal_email(),
			'total_commissions' => $total_commissions,
			'currency'          => 'NZD',
			'order_ids'         => $order_ids,
			'commission_ids'    => $commission_ids,
			'note'              => $note,
		];

		$data['currency'] = apply_filters( 'woocommerce_currency', get_option( 'woocommerce_currency' ) );

		if ( Validate::email( $designer->get_paypal_email() ) ) {
			$item_id = ( new PaymentItem )->create( $data );
		}
		if ( isset( $item_id ) && is_numeric( $item_id ) ) {
			$data['item_id'] = $item_id;
		} else {
			$data['item_id'] = uniqid();
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function create_table() {
		global $wpdb;
		$table_name = $this->get_table_name( $this->table );
		$collate    = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
			payment_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			payment_batch_id varchar(50) NULL DEFAULT NULL,
			payment_status varchar(50) NULL DEFAULT NULL,
			currency varchar(3) NULL DEFAULT NULL,
			amount float NOT NULL DEFAULT '0',
			created_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NULL DEFAULT NULL,
			deleted_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (payment_id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );
	}
}
