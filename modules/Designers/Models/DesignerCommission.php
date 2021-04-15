<?php

namespace YouSaidItCards\Modules\Designers\Models;

use ArrayObject;
use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Error;

class DesignerCommission extends DatabaseModel {

	/**
	 * @inheritDoc
	 */
	protected $table = 'designer_commissions';

	/**
	 * Card table
	 *
	 * @var string
	 */
	protected $card_table = 'designer_cards';

	/**
	 * @inheritDoc
	 */
	protected $primaryKey = 'commission_id';

	/**
	 * @var array
	 */
	protected static $report_types = [ 'today', 'yesterday', 'current_week', 'current_month', 'last_month', 'custom' ];

	/**
	 * Array representation of the class
	 *
	 * @return array
	 */
	public function to_array() {
		$data = $this->data;

		$data['commission_id']    = intval( $this->get( 'commission_id' ) );
		$data['card_id']          = intval( $this->get( 'card_id' ) );
		$data['designer_id']      = intval( $this->get( 'designer_id' ) );
		$data['customer_id']      = intval( $this->get( 'customer_id' ) );
		$data['order_id']         = intval( $this->get( 'order_id' ) );
		$data['order_item_id']    = intval( $this->get( 'order_item_id' ) );
		$data['order_quantity']   = intval( $this->get( 'order_quantity' ) );
		$data['item_commission']  = floatval( $this->get( 'item_commission' ) );
		$data['total_commission'] = floatval( $this->get( 'total_commission' ) );
		$data['created_at']       = mysql_to_rfc3339( $this->get( 'created_at' ) );
		$data['updated_at']       = mysql_to_rfc3339( $this->get( 'updated_at' ) );

		unset( $data['deleted_at'] );

		return $data;
	}

	/**
	 * Get report type
	 *
	 * @return array
	 */
	public static function get_report_types() {
		return static::$report_types;
	}

	/**
	 * @param string $report_type
	 * @param string $from
	 * @param string $to
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function get_start_and_end_date( string $report_type, string $from = '', string $to = '' ): array {
		$startTime = new \DateTime();
		$startTime->setTimezone( wp_timezone() );

		$endTime = new \DateTime();
		$endTime->setTimezone( wp_timezone() );

		if ( 'yesterday' == $report_type ) {
			$startTime->modify( 'yesterday' );
			$endTime->modify( 'yesterday' );
		}

		if ( 'current_week' == $report_type ) {
			$startTime->modify( '- 6 days' );
		}

		if ( 'current_month' == $report_type ) {
			$startTime->modify( 'first day of this month' );
		}

		if ( 'last_month' == $report_type ) {
			$startTime->modify( 'first day of last month' );
			$endTime->modify( 'last day of last month' );
		}

		if ( 'custom' == $report_type ) {
			if ( strtotime( $from ) !== false ) {
				$startTime->setTimestamp( strtotime( $from ) );
			}
			if ( strtotime( $to ) !== false ) {
				$endTime->setTimestamp( strtotime( $to ) );
			}
		}

		return [
			$startTime->format( "Y-m-d" ),
			$endTime->format( "Y-m-d" )
		];
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function find( $args = [] ) {
		list( $per_page, $offset, $orderby, $order ) = $this->get_pagination_and_order_data( $args );

		global $wpdb;
		$table      = $this->get_table_name( $this->table );
		$card_table = $this->get_table_name( $this->card_table );

		$query = "SELECT {$table}.*, {$card_table}.card_title AS product_title, {$wpdb->users}.display_name as designer_name";

		$query .= $this->get_query_sql( $args );

		$query   .= " ORDER BY {$orderby} {$order}";
		$query   .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results;
	}

	/**
	 * Create if not already exists
	 *
	 * @param array $data
	 *
	 * @return WP_Error|int
	 */
	public static function createIfNotExists( array $data ) {
		if ( ! isset( $data['order_id'], $data['order_item_id'] ) ) {
			return new WP_Error( 'incomplete_data', 'Required data not found.' );
		}
		$item = static::find_for_order( $data['order_id'], $data['order_item_id'] );
		if ( $item instanceof self ) {
			return $item->get( 'id' );
		}

		return ( new static )->create( $data );
	}

	/**
	 * Find commission data based on order and order item
	 *
	 * @param int $order_id
	 * @param int $order_item_id
	 *
	 * @return ArrayObject|self
	 */
	public static function find_for_order( $order_id, $order_item_id ) {
		global $wpdb;
		$self  = new static();
		$table = $self->get_table_name( $self->table );

		$sql = "SELECT * FROM {$table} WHERE 1 = 1";
		$sql .= $wpdb->prepare( " AND order_id = %d", intval( $order_id ) );
		$sql .= $wpdb->prepare( " AND order_item_id = %d", intval( $order_item_id ) );

		$result = $wpdb->get_col( $sql, ARRAY_A );
		if ( $result ) {
			return new self( $result );
		}

		return new ArrayObject();
	}

