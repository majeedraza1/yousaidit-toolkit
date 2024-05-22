<?php

namespace YouSaidItCards\ShipStation;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

/**
 * OrderItemPdf
 */
class OrderItemPdf extends DatabaseModel {
	protected $table = 'shipstation_order_item_pdf';

	/**
	 * Get PDF id
	 *
	 * @return int
	 */
	public function get_pdf_id(): int {
		return (int) $this->get_prop( 'pdf_id' );
	}

	/**
	 * Get PDF width
	 *
	 * @return int
	 */
	public function get_pdf_width(): int {
		return (int) $this->get_prop( 'pdf_width' );
	}

	/**
	 * Get PDF height
	 *
	 * @return int
	 */
	public function get_pdf_height(): int {
		return (int) $this->get_prop( 'pdf_height' );
	}

	/**
	 * Find by product SKU
	 *
	 * @param  string  $product_sku  Product SKU.
	 * @param  int  $order_item_id  Order item id.
	 *
	 * @return false|static
	 */
	public static function find_by_sku( string $product_sku, int $order_item_id ) {
		$query = static::get_query_builder();
		$query->where( 'product_sku', $product_sku );
		$query->where( 'order_item_id', $order_item_id );
		$item = $query->first();
		if ( $item ) {
			return new static( $item );
		}

		return false;
	}

	public static function create_if_not_exists( array $data ): OrderItemPdf {
		$item = static::find_by_sku( $data['product_sku'], $data['order_item_id'] );
		if ( $item instanceof static ) {
			return $item;
		}

		$data['id'] = static::create( $data );

		return new static( $data );
	}

	public static function create_table() {
		global $wpdb;
		$self    = new static();
		$table   = $self->get_table_name();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`product_id` BIGINT(20) UNSIGNED NOT NULL,
				`product_sku` VARCHAR(50) NULL DEFAULT NULL,
				`store_id` VARCHAR(20) NULL DEFAULT NULL,
				`order_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'ShipStation order id',
				`order_item_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'ShipStation order item id',
				`pdf_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				`pdf_width` INT(6) UNSIGNED NOT NULL DEFAULT 0,
				`pdf_height` INT(6) UNSIGNED NOT NULL DEFAULT 0,
				`card_size` VARCHAR(50) NULL DEFAULT NULL,
				`created_at` datetime NULL DEFAULT NULL,
				`updated_at` datetime NULL DEFAULT NULL,
				PRIMARY KEY (id),
    			INDEX `product_sku` (`product_sku`),
    			UNIQUE `order_item_id` (`order_item_id`)
		) {$collate}";

		$version = get_option( $table . '_version', '0.1.0' );
		if ( version_compare( $version, '1.0.0', '<' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			update_option( $table . '_version', '1.0.0' );
		}
	}
}