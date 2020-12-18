<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use WC_Product;
use WC_Product_Data_Store_CPT;
use WP_REST_Request;
use WP_REST_Server;
use WP_Term;
use YouSaidItCards\Modules\WooCommerce\ProductUtils;
use YouSaidItCards\Modules\WooCommerce\WcRestClient;
use YouSaidItCards\REST\ApiController;

class ProductController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', [ self::$instance, 'register_routes' ] );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, 'home', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_home_screen_items' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'show_rude_card' => [
					'description'       => __( 'Whether to show or hide rude cards.' ),
					'type'              => 'string',
					'default'           => 'yes',
					'validate_callback' => 'rest_validate_request_arg',
					'enum'              => [ 'yes', 'no' ],
				]
			],
		] );
		register_rest_route( $this->namespace, 'products', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => '__return_true',
			'args'                => $this->get_collection_params(),
		] );
		register_rest_route( $this->namespace, 'products/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_item' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function get_home_screen_items( WP_REST_Request $request ) {
		$show_rude_card = $request->get_param( 'show_rude_card' );

		$categories = ProductUtils::product_categories();
		$cats       = [];
		foreach ( $categories as $category ) {
			if ( $category->parent == 0 ) {
				if ( ! isset( $cats[ $category->term_id ] ) ) {
					$cats[ $category->term_id ] = [];
				}
			} else {
				$cats[ $category->parent ][] = intval( $category->term_id );
			}
		}

		$product_ids = [];
		foreach ( $cats as $parent_id => $term_ids ) {
			if ( ! ( is_array( $term_ids ) && count( $term_ids ) ) ) {
				continue;
			}
			$product_query = new \WC_Product_Query( ProductUtils::parse_args( [
				'return'     => 'ids',
				'limit'      => 10,
				'categories' => array_merge( [ intval( $parent_id ) ], $term_ids )
			] ) );
			$result        = $product_query->get_products();
			if ( is_array( $result ) && count( $result ) ) {
				$product_ids = array_merge( $product_ids, $result );
			}
		}
		$args = [
			'limit'   => count( $product_ids ),
			'include' => $product_ids
		];
		if ( $show_rude_card == 'no' ) {
			$args['show_rude_card'] = 'no';
		}
		$product_query  = new \WC_Product_Query( ProductUtils::parse_args( $args ) );
		$products       = $product_query->get_products();
		$products_array = [];
		/** @var WC_Product $product */
		foreach ( $products as $product ) {
			$products_array[] = ProductUtils::format_product_for_response( $product );
		}

		return $this->respondOK( [
			'products'   => $products_array,
			'categories' => static::format_categories_collection( $categories ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$page           = (int) $request->get_param( 'page' );
		$per_page       = (int) $request->get_param( 'per_page' );
		$search         = $request->get_param( 'search' );
		$show_rude_card = $request->get_param( 'show_rude_card' );
		$categories     = $request->get_param( 'categories' );
		$tags           = $request->get_param( 'tags' );
		$sort           = $request->get_param( 'sort' );

		$args = [
			'paginate' => true,
			'limit'    => $per_page,
			'page'     => $page,
		];
		if ( $show_rude_card == 'no' ) {
			$args['show_rude_card'] = 'no';
		}
		if ( is_array( $categories ) && count( $categories ) ) {
			$args['categories'] = $categories;
		}
		if ( is_array( $tags ) && count( $tags ) ) {
			$args['tags'] = $tags;
		}
		if ( ! empty( $search ) && is_scalar( $search ) ) {
			$products_ids    = ( new WC_Product_Data_Store_CPT )->search_products( $search, '', true );
			$args['include'] = array_merge( $products_ids, array( 0 ) );
		}

		$product_query = ProductUtils::get_products( $args );
		$response      = [
			'items'      => $product_query['products'],
			'pagination' => static::get_pagination_data( $product_query['total_items'], $per_page, $page ),
		];

		return $this->respondOK( $response );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		$client  = new WcRestClient();
		$product = $client->list_product( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $product ) ) {
			return $this->respondWithError( $product );
		}

		return $this->respondOK( $product );
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params(): array {
		$params               = parent::get_collection_params();
		$params['categories'] = [
			'description'       => __( 'List of categories id separated by coma or as an array.' ),
			'type'              => [ 'array', 'string' ],
			'default'           => [],
			'sanitize_callback' => [ $this, 'sanitize_ids' ],
			'validate_callback' => 'rest_validate_request_arg',
		];
		$params['tags']       = [
			'description'       => __( 'List of tags id separated by coma or as an array.' ),
			'type'              => [ 'array', 'string' ],
			'default'           => [],
			'sanitize_callback' => [ $this, 'sanitize_ids' ],
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['show_rude_card'] = [
			'description'       => __( 'Whether to show or hide rude cards.' ),
			'type'              => 'string',
			'default'           => 'yes',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => [ 'yes', 'no' ],
		];

		return $params;
	}

	/**
	 * Sanitize value
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public function sanitize_ids( $value ): array {
		if ( empty( $value ) ) {
			return [];
		}

		if ( is_string( $value ) ) {
			$value = array_filter( explode( ',', $value ) );
		}

		return ( is_array( $value ) && count( $value ) ) ? array_map( 'intval', $value ) : [];
	}

	/**
	 * @param WP_Term[] $categories
	 *
	 * @return array
	 */
	public static function format_categories_collection( array $categories ): array {
		$data = [];
		foreach ( $categories as $category ) {
			$thumbnail_id = absint( get_term_meta( $category->term_id, 'thumbnail_id', true ) );
			$image        = ProductUtils::format_image_for_response( $thumbnail_id );

			$data[] = [
				'id'          => $category->term_id,
				'slug'        => $category->slug,
				'name'        => $category->name,
				'description' => $category->description,
				'count'       => $category->count,
				'image'       => $image,
				'parent'      => $category->parent,
			];
		}

		return $data;
	}

}
