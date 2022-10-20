<?php

namespace YouSaidItCards\Modules\Reminders\Models;

use Stackonet\WP\Framework\Abstracts\DatabaseModel;

class ReminderGroup extends DatabaseModel {
	protected $table = 'reminder_groups';

	public function to_array(): array {
		return [
			'id'                   => (int) $this->get( 'id' ),
			'title'                => $this->get( 'title' ),
			'product_categories'   => $this->get_product_categories_ids(),
			'primary_category_url' => $this->get_first_category_url(),
			'cta_link'             => $this->get_cta_link(),
			'email_template_url'   => $this->get_email_template_url(),
			'menu_order'           => (int) $this->get( 'menu_order' ),
			'created_at'           => mysql_to_rfc3339( $this->get( 'created_at' ) ),
			'updated_at'           => mysql_to_rfc3339( $this->get( 'updated_at' ) ),
			'occasion_date'        => $this->get( 'occasion_date' ),
		];
	}

	/**
	 * Get product categories
	 *
	 * @return array
	 */
	public function get_product_categories_ids(): array {
		$product_categories = $this->get( 'product_categories' );
		if ( is_string( $product_categories ) ) {
			$product_categories = explode( ',', $product_categories );
		}
		if ( is_array( $product_categories ) ) {
			$product_categories = array_filter( $product_categories );
			$product_categories = count( $product_categories ) ? array_map( 'intval', $product_categories ) : [];
		}

		return $product_categories;
	}

	/**
	 * @return array[]
	 */
	public function get_products(): array {
		$cat_ids   = $this->get_product_categories_ids();
		$cache_key = 'reminder_group_products_' . md5( wp_json_encode( $cat_ids ) );
		$data      = wp_cache_get( $cache_key, $this->get_cache_group() );
		if ( false === $data ) {
			$data  = [];
			$terms = get_terms(
				[
					'taxonomy' => 'product_cat',
					'include'  => $cat_ids,
				]
			);
			foreach ( $terms as $term ) {
				$products = wc_get_products( [
					'category'       => $term->slug,
					'limit'          => 1,
					'show_rude_card' => 'no',
					'orderby'        => 'rand',
				] );
				/** @var \WC_Product $product */
				$product             = $products[0];
				$data[ $term->slug ] = [
					'category' => $term,
					'product'  => $product,
				];
			}
//			set_transient( $cache_key, $data, DAY_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Get first category link
	 *
	 * @return string
	 */
	public function get_first_category_url(): string {
		$product_categories = $this->get_product_categories_ids();
		if ( ! $product_categories ) {
			return '';
		}

		$first_category = get_term( $product_categories[0] );
		if ( ! $first_category ) {
			return '';
		}

		return get_term_link( $first_category );
	}

	/**
	 * Get CTA link
	 *
	 * @return string
	 */
	public function get_cta_link(): string {
		return $this->get( 'cta_link' );
	}

	/**
	 * Get email template
	 *
	 * @return string
	 */
	public function get_email_template_url(): string {
		return add_query_arg( [
			'action'   => 'reminder_email_template',
			'group_id' => $this->get( 'id' )
		], admin_url( 'admin-ajax.php' ) );
	}


	/**
	 * Format term slug
	 *
	 * @param array $tags List of term slug or term id.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	public static function format_term_slug( array $tags, string $taxonomy ): array {
		$ids = [];
		foreach ( $tags as $index => $tag ) {
			if ( is_numeric( $tag ) ) {
				$ids[] = intval( $tag );
				unset( $tags[ $index ] );
			}
		}
		if ( count( $ids ) ) {
			$terms = get_terms(
				[
					'taxonomy' => $taxonomy,
					'include'  => $ids,
				]
			);
			$slugs = is_array( $terms ) ? wp_list_pluck( $terms, 'slug' ) : [];
			$tags  = array_merge( $slugs, array_values( $tags ) );
		}

		return $tags;
	}

	public static function create_table() {
		global $wpdb;
		$self       = new static();
		$table_name = $self->get_table_name();
		$collate    = $wpdb->get_charset_collate();

		$table_schema = "CREATE TABLE IF NOT EXISTS {$table_name} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Group title',
                `product_categories` TEXT NULL DEFAULT NULL,
                `cta_link` TEXT NULL DEFAULT NULL,
                `menu_order` int NOT NULL DEFAULT '0',
                `occasion_date` date DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) $collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $table_schema );
	}
}