	/**
	 * Get designer commission
	 *
	 * @return array
	 */
	public static function get_commission_by_designers(): array {
		$self = new static;
		global $wpdb;
		$table = $self->get_table_name();

		$query   = "SELECT SUM( total_commission ) AS unpaid_commission, designer_id FROM {$table}";
		$query   .= " WHERE {$self->deleted_at} IS NULL";
		$query   .= $wpdb->prepare( " AND payment_status = %s", 'unpaid' );
		$query   .= " GROUP BY designer_id";
		$results = $wpdb->get_results( $query, ARRAY_A );

		$query    = "SELECT SUM( total_commission ) AS paid_commission, designer_id FROM {$table}";
		$query    .= " WHERE {$self->deleted_at} IS NULL";
		$query    .= $wpdb->prepare( " AND payment_status = %s", 'paid' );
		$query    .= " GROUP BY designer_id";
		$results2 = $wpdb->get_results( $query, ARRAY_A );

		$data = [];
		foreach ( $results as $result ) {
			$data[ $result['designer_id'] ] = [
				'unpaid_commission' => round( floatval( $result['unpaid_commission'] ), 2 ),
			];
		}
		foreach ( $results2 as $result ) {
			if ( ! isset( $data[ $result['designer_id'] ] ) ) {
				$data[ $result['designer_id'] ] = [ 'unpaid_commission' => 0 ];
			}
			$data[ $result['designer_id'] ]['paid_commission'] = round( floatval( $result['paid_commission'] ), 2 );
		}

		return $data;
	}

	/**
	 * @param int $designer_id
	 *
	 * @return false|float|int
	 */
	public function get_total_commission_earned( $designer_id ) {
		global $wpdb;
		$table = $this->get_table_name( $this->table );

		$query   = "SELECT SUM( total_commission ) AS total_revenue FROM {$table} WHERE {$this->deleted_at} IS NULL";
		$query   .= $wpdb->prepare( " AND designer_id = %d", intval( $designer_id ) );
		$results = $wpdb->get_row( $query, ARRAY_A );

		return isset( $results['total_revenue'] ) ? round( $results['total_revenue'], 2 ) : 0;
	}

	/**
	 * @param int $designer_id
	 *
	 * @return false|float|int
	 */
	public function get_total_commission_earned_unpaid( $designer_id ) {
		global $wpdb;
		$table = $this->get_table_name( $this->table );

		$query   = "SELECT SUM( total_commission ) AS unpaid_revenue FROM {$table} WHERE {$this->deleted_at} IS NULL";
		$query   .= $wpdb->prepare( " AND payment_status = %s", 'unpaid' );
		$query   .= $wpdb->prepare( " AND designer_id = %d", intval( $designer_id ) );
		$results = $wpdb->get_row( $query, ARRAY_A );

		return isset( $results['unpaid_revenue'] ) ? round( $results['unpaid_revenue'], 2 ) : 0;
	}

	/**
	 * Count total unique orders for a designer
	 *
	 * @param int $designer_id
	 *
	 * @return int
	 */
	public function count_total_orders( $designer_id = 0 ) {
		global $wpdb;
		$table   = $this->get_table_name( $this->table );
		$query   = "SELECT count( DISTINCT(order_id) ) as total_orders FROM {$table} WHERE {$this->deleted_at} IS NULL";
		$query   .= $wpdb->prepare( " AND designer_id = %d", intval( $designer_id ) );
		$results = $wpdb->get_row( $query, ARRAY_A );

		return isset( $results['total_orders'] ) ? intval( $results['total_orders'] ) : 0;
	}

	/**
	 * Get unique customer
	 *
	 * @param int $designer_id
	 *
	 * @return array
	 */
	public function count_unique_customers( $designer_id = 0 ) {
		global $wpdb;
		$table = $this->get_table_name( $this->table );
		$query = "SELECT customer_id, SUM( order_quantity ) AS number_of_buy FROM {$table} WHERE {$this->deleted_at} IS NULL";
		if ( $designer_id ) {
			$query .= $wpdb->prepare( " AND designer_id = %d", intval( $designer_id ) );
		}
		$query   .= " GROUP BY `customer_id`";
		$results = $wpdb->get_results( $query, ARRAY_A );

		$statuses = [];

		foreach ( $results as $status ) {
			$statuses[] = [
				'customer_id'     => intval( $status['customer_id'] ),
				'cards_purchased' => intval( $status['number_of_buy'] ),
			];
		}

		return $statuses;
	}

