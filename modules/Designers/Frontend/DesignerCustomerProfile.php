<?php

namespace YouSaidItCards\Modules\Designers\Frontend;

use YouSaidItCards\Modules\Designers\Models\CardDesigner;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

class DesignerCustomerProfile {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	protected static $endpoint;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			static::$endpoint = CardDesigner::PROFILE_ENDPOINT;

			add_action( 'init', [ self::$instance, 'custom_rewrite_rule' ], 10, 0 );
			add_filter( 'query_vars', [ self::$instance, 'add_query_var' ] );
			add_action( 'template_redirect', [ self::$instance, 'template_redirect' ] );
			add_filter( 'document_title_parts', [ self::$instance, 'document_title' ], 10 );
			add_action( 'pre_get_posts', [ self::$instance, 'pre_get_posts' ] );
			add_filter( 'woocommerce_product_categories_widget_dropdown_args',
				[ self::$instance, 'categories_widget_dropdown_args' ] );
			add_filter( 'woocommerce_widget_get_current_page_url', [ self::$instance, 'change_close_link_url' ] );
			add_filter( 'get_terms', [ self::$instance, 'get_terms' ] );
		}

		return self::$instance;
	}

	/**
	 * @param \WP_Term[] $terms
	 *
	 * @return \WP_Term[]
	 */
	public function get_terms( $terms ) {
		global $wp_query;
		$designer = $wp_query->get( static::$endpoint );
		if ( empty( $designer ) ) {
			return $terms;
		}

		$user = get_user_by( 'login', $designer );
		if ( ! $user instanceof \WP_User ) {
			return $terms;
		}

		$filters  = static::get_tax_query_data();
		$includes = CardDesigner::find_terms( $user->ID, $filters );
		if ( count( $includes ) < 1 ) {
			return $terms;
		}

		$modified_terms = [];
		foreach ( $terms as $term ) {
			if ( ! in_array( $term->term_id, $includes ) ) {
				continue;
			}

			$modified_terms[] = $term;
		}

		return $modified_terms;
	}

	/**
	 * @param string $link
	 *
	 * @return string
	 */
	public static function change_close_link_url( $link ) {
		global $wp_query;
		if ( ! empty( $wp_query->get( static::$endpoint ) ) ) {
			$url         = wp_parse_url( $link );
			$url['path'] = '/' . static::$endpoint . '/' . $wp_query->get( static::$endpoint ) . '/';
			$link        = static::unparse_url( $url );
		}

		return $link;
	}

	/**
	 * Un parse URL
	 *
	 * @param array $parsed_url
	 *
	 * @return string
	 */
	public static function unparse_url( array $parsed_url ) {
		$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
		$pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass     = ( $user || $pass ) ? "$pass@" : '';
		$path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
		$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	/**
	 * @return array
	 */
	public static function get_tax_query_data() {
		$filters = [];
		foreach ( $_GET as $key => $value ) {
			if ( ( false !== strpos( $key, 'filter_' ) || 'product_cat' == $key ) && ( $value != 'any' || ! empty( $value ) ) ) {
				$attr_key             = str_replace( 'filter_', 'pa_', $key );
				$filters[ $attr_key ] = sanitize_text_field( $value );
			}
		}

		return $filters;
	}

	/**
	 * Get categories widget dropdown args
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function categories_widget_dropdown_args( $args ) {
		global $wp_query;
		$designer = $wp_query->get( self::$endpoint );
		if ( ! empty( $designer ) ) {
			$args['class'] = 'designer_dropdown_product_cat';
			if ( isset( $args['selected'] ) ) {
				unset( $args['selected'] );
			}
		}

		return $args;
	}

	/**
	 * @param \WP_Query $wp_query
	 */
	public function pre_get_posts( $wp_query ) {
		// We only want to affect the main query.
		if ( ! $wp_query->is_main_query() ) {
			return;
		}

		$designer = $wp_query->get( self::$endpoint );
		if ( empty( $designer ) ) {
			return;
		}

		$user = false;
		if ( is_string( $designer ) ) {
			$user = get_user_by( 'login', $designer );
		}

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$filters      = static::get_tax_query_data();
		$products_ids = CardDesigner::get_products( $user->ID, $filters );

		$wp_query->set( 'post_type', 'product' );
		$wp_query->set( 'page_id', '' );

		$wp_query->is_singular          = false;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_page              = true;
		$wp_query->set( 'wc_query', 'product_query' );
		$wp_query->set( 'post__in', $products_ids );
	}

	/**
	 * Add custom rewrite rule
	 */
	public static function custom_rewrite_rule() {
		add_rewrite_rule( '^' . static::$endpoint . '/?([^/]*)/?', 'index.php?' . static::$endpoint . '=$matches[1]', 'top' );
	}

	/**
	 * Add query var
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function add_query_var( array $vars ) {
		$vars[] = self::$endpoint;

		return $vars;
	}

	/**
	 * Change document title for designer customer profile page
	 *
	 * @param array $title
	 *
	 * @return array
	 */
	public function document_title( $title ) {
		global $wp_query;
		if ( ! empty( $wp_query->get( self::$endpoint ) ) ) {
			$author = get_queried_object();
			if ( $author instanceof \WP_User ) {
				$title['title'] = $author->display_name;
			}
		}


		return $title;
	}

	/**
	 * Include author template
	 */
	public function template_redirect() {
		global $wp_query;

		$designer = $wp_query->get( self::$endpoint );

		// if this is not a request for designer
		if ( empty( $designer ) ) {
			return;
		}

		$user = false;
		if ( is_string( $designer ) ) {
			$user = get_user_by( 'login', $designer );
		}
		if ( is_numeric( $designer ) ) {
			$user = get_user_by( 'id', intval( $designer ) );
		}

		if ( ! $user instanceof \WP_User ) {
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}

		$wp_query->queried_object = $user;
		add_filter( 'woocommerce_enqueue_styles', '__return_false' );

		include YOUSAIDIT_TOOLKIT_PATH . '/templates/author.php';
		exit;
	}
}
