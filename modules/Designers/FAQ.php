<?php

namespace YouSaidItCards\Modules\Designers;

use JsonSerializable;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class FAQ implements JsonSerializable {
	/**
	 * Post type name
	 *
	 * @var string
	 */
	const POST_TYPE = 'developer-faqs';

	/**
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Meta data
	 * @var array
	 */
	protected $meta_data = [
	];

	/**
	 * SpecialAnnouncement constructor.
	 *
	 * @param null|WP_Post $post
	 */
	public function __construct( $post = null ) {
		$post = get_post( $post );
		if ( $post->post_type == static::POST_TYPE ) {
			$this->post = $post;
		}
	}

	/**
	 * @return array
	 */
	public function to_array() {
		return [
			'id'      => $this->get_id(),
			'title'   => $this->get_title(),
			'content' => $this->get_content(),
		];
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return intval( $this->post->ID );
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->post->ID );
	}

	/**
	 * @return string
	 */
	public function get_content() {
		return apply_filters( 'the_content', $this->post->post_content );
	}

	/**
	 * @param array $args
	 *
	 * @return WP_Query
	 */
	public static function find( array $args = [] ) {
		$args = wp_parse_args( $args, array(
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
		) );

		$args['post_type'] = static::POST_TYPE;

		return new WP_Query( $args );
	}

	/**
	 * Find by id
	 *
	 * @param int $id
	 *
	 * @return bool|static
	 */
	public static function find_by_id( $id ) {
		$post = get_post( $id );
		if ( $post->post_type == static::POST_TYPE ) {
			return new static( $post );
		}

		return false;
	}

	/**
	 * Get post type args
	 *
	 * @return array
	 */
	public static function get_post_type_args() {
		$labels = array(
			'name'                  => _x( 'Posts', 'Post Type General Name', 'stackonet-yousaidit-toolkit' ),
			'singular_name'         => _x( 'Post', 'Post Type Singular Name', 'stackonet-yousaidit-toolkit' ),
			'menu_name'             => __( 'Designer FAQs', 'stackonet-yousaidit-toolkit' ),
			'name_admin_bar'        => __( 'Designer FAQs', 'stackonet-yousaidit-toolkit' ),
			'archives'              => __( 'Post Archives', 'stackonet-yousaidit-toolkit' ),
			'attributes'            => __( 'Post Attributes', 'stackonet-yousaidit-toolkit' ),
			'parent_item_colon'     => __( 'Parent Post:', 'stackonet-yousaidit-toolkit' ),
			'all_items'             => __( 'All Posts', 'stackonet-yousaidit-toolkit' ),
			'add_new_item'          => __( 'Add New Post', 'stackonet-yousaidit-toolkit' ),
			'add_new'               => __( 'Add New', 'stackonet-yousaidit-toolkit' ),
			'new_item'              => __( 'New Post', 'stackonet-yousaidit-toolkit' ),
			'edit_item'             => __( 'Edit Post', 'stackonet-yousaidit-toolkit' ),
			'update_item'           => __( 'Update Post', 'stackonet-yousaidit-toolkit' ),
			'view_item'             => __( 'View Post', 'stackonet-yousaidit-toolkit' ),
			'view_items'            => __( 'View Posts', 'stackonet-yousaidit-toolkit' ),
			'search_items'          => __( 'Search Post', 'stackonet-yousaidit-toolkit' ),
			'not_found'             => __( 'Not found', 'stackonet-yousaidit-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'stackonet-yousaidit-toolkit' ),
			'items_list'            => __( 'Posts list', 'stackonet-yousaidit-toolkit' ),
			'items_list_navigation' => __( 'Posts list navigation', 'stackonet-yousaidit-toolkit' ),
			'filter_items_list'     => __( 'Filter post list', 'stackonet-yousaidit-toolkit' ),
		);
		$args   = array(
			'label'               => __( 'Post', 'stackonet-yousaidit-toolkit' ),
			'description'         => __( 'A list of FAQs', 'stackonet-yousaidit-toolkit' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-media-document',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