	public function get_unpaid_commission( $args = [] ) {
		global $wpdb;
		$table = $this->get_table_name( $this->table );

		$payment_statuses = [ 'paid', 'unpaid' ];
		$order_statuses   = array_keys( wc_get_order_statuses() );

		$payment_status = isset( $args['payment_status'] ) ? $args['payment_status'] : '';
		$payment_status = in_array( $payment_status, $payment_statuses ) ? $payment_status : 'unpaid';

		$order_status = isset( $args['order_status'] ) ? $args['order_status'] : '';
		$order_status = in_array( 'wc-' . $order_status, $order_statuses ) ? $order_status : '';

		$designer_id = isset( $args['designer_id'] ) ? intval( $args['designer_id'] ) : 0;
		$order_id    = isset( $args['order_id'] ) ? intval( $args['order_id'] ) : 0;

		$query = "SELECT * FROM {$table}";
		$query .= " WHERE 1 = 1";
		$query .= $wpdb->prepare( " AND payment_status = %s", $payment_status );

		if ( ! empty( $order_status ) ) {
			$query .= $wpdb->prepare( " AND order_status = %s", $order_status );
		}

		if ( ! empty( $designer_id ) ) {
			$query .= $wpdb->prepare( " AND designer_id = %d", $designer_id );
		}

		if ( ! empty( $order_id ) ) {
			$query .= $wpdb->prepare( " AND order_id = %d", $order_id );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		return is_array( $results ) && count( $results ) ? $results : [];
	}

	/**
	 * @param string|int $payment_id
	 * @param array $commission_ids
	 */
	public function mark_commissions_paid( $payment_id, $commission_ids ) {
		global $wpdb;
		$table = $this->get_table_name( $this->table );
		$query = $wpdb->prepare( "UPDATE {$table} SET payment_status = 'paid', payment_id = %s", $payment_id );
		$query .= " WHERE commission_id IN(" . implode( ',', $commission_ids ) . ")";
		$wpdb->query( $query );
	}

	/**
	 * @inheritDoc
	 */
	public function count_records( array $args = [] ) {
		global $wpdb;
		$query   = "SELECT COUNT(*) AS total_items";
		$query   .= $this->get_query_sql( $args );
		$results = $wpdb->get_row( $query, ARRAY_A );

		return is_numeric( $results['total_items'] ) ? intval( $results['total_items'] ) : 0;
	}

	/**
	 * Count for payout
	 *
	 * @return array
	 */
	public static function count_card_for_payout() {
		$self = new static;
		global $wpdb;
		$table = $self->get_table_name( $self->table );

		$counts = [
			'designers'     => 0,
			'orders'        => 0,
			'orders_status' => [],
		];

		$query  = "SELECT COUNT(DISTINCT designer_id) AS total_designers FROM {$table}";
		$query  .= " WHERE {$self->deleted_at} IS NULL AND payment_status = 'unpaid'";
		$result = $wpdb->get_row( $query, ARRAY_A );

		$counts['designers'] = intval( $result['total_designers'] );

		$query  = "SELECT COUNT(DISTINCT order_id) AS total_orders FROM {$table}";
		$query  .= " WHERE {$self->deleted_at} IS NULL AND payment_status = 'unpaid'";
		$result = $wpdb->get_row( $query, ARRAY_A );

		$counts['orders'] = intval( $result['total_orders'] );

		$query  = "SELECT SUM(total_commission) AS total_commissions FROM {$table}";
		$query  .= " WHERE {$self->deleted_at} IS NULL AND payment_status = 'unpaid'";
		$result = $wpdb->get_row( $query, ARRAY_A );

		$counts['total_commission'] = round( floatval( $result['total_commissions'] ), 2 );

		$query          = "SELECT order_status, COUNT( * ) AS total FROM {$table}";
		$query          .= " WHERE {$self->deleted_at} IS NULL AND payment_status = 'unpaid'";
		$query          .= " GROUP BY order_status";
		$order_statuses = $wpdb->get_results( $query, ARRAY_A );

		$currency_symbol = get_woocommerce_currency_symbol();

		$cards = [
			[ 'key' => 'designers', 'label' => 'Designers to Pay', 'count' => $counts['designers'] ],
			[ 'key' => 'orders', 'label' => 'Total Orders to Pay', 'count' => $counts['orders'] ],
			[
				'key'   => 'total_commission',
				'label' => 'Total Amount to Pay',
				'count' => sprintf( "%s%s", $currency_symbol, $counts['total_commission'] )
			],
		];

		if ( count( $order_statuses ) ) {
			if ( ! function_exists( 'wc_get_order_statuses' ) && defined( 'WC_ABSPATH' ) ) {
				include_once WC_ABSPATH . 'includes/wc-order-functions.php';
			}
			$_statuses = wc_get_order_statuses();
			foreach ( $order_statuses as $result ) {
				$label = $_statuses[ 'wc-' . $result['order_status'] ];

				$cards[] = [
					'key'   => 'orders_status_' . $result['order_status'],
					'label' => 'Orders(' . $label . ') to Pay',
					'count' => intval( $result['total'] )
				];
			}
		}

		return $cards;
	}

	/**
	 * @inheritDoc
	 */
	public function create_table() {
		global $wpdb;
		$table_name = $this->get_table_name( $this->table );
		$collate    = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
			commission_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			card_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			designer_id bigint(20) NOT NULL DEFAULT '0',
			customer_id bigint(20) NOT NULL DEFAULT '0',
			order_id bigint(20) NOT NULL DEFAULT '0' COMMENT 'ShipStation order id',
			order_item_id bigint(20) NOT NULL DEFAULT '0' COMMENTS 'ShipStation order item id',
			order_quantity int(10) NOT NULL DEFAULT '0',
			item_commission float NOT NULL DEFAULT '0',
			total_commission float NOT NULL DEFAULT '0',
			card_size varchar(20) NULL DEFAULT NULL,
			payment_status varchar(10) NULL DEFAULT 'unpaid',
			payment_id varchar(100) NULL DEFAULT NULL,
			order_status varchar(20) NULL DEFAULT NULL,
			created_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NULL DEFAULT NULL,
			deleted_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (commission_id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );

		$this->add_foreign_key();
	}

	public function add_foreign_key() {
		global $wpdb;
		$table_name = $this->get_table_name( $this->table );
		$card_table = $this->get_table_name( $this->card_table );

		$option = get_option( 'designer_commissions_table_version', '1.0.0' );
		if ( version_compare( $option, '1.0.1', '<' ) ) {
			$constant_name = $this->get_foreign_key_constant_name( $table_name, $card_table );
			$sql           = "ALTER TABLE `{$table_name}`";
			$sql           .= " ADD CONSTRAINT `{$constant_name}`";
			$sql           .= " FOREIGN KEY (`card_id`)";
			$sql           .= " REFERENCES `{$card_table}`(`id`)";
			$sql           .= " ON DELETE NO ACTION";
			$sql           .= " ON UPDATE CASCADE;";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `designer_id` (`designer_id`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `payment_status` (`payment_status`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `payment_id` (`payment_id`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `order_status` (`order_status`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `created_at` (`created_at`);";
			$wpdb->query( $sql );

			update_option( 'designer_commissions_table_version', '1.0.1' );
		}

		if ( version_compare( $option, '1.0.2', '<' ) ) {
			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `order_id` (`order_id`);";
			$wpdb->query( $sql );

			update_option( 'designer_commissions_table_version', '1.0.2' );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_query_sql( array $args ): string {
		global $wpdb;
		$table      = $this->get_table_name( $this->table );
		$card_table = $this->get_table_name( $this->card_table );

		$query = " FROM {$table}";
		$query .= " LEFT JOIN {$card_table} ON {$table}.card_id = {$card_table}.id";
		$query .= " LEFT JOIN {$wpdb->users} ON {$table}.designer_id = {$wpdb->users}.ID";
		$query .= " WHERE 1=1";

		if ( isset( $args['designer_id'] ) && is_numeric( $args['designer_id'] ) ) {
			$query .= $wpdb->prepare( " AND designer_id = %d", intval( $args['designer_id'] ) );
		}

		if ( isset( $args['order_id__in'] ) && is_array( $args['order_id__in'] ) ) {
			$ids   = array_map( 'intval', $args['order_id__in'] );
			$query .= " AND order_id IN(" . implode( ',', $ids ) . ")";
		}

		if ( isset( $args['payment_status'] ) && in_array( $args['payment_status'], [ 'paid', 'unpaid' ] ) ) {
			$query .= $wpdb->prepare( " AND payment_status = %s", $args['payment_status'] );
		}


		if ( ! empty( $args['order_status'] ) && substr( $args['order_status'], 0, 3 ) == 'wc-' ) {
			$order_status = str_replace( 'wc-', '', $args['order_status'] );
			$query        .= $wpdb->prepare( " AND order_status = %s", $order_status );
		}

		if ( isset( $args['from'], $args['to'] ) && Validate::date( $args['from'] ) && Validate::date( $args['to'] ) ) {
			$query .= $wpdb->prepare( " AND {$table}.created_at BETWEEN %s AND %s",
				$args['from'] . " 00:00:00",
				$args['to'] . " 23:59:59"
			);
		}

		return $query;
	}
}
