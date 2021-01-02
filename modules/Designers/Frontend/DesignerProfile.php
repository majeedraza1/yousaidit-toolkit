<?php

namespace YouSaidItCards\Modules\Designers\Frontend;

use WC_Product;
use WP_Post;
use WP_Term;
use WP_User;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Session\Session;
use YouSaidItCards\Utilities\MarketPlace;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

class DesignerProfile {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_shortcode( 'designer_profile_page', [ self::$instance, 'profile_page' ] );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'designer_profile_scripts' ] );

			add_filter( 'template_include', [ self::$instance, 'template_include' ] );
			add_action( 'designer_cards', [ self::$instance, 'designer_cards' ] );
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query',
				[ self::$instance, 'handle_custom_query_var' ], 10, 2 );

			/**
			 * @see https://github.com/stackonet/stackonet-yousaidit-toolkit/issues/29
			 */
			// add_action( 'woocommerce_after_shop_loop_item_title', [ self::$instance, 'show_designer_name' ], 99 );
			add_action( 'woocommerce_single_product_summary', [ self::$instance, 'show_designer_name' ], 99 );
		}

		return self::$instance;
	}

	/**
	 * Show designer name
	 */
	public function show_designer_name() {
		/** @var WC_Product $product */
		global $product;
		$designer_id = $product->get_meta( '_card_designer_id', true );
		if ( is_numeric( $designer_id ) ) {
			$designer = new CardDesigner( $designer_id );
			echo $designer->get_profile_link_card();
		}
	}

	/**
	 * @param array $query
	 * @param array $query_vars
	 *
	 * @return mixed
	 */
	public function handle_custom_query_var( $query, $query_vars ) {
		if ( ! empty( $query_vars['designer_id'] ) ) {
			$query['meta_query'][] = array(
				'key'   => '_card_designer_id',
				'value' => esc_attr( $query_vars['designer_id'] ),
			);
		}

		$session        = Session::get_instance();
		$show_rude_card = $session->get( 'show_rude_card' );
		if ( 'no' == $show_rude_card ) {
			$query['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_is_rude_card',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_is_rude_card',
					'value'   => 'yes',
					'compare' => '!=',
				)
			);
		}

		if ( ! empty( $query_vars['designer_tax_query'] ) ) {
			$tax_query = [ 'relation' => 'AND' ];
			foreach ( $query_vars['designer_tax_query'] as $taxonomy => $taxonomy_slug ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => [ $taxonomy_slug ],
				];
			}

			$query['tax_query'][] = $tax_query;
		}

		return $query;
	}

	/**
	 * Designer profile page content
	 */
	public function designer_cards() {
		/** @var WP_User $author */
		$author   = get_queried_object();
		$filters  = DesignerCustomerProfile::get_tax_query_data();
		$products = CardDesigner::get_products( $author->ID, $filters );
		if ( is_array( $products ) && count( $products ) ) {
			echo do_shortcode( "[products ids='" . implode( ',', $products ) . "']" );
		} else {
			echo '<p class="no-item-found" style="text-align: center;font-size: 2rem;">No card available.</p>';
		}
	}


	public static function product_loop( $ids = [] ) {
		ob_start();
		// Setup the loop.
		wc_setup_loop();

		$original_post = $GLOBALS['post'];

		// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
		do_action( 'woocommerce_before_shop_loop' );

		woocommerce_product_loop_start();

		if ( wc_get_loop_prop( 'total' ) ) {
			foreach ( $ids as $product_id ) {
				$GLOBALS['post'] = get_post( $product_id ); // WPCS: override ok.
				setup_postdata( $GLOBALS['post'] );

				// Render product template.
				wc_get_template_part( 'content', 'product' );
			}
		}

		$GLOBALS['post'] = $original_post; // WPCS: override ok.
		woocommerce_product_loop_end();

		// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
		do_action( 'woocommerce_after_shop_loop' );

		wp_reset_postdata();
		wc_reset_loop();

		return ob_get_clean();
	}

	/**
	 * @param $template
	 *
	 * @return string
	 */
	public function template_include( $template ) {
		if ( is_archive() && is_author() ) {
			$template = YOUSAIDIT_TOOLKIT_PATH . '/templates/author.php';
		}

		return $template;
	}

	/**
	 * Load designer scripts
	 */
	public function designer_profile_scripts() {
		global $post;
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'designer_profile_page' ) ) {
			wp_enqueue_style( 'stackonet-designer-profile' );
			wp_enqueue_script( 'stackonet-designer-profile' );
		}
	}

	protected static function add_product_attributes( array &$data ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$options              = (array) get_option( 'yousaiditcard_designers_settings' );
		$attributes           = isset( $options['product_attribute_taxonomies'] ) ? $options['product_attribute_taxonomies'] : [];

		foreach ( $attribute_taxonomies as $tax ) {
			if ( ! in_array( $tax->attribute_name, $attributes ) ) {
				continue;
			}

			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
			/** @var WP_Term[] $terms */
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, ] );

			$options = [];
			foreach ( $terms as $term ) {
				$options[] = [
					'id'   => $term->term_id,
					'name' => $term->name,
				];
			}

			$data['attributes'][] = [
				'attribute_name'  => $tax->attribute_name,
				'attribute_label' => esc_html( $tax->attribute_label ),
				'options'         => $options,
			];
		}
	}

	/**
	 * @param array $data
	 */
	protected static function add_product_categories( array &$data ) {
		/** @var WP_Term[] $cats */
		$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false, ] );

		foreach ( $cats as $cat ) {
			if ( 'Uncategorized' == $cat->name ) {
				continue;
			}
			$data['categories'][] = [ 'id' => $cat->term_id, 'name' => $cat->name, 'parent' => $cat->parent, ];
		}
	}

	/**
	 * @param array $data
	 */
	protected static function add_product_tags( array &$data ) {
		/** @var WP_Term[] $cats */
		$cats = get_terms( [ 'taxonomy' => 'product_tag', 'hide_empty' => false, ] );

		foreach ( $cats as $cat ) {
			if ( 'Uncategorized' == $cat->name ) {
				continue;
			}
			$data['tags'][] = [ 'id' => $cat->term_id, 'name' => $cat->name, ];
		}
	}

	/**
	 * @param array $data
	 */
	private static function add_card_sizes( array &$data ) {
		$options        = (array) get_option( 'yousaiditcard_designers_settings' );
		$attribute_name = isset( $options['product_attribute_for_card_sizes'] ) ? $options['product_attribute_for_card_sizes'] : '';

		if ( empty( $attribute_name ) ) {
			$data['card_sizes'] = [];

			return;
		}

		$taxonomy = wc_attribute_taxonomy_name( $attribute_name );
		/** @var WP_Term[] $terms */
		$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, ] );

		foreach ( $terms as $term ) {
			$data['card_sizes'][] = [
				'term_id' => $term->term_id,
				'slug'    => $term->slug,
				'name'    => $term->name,
			];
		}
	}

	/**
	 * @return string
	 */
	public function profile_page() {
		$current_user = wp_get_current_user();

		add_action( 'wp_footer', [ $this, 'add_inline_scripts' ], 5 );

		if ( ! $current_user->exists() ) {
			return '<div id="designer_profile_page_need_login">You need to login to view this page.</div>';
		}


		return '<div id="designer_profile_page"></div>';
	}

	/**
	 * Add inline scripts
	 *
	 * @param null|WP_User $current_user
	 */
	public static function add_inline_scripts( $current_user = null ) {
		if ( ! $current_user instanceof WP_User ) {
			$current_user = wp_get_current_user();
		}

		$data = [
			'siteTitle'        => get_option( 'blogname' ),
			'logoUrl'          => '',
			'categories'       => [],
			'tags'             => [],
			'cards'            => [],
			'attributes'       => [],
			'privacyPolicyUrl' => get_privacy_policy_url(),
			'restRoot'         => esc_url_raw( rest_url( 'stackonet/v1' ) ),
		];

		$options = (array) get_option( 'yousaiditcard_designers_settings' );
		if ( isset( $options['terms_page_id'] ) && is_numeric( $options['terms_page_id'] ) ) {
			$data['termsUrl'] = get_page_link( $options['terms_page_id'] );
		}
		if ( isset( $options['designer_dashboard_logo_id'] ) && is_numeric( $options['designer_dashboard_logo_id'] ) ) {
			$src = wp_get_attachment_image_src( $options['designer_dashboard_logo_id'], 'full' );
			if ( isset( $src[0] ) && filter_var( $src[0], FILTER_VALIDATE_URL ) ) {
				$data['logoUrl'] = $src[0];
			}
		}

		if ( ! $current_user->exists() ) {
			$data['lostPasswordUrl'] = wp_lostpassword_url();
		}

		if ( $current_user->exists() ) {
			$data['restNonce'] = wp_create_nonce( 'wp_rest' );

			$data['logOutUrl'] = wp_logout_url( get_permalink() );

			$designer = new CardDesigner( $current_user );

			$data['user'] = [
				'id'               => $current_user->ID,
				'display_name'     => $current_user->display_name,
				'avatar_url'       => $designer->get_avatar_url(),
				'author_posts_url' => $designer->get_products_url(),
			];

			self::add_card_sizes( $data );
			self::add_product_categories( $data );
			self::add_product_tags( $data );
			self::add_product_attributes( $data );

			$data['user_card_categories'] = ( new DesignerCard() )->get_user_cards_categories_ids( $current_user->ID );
			$data['order_statuses']       = wc_get_order_statuses();
			$data['marketPlaces']         = MarketPlace::all();
		}

		echo '<script type="text/javascript">window.DesignerProfile = ' . wp_json_encode( $data ) . '</script>' . PHP_EOL;
	}
}
