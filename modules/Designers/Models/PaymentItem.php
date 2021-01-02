<?php

namespace YouSaidItCards\Modules\Designers\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

defined( 'ABSPATH' ) || exit;

class PaymentItem extends DatabaseModel {

	/**
	 * @inhericDoc
	 */
	protected $table = 'designer_payment_items';

	/**
	 * @inheritDoc
	 */
	protected $primaryKey = 'item_id';

	/**
	 * Payment table name
	 *
	 * @var string
	 */
	protected $payment_table = 'designer_payments';

	/**
	 * @param int $designer_id
	 * @param array $args
	 *
	 * @return array
	 */
	public function find_by_designer_id( $designer_id, array $args = [] ) {
		list( $per_page, $offset, $orderby, $order ) = $this->get_pagination_and_order_data( $args );

		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$query = "SELECT * FROM {$table} WHERE 1=1";
		$query .= $wpdb->prepare( " AND designer_id = %d", intval( $designer_id ) );

		$query   .= " ORDER BY {$orderby} {$order}";
		$query   .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
		$results = $wpdb->get_results( $query, ARRAY_A );

		$items = [];

		if ( count( $results ) ) {
			foreach ( $results as $result ) {
				$items[] = new self( $result );
			}
		}

		return $items;
	}

	/**
	 * @param $payment_id
	 *
	 * @return array|object|null
	 */
	public function find_by_payment_id( $payment_id ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE payment_id = %d", intval( $payment_id ) ),
			ARRAY_A
		);

		$items = [];

		if ( count( $results ) ) {
			foreach ( $results as $result ) {
				$items[] = new self( $result );
			}
		}

		return $items;
	}

	/**
	 * @inheritDoc
	 */
	public function count_records() {
		return [];
	}

	/**
	 * @param \PayPal\Api\PayoutItemDetails $data
	 *
	 * @return mixed
	 */
	public static function payoutItemToPaymentItem( $data ) {
		$item = $data->getPayoutItem();

		return [
			'item_id'            => (int) $item->getSenderItemId(),
			'payout_item_id'     => $data->getPayoutItemId(),
			'transaction_status' => $data->getTransactionStatus(),
			'currency'           => $item->getAmount()->getCurrency(),
			'total_commissions'  => (float) $item->getAmount()->getValue(),
			'error_message'      => $data->getErrors()->getMessage(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function create_table() {
		global $wpdb;
		$table_name    = $this->get_table_name( $this->table );
		$payment_table = $this->get_table_name( $this->payment_table );
		$collate       = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
			item_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			payment_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			designer_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			paypal_email varchar(100) NULL DEFAULT NULL,
			total_commissions float NOT NULL DEFAULT '0',
			currency varchar(3) NOT NULL DEFAULT 'USD',
			order_ids TEXT NULL DEFAULT NULL,
			commission_ids TEXT NULL DEFAULT NULL,
			note TEXT NULL DEFAULT NULL,
			payout_item_id varchar(255) NULL DEFAULT NULL,
			transaction_status varchar(50) NULL DEFAULT NULL,
			error_message TEXT NULL DEFAULT NULL,
			created_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NULL DEFAULT NULL,
			deleted_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (item_id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );

		$sql = "ALTER TABLE `{$table_name}`";
		$sql .= " ADD CONSTRAINT `{$payment_table}_{$table_name}_foreign`";
		$sql .= " FOREIGN KEY (`payment_id`)";
		$sql .= " REFERENCES `{$payment_table}`(`payment_id`)";
		$sql .= " ON DELETE CASCADE";
		$sql .= " ON UPDATE CASCADE;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE `{$table_name}` ADD INDEX `designer_id` (`designer_id`);";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE `{$table_name}` ADD INDEX `paypal_email` (`paypal_email`);";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE `{$table_name}` ADD INDEX `created_at` (`created_at`);";
		$wpdb->query( $sql );
	}
}
