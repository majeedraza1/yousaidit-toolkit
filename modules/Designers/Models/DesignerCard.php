<?php

namespace YouSaidItCards\Modules\Designers\Models;

use ArrayObject;
use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use Stackonet\WP\Framework\Supports\Validate;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class DesignerCard extends DatabaseModel {

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'designer_cards';

	/**
	 * Column name for holding author id
	 *
	 * @var string
	 */
	protected $created_by = 'designer_user_id';

	/**
	 * @var string
	 */
	protected $cache_group = 'designer_cards';

	/**
	 * @var array
	 */
	protected $gallery_images = [];

	/**
	 * @var bool
	 */
	protected $gallery_images_read = false;

	/**
	 * Card categories
	 *
	 * @var array
	 */
	protected $categories = [];

	/**
	 * Card tags
	 *
	 * @var array
	 */
	protected $tags = [];

	/**
	 * Valid status
	 *
	 * @var array
	 */
	protected static $valid_statuses = [ 'processing', 'accepted', 'rejected', 'need-modification' ];

	/**
	 * @var CardDesigner
	 */
	private $designer;

	/**
	 * Card comments
	 *
	 * @var array
	 */
	private $comments = [];

	/**
	 * @var bool
	 */
	private $comments_read = false;

	/**
	 * Get comments count
	 *
	 * @var int
	 */
	private $comments_count = 0;

	/**
	 * Get available statuses
	 *
	 * @return array
	 */
	public static function get_available_statuses() {
		return apply_filters( 'yousaiditcard_available_card_statuses', [
			'processing'        => 'Processing',
			'accepted'          => 'Accepted',
			'rejected'          => 'Rejected',
			'need-modification' => 'Need Modification',
			'draft'             => 'Draft',
			'trash'             => 'Trash',
		] );
	}

	/**
	 * Get available market places
	 *
	 * @return string[]
	 */
	public static function get_available_market_places() {
		return [ 'yousaidit', 'yousaidit-trade', 'etsy', 'amazon', 'ebay' ];
	}

	/**
	 * Get valid status
	 *
	 * @return array
	 */
	public static function get_valid_statuses() {
		return array_keys( static::get_available_statuses() );
	}

	/**
	 * Array representation of the class
	 *
	 * @return array
	 */
	public function to_array() {
		$data = [
			'id'                     => intval( $this->get( 'id' ) ),
			'card_title'             => $this->get( 'card_title' ),
			'card_sizes'             => $this->get( 'card_sizes' ),
			'categories'             => $this->get_categories(),
			'tags'                   => $this->get_tags(),
			'sizes'                  => $this->get_sizes(),
			'attributes'             => $this->get_attributes(),
			'image'                  => $this->get_image(),
			'gallery_images'         => $this->get_gallery_images(),
			'pdf_data'               => $this->get_pdf_data(),
			'total_sale'             => $this->get_all_sizes_total_sales(),
			'commission'             => $this->get_commission_data(),
			'marketplace_commission' => $this->get_marketplace_commission(),
			'product_id'             => $this->get_product_id(),
			'designer_user_id'       => $this->get_designer_user_id(),
			'rude_card'              => $this->is_rude_card(),
			'status'                 => $this->get( 'status' ),
			'card_sku'               => $this->get( 'card_sku' ),
			'suggest_tags'           => $this->get( 'suggest_tags' ),
			'market_place'           => $this->get_market_places(),
			'comments_count'         => $this->get_comments_count(),
			'created_at'             => mysql_to_rfc3339( $this->get( 'created_at' ) ),
			'updated_at'             => mysql_to_rfc3339( $this->get( 'updated_at' ) ),
		];

		if ( $this->get_product_id() ) {
			$data['product_url'] = $this->get_product_edit_url();
		}

		if ( ! empty( $this->get( 'deleted_at' ) ) ) {
			$data['status'] = 'trash';
		}

		$data['designer'] = $this->get_designer()->to_array();

		return $data;
	}

	/**
	 * Get market places
	 *
	 * @return array
	 */
	public function get_market_places(): array {
		$market_places = $this->get( 'market_places' );
		$market_places = is_array( $market_places ) ? $market_places : [];

		if ( false === array_search( 'yousaidit', $market_places, true ) ) {
			$market_places[] = 'yousaidit';
		}

		return $market_places;
	}

	/**
	 * Get card id
	 *
	 * @return int
	 */
	public function get_id() {
		return intval( $this->get( 'id' ) );
	}

	/**
	 * Get designer user id
	 *
	 * @return int
	 */
	public function get_designer_user_id() {
		return intval( $this->get( $this->created_by ) );
	}

	/**
	 * Get card edit url
	 *
	 * @return string
	 */
	public function get_card_edit_url() {
		return add_query_arg( [ 'page' => 'designers#/cards/' . $this->get_id() ], admin_url( 'admin.php' ) );
	}

	/**
	 * Get card type
	 *
	 * @return string
	 */
	public function get_card_type(): string {
		return $this->get( 'card_type', 'static' );
	}

	/**
	 * Check if card type dynamic
	 * @return bool
	 */
	public function is_dynamic_card(): bool {
		return 'dynamic' == $this->get_card_type();
	}

	public function get_all_sizes_total_sales() {
		$sales = (array) $this->get( 'total_sale' );
		$count = 0;
		foreach ( $sales as $sizeKey => $saleCount ) {
			if ( is_numeric( $saleCount ) ) {
				$count += floatval( $saleCount );
			}
		}

		return $count;
	}

	/**
	 * Get designer
	 *
	 * @return CardDesigner
	 */
	public function get_designer() {
		if ( ! $this->designer instanceof CardDesigner ) {
			$this->designer = new CardDesigner( $this->get_designer_user_id() );
		}

		return $this->designer;
	}

	/**
	 * Get card comments
	 *
	 * @return array
	 */
	public function get_comments() {
		if ( false === $this->comments_read ) {
			$this->comments       = CardComment::get_comments_for_card( $this->get_id() );
			$this->comments_count = count( $this->comments );
			$this->comments_read  = true;
		}

		return $this->comments;
	}

	/**
	 * @return int
	 */
	public function get_comments_count() {
		if ( false === $this->comments_read ) {
			$this->get_comments();
		}

		return $this->comments_count;
	}

	/**
	 * Get product id
	 *
	 * @return int
	 */
	public function get_product_id() {
		return intval( $this->get( 'product_id' ) );
	}

	/**
	 * Get product edit url
	 *
	 * @return string
	 */
	public function get_product_edit_url() {
		if ( ! $this->get_product_id() ) {
			return '';
		}

		return add_query_arg( [ 'post' => $this->get_product_id(), 'action' => 'edit' ], admin_url( 'post.php' ) );
	}

	/**
	 * Is rude card
	 *
	 * @return bool
	 */
	public function is_rude_card() {
		return Validate::checked( $this->get( 'rude_card' ) );
	}

	/**
	 * Get commission data
	 *
	 * @return array
	 */
	public function get_commission_data(): array {
		$commission  = $this->get( 'commission_per_sale' );
		$_commission = $commission['commission'] ?? [];
		$data        = [];
		if ( isset( $commission['commission_type'] ) ) {
			$data['commission_type'] = $commission['commission_type'];
		}
		foreach ( $_commission as $size => $value ) {
			$data['commission_amount'][ $size ] = floatval( $value );
		}

		return $data;
	}

	/**
	 * Get marketplace commission
	 *
	 * @return array
	 */
	public function get_marketplace_commission() {
		$commissions = (array) $this->get( 'marketplace_commission' );

		$default = [];
		foreach ( $this->get_market_places() as $marketplace ) {
			foreach ( $this->get( 'card_sizes' ) as $size ) {
				$commission = isset( $commissions[ $marketplace ][ $size ] ) ?
					floatval( $commissions[ $marketplace ][ $size ] ) : 0;

				$default[ $marketplace ][ $size ] = $commission > 0 ? $commission : '';
			}
		}

		return $default;
	}

	/**
	 * Get commission
	 *
	 * @param string $size
	 * @param string|null $marketplace
	 *
	 * @return float|int
	 */
	public function get_commission( $size, $marketplace = null ) {
		$commissions        = $this->get_commission_data();
		$amount             = $commissions['commission_amount'];
		$general_commission = isset( $amount[ $size ] ) ? floatval( $amount[ $size ] ) : 0;

		if ( ! empty( $marketplace ) ) {
			$commissions = $this->get_marketplace_commission();

			return ( isset( $commissions[ $marketplace ][ $size ] ) && $commissions[ $marketplace ][ $size ] ) ?
				floatval( $commissions[ $marketplace ][ $size ] ) :
				$general_commission;
		}

		return $general_commission;
	}

	/**
	 * Get categories
	 *
	 * @return array
	 */
	public function get_categories() {
		/** @var \WP_Term[] $terms */
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'include'    => $this->get( 'categories_ids' ),
		) );
		foreach ( $terms as $term ) {
			$this->categories[] = [
				'id'    => $term->term_id,
				'title' => $term->name,
			];
		}

		return $this->categories;
	}

	/**
	 * Get card tags
	 *
	 * @return array
	 */
	public function get_tags() {
		$tags_ids = $this->get( 'tags_ids' );
		if ( count( $tags_ids ) < 1 ) {
			return [];
		}
		/** @var \WP_Term[] $terms */
		$terms = get_terms( array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
			'include'    => $this->get( 'tags_ids' ),
		) );
		foreach ( $terms as $term ) {
			$this->tags[] = [
				'id'    => $term->term_id,
				'title' => $term->name,
			];
		}

		return $this->tags;
	}

	/**
	 * get sizes
	 */
	public function get_sizes() {
		$card_sizes = $this->get( 'card_sizes' );
		$settings   = get_option( 'yousaiditcard_designers_settings' );
		if ( empty( $settings['product_attribute_for_card_sizes'] ) ) {
			return $card_sizes;
		}

		$attr_name = $settings['product_attribute_for_card_sizes'];
		$taxonomy  = wc_attribute_taxonomy_name( $attr_name );
		/** @var WP_Term[] $terms */
		$terms   = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, ] );
		$options = [];
		foreach ( $terms as $term ) {
			if ( ! in_array( $term->slug, $card_sizes ) ) {
				continue;
			}
			$options[] = [
				'id'    => $term->term_id,
				'title' => $term->name,
			];
		}

		return $options;
	}

	/**
	 * Get card attributes
	 *
	 * @return array
	 */
	public function get_attributes() {
		$_attributes = [];
		$attributes  = $this->get( 'attributes' );


		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $tax ) {
			foreach ( $attributes as $attribute => $attribute_ids ) {
				if ( $tax->attribute_name != $attribute ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
				/** @var WP_Term[] $terms */
				$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'include' => $attribute_ids ] );

				$options = [];
				foreach ( $terms as $term ) {
					$options[] = [
						'id'    => $term->term_id,
						'title' => $term->name,
					];
				}

				$_attributes[] = [
					'attribute_id'    => $tax->attribute_id,
					'attribute_name'  => $tax->attribute_name,
					'attribute_label' => esc_html( $tax->attribute_label ),
					'options'         => $options,
				];
			}
		}

		return $_attributes;
	}

	/**
	 * Get attachment ids
	 *
	 * @return array
	 */
	public function get_attachment_ids() {
		return $this->get( 'attachment_ids' );
	}

	/**
	 * Get gallery images ids
	 *
	 * @return int[]
	 */
	public function get_gallery_images_ids() {
		return isset( $this->get_attachment_ids()['gallery_images_ids'] ) ? $this->get_attachment_ids()['gallery_images_ids'] : 0;
	}

	/**
	 * Get gallery images ids
	 *
	 * @return array
	 */
	public function get_pdf_ids() {
		return isset( $this->get_attachment_ids()['pdf_ids'] ) ? $this->get_attachment_ids()['pdf_ids'] : [];
	}

	/**
	 * Get image id
	 *
	 * @return int
	 */
	public function get_image_id() {
		return isset( $this->get_attachment_ids()['image_id'] ) ? $this->get_attachment_ids()['image_id'] : 0;
	}

	/**
	 * Get product image id
	 *
	 * @return int
	 */
	public function get_product_image_id() {
		if ( ! $this->get_product_id() ) {
			return 0;
		}

		return (int) get_post_thumbnail_id( $this->get_product_id() );
	}

	/**
	 * Get gallery images
	 *
	 * @param string $size
	 *
	 * @return array|ArrayObject
	 */
	public function get_image( $size = 'full' ) {
		$id = $this->get_image_id();
		if ( empty( $id ) ) {
			$id = $this->get_product_image_id();
		}
		$img = wp_get_attachment_image_src( $id, $size );

		if ( $img === false ) {
			return new ArrayObject();
		}

		return [
			'id'     => $id,
			'title'  => get_the_title( $id ),
			'url'    => $img[0],
			'width'  => $img[1],
			'height' => $img[2],
		];
	}

	/**
	 * Get gallery images
	 *
	 * @param string $size
	 *
	 * @return array
	 */
	public function get_gallery_images( $size = 'full' ) {
		if ( ! $this->gallery_images_read ) {
			$ids = $this->get_gallery_images_ids();
			foreach ( $ids as $id ) {
				$img = wp_get_attachment_image_src( $id, $size );
				if ( false === $img ) {
					continue;
				}
				$this->gallery_images[] = [
					'id'     => $id,
					'title'  => get_the_title( $id ),
					'url'    => $img[0],
					'width'  => $img[1],
					'height' => $img[2],
				];
			}
			$this->gallery_images_read = true;
		}

		return $this->gallery_images;
	}

	/**
	 * Get PDF data
	 *
	 * @return array
	 */
	public function get_pdf_data() {
		$pdf_ids = $this->get_pdf_ids();
		$data    = [];
		foreach ( $pdf_ids as $size => $ids ) {
			foreach ( $ids as $id ) {
				$data[ $size ][] = [
					'id'    => $id,
					'title' => get_the_title( $id ),
					'url'   => wp_get_attachment_url( $id ),
				];
			}
		}

		return $data;
	}

	/**
	 * Get pdf id for a size
	 *
	 * @param string $size
	 *
	 * @return int
	 */
	public function get_pdf_id_for_size( $size ) {
		$pdf_ids = $this->get_pdf_ids();

		return isset( $pdf_ids[ $size ] ) && is_array( $pdf_ids[ $size ] ) ? intval( $pdf_ids[ $size ][0] ) : 0;
	}

	/**
	 * Find multiple records from database
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function find( $args = [] ) {
		list( $per_page, $offset, $orderby, $order ) = $this->get_pagination_and_order_data( $args );
		$search = isset( $args['search'] ) ? esc_sql( $args['search'] ) : '';
		$status = isset( $args['status'] ) ? $args['status'] : 'all';

		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$query = "SELECT * FROM {$table} WHERE 1=1";

		if ( isset( $args[ $this->created_by ] ) && is_numeric( $args[ $this->created_by ] ) ) {
			$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $args[ $this->created_by ] ) );
		}

		if ( ! empty( $search ) ) {
			$query .= " AND(";
			$query .= $wpdb->prepare( " `card_title` LIKE %s", '%' . $search . '%' );
			$query .= $wpdb->prepare( " OR `card_sku` LIKE %s", '%' . $search . '%' );
			$query .= " )";
		}

		if ( 'trash' == $status ) {
			$query .= " AND {$this->deleted_at} IS NOT NULL";
		} else {
			$query .= " AND {$this->deleted_at} IS NULL";
		}

		if ( in_array( $status, static::$valid_statuses ) ) {
			$query .= $wpdb->prepare( " AND status = %s", $status );
		}

		$query   .= " ORDER BY {$orderby} {$order}";
		$query   .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
		$results = $wpdb->get_results( $query, ARRAY_A );

		$items = [];
		foreach ( $results as $result ) {
			$items[] = new self( $result );
		}

		return $items;
	}

	/**
	 * Find record by id
	 *
	 * @param int $id
	 *
	 * @return array|self|ArrayObject
	 */
	public function find_by_id( $id ) {
		$item = parent::find_by_id( $id );
		if ( $item ) {
			return new self( $item );
		}

		return new ArrayObject();
	}

	/**
	 * Reset product id
	 *
	 * @param int $product_id
	 */
	public function reset_product_id( $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		$wpdb->update( $table, [ 'product_id' => 0 ], [ 'product_id' => $product_id ] );
	}

	/**
	 * Increase sales count
	 *
	 * @param \WC_Order_Item_Product $product_item
	 */
	public function increase_sales_count( $product_item ) {
		$size     = static::get_order_item_card_size( $product_item );
		$quantity = (int) $product_item->get_quantity();
		if ( false == $size || $quantity < 1 ) {
			return;
		}

		$sales         = $this->get( 'total_sale' );
		$sales         = is_array( $sales ) ? $sales : [];
		$current_value = isset( $sales[ $size ] ) ? intval( $sales[ $size ] ) : 0;

		$sales[ $size ] = $current_value + $quantity;

		$this->update( [ 'id' => $this->get( 'id' ), 'total_sale' => $sales ] );
	}

	/**
	 * Get user cards categories ids
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_user_cards_categories_ids( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$query   = "SELECT `categories_ids` FROM {$table} WHERE 1=1";
		$query   .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $user_id ) );
		$results = $wpdb->get_results( $query, ARRAY_A );
		$ids     = [];
		if ( count( $results ) ) {
			foreach ( $results as $result ) {
				$_ids = @maybe_unserialize( $result['categories_ids'] );
				if ( is_array( $_ids ) && count( $_ids ) ) {
					foreach ( $_ids as $id ) {
						$ids[] = $id;
					}
				}
			}
		}

		return count( $ids ) ? array_values( array_unique( $ids ) ) : [];
	}

	/**
	 * @inheritDoc
	 */
	public function count_records( $user_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		$query = "SELECT status, COUNT( * ) AS num_entries FROM {$table} WHERE {$this->deleted_at} IS NULL";
		if ( $user_id ) {
			$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $user_id ) );
		}
		$query   .= " GROUP BY `status`";
		$results = $wpdb->get_results( $query, ARRAY_A );

		$statuses = array_fill_keys( static::$valid_statuses, 0 );

		foreach ( $results as $status ) {
			if ( isset( $statuses[ $status['status'] ] ) ) {
				$statuses[ $status['status'] ] = intval( $status['num_entries'] );
			}
		}

		$statuses['all'] = array_sum( $statuses );


		// Trash count
		$query = "SELECT COUNT( * ) AS num_entries FROM {$table} WHERE {$this->deleted_at} IS NOT NULL";
		if ( $user_id ) {
			$query .= $wpdb->prepare( " AND {$this->created_by} = %d", intval( $user_id ) );
		}
		$results           = $wpdb->get_row( $query, ARRAY_A );
		$statuses['trash'] = intval( $results['num_entries'] );

		return $statuses;
	}

	/**
	 * Get valid categories ids
	 *
	 * @return array
	 */
	public static function get_valid_categories_ids() {
		$term_args  = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		);
		$term_query = new \WP_Term_Query( $term_args );

		return $term_query->get_terms();
	}

	/**
	 * Get valid tags ids
	 *
	 * @return array
	 */
	public static function get_valid_tags_ids() {
		$term_args  = array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
			'fields'     => 'ids',
		);
		$term_query = new \WP_Term_Query( $term_args );

		return $term_query->get_terms();
	}

	/**
	 * @param \WC_Order_Item_Product $product_item
	 *
	 * @return bool|string
	 */
	public static function get_order_item_card_size( \WC_Order_Item_Product $product_item ) {
		$settings = get_option( 'yousaiditcard_designers_settings' );
		if ( empty( $settings['product_attribute_for_card_sizes'] ) ) {
			return false;
		}
		$attr_name = $settings['product_attribute_for_card_sizes'];
		$taxonomy  = wc_attribute_taxonomy_name( $attr_name );
		$size      = $product_item->get_meta( $taxonomy, true );
		if ( empty( $size ) ) {
			return false;
		}

		return $size;
	}

	/**
	 * @param string $size
	 *
	 * @return bool|float
	 */
	public function get_commission_for_size( $size ) {
		$commission_data = $this->get_commission_data();
		$commission_type = $commission_data['commission_type'];
		if ( 'fix' != $commission_type ) {
			return false;
		}
		$commission_amount = $commission_data['commission_amount'];
		if ( isset( $commission_amount[ $size ] ) && is_numeric( $commission_amount[ $size ] ) ) {
			return floatval( $commission_amount[ $size ] );
		}

		return false;
	}

	/**
	 * @param \WC_Order_Item_Product $product_item
	 *
	 * @return float
	 */
	public function get_commission_for_order_item( \WC_Order_Item_Product $product_item ) {
		$size              = static::get_order_item_card_size( $product_item );
		$commission_amount = $this->get_commission_for_size( $size );
		if ( $commission_amount === false ) {
			return 0;
		}

		return floatval( $commission_amount * $product_item->get_quantity() );
	}

	/**
	 * Create table
	 */
	public function create_table() {
		global $wpdb;
		$table_name = $this->get_table_name( $this->table );
		$collate    = $wpdb->get_charset_collate();

		$tables = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			card_title TEXT NULL DEFAULT NULL,
			card_sizes TEXT NULL DEFAULT NULL,
			categories_ids TEXT NULL DEFAULT NULL,
			tags_ids TEXT NULL DEFAULT NULL,
			attachment_ids TEXT NULL DEFAULT NULL,
			attributes TEXT NULL DEFAULT NULL,
			total_sale TEXT NULL DEFAULT NULL,
			commission_per_sale TEXT NULL DEFAULT NULL,
			marketplace_commission TEXT NULL DEFAULT NULL,
			product_id bigint(20) NOT NULL DEFAULT '0',
			designer_user_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			status varchar(20) NOT NULL DEFAULT 'processing',
			rude_card varchar(5) NULL DEFAULT NULL,
			card_sku varchar(100) NULL DEFAULT NULL,
			suggest_tags TEXT NULL DEFAULT NULL,
			created_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NULL DEFAULT NULL,
			deleted_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $tables );

		$this->get_table_column();

		$option = get_option( $table_name . '-version', '1.0.0' );
		if ( version_compare( $option, '1.0.1', '<' ) ) {
			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `designer_user_id` (`designer_user_id`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE `{$table_name}` ADD INDEX `status` (`status`);";
			$wpdb->query( $sql );

			update_option( 'designer_cards_table_version', '1.0.1' );
		}
	}

	/**
	 * Add table column
	 */
	public function get_table_column() {
		global $wpdb;
		$table_name = $this->get_table_name();
		$option     = get_option( $table_name . '-version', '1.0.0' );
		if ( version_compare( $option, '1.0.4', '<' ) ) {
			$sql = "ALTER TABLE {$table_name} ADD `market_places` TEXT NULL DEFAULT NULL AFTER `suggest_tags`;";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE {$table_name} ADD `marketplace_commission` TEXT NULL DEFAULT NULL AFTER `commission_per_sale`;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.0.4' );
		}

		if ( version_compare( $option, '1.1.0', '<' ) ) {
			$sql = "ALTER TABLE {$table_name} ADD `card_type` VARCHAR(50) NOT NULL DEFAULT 'static' AFTER `id`, ADD INDEX `card_type` (`card_type`);";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE {$table_name} ADD `dynamic_card_payload` TEXT NULL DEFAULT NULL AFTER `card_type`;";
			$wpdb->query( $sql );

			update_option( $table_name . '-version', '1.1.0' );
		}
	}
}
