<?php

namespace YouSaidItCards\Modules\Designers\Models;

use JsonSerializable;
use WC_Product_Query;
use WP_Role;
use WP_User;
use WP_User_Query;

defined( 'ABSPATH' ) || exit;

class CardDesigner implements JsonSerializable {

	/**
	 * Card designer role
	 */
	const ROLE = 'card_designer';

	/**
	 * Card designer capabilities
	 */
	const CAPABILITIES = [
		'read' => true,
	];

	/**
	 * Profile endpoint for customer
	 */
	const PROFILE_ENDPOINT = 'designer';

	/**
	 * @var int
	 */
	protected static $found_users = 0;

	/**
	 * @var array
	 */
	protected static $terms_ids = [];

	protected static $terms_ids_read = false;

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Check if card data has been read
	 *
	 * @var bool
	 */
	protected $card_read = false;

	/**
	 * Designer cards
	 *
	 * @var DesignerCard[]
	 */
	protected $cards = [];

	/**
	 * Check if card count data has been read
	 *
	 * @var bool
	 */
	protected $cards_count_read = false;

	/**
	 * @var array
	 */
	protected $cards_count = [];

	/**
	 * Meta data
	 *
	 * @var array
	 */
	protected $meta_data = [
		'paypal_email'               => '',
		'location'                   => '',
		'business_name'              => '',
		'vat_registration_number'    => '',
		'vat_certificate_issue_date' => '',
		'avatar_id'                  => 0,
		'cover_photo_id'             => 0,
		'business_address'           => [],
	];

	/**
	 * @var array
	 */
	protected $business_address = [
		'address_1' => '',
		'address_2' => '',
		'city'      => '',
		'post_code' => '',
		'country'   => '',
		'state'     => '',
	];

	/**
	 * CardDesigner constructor.
	 *
	 * @param WP_User|int|null $user
	 */
	public function __construct( $user = null ) {
		if ( $user instanceof WP_User ) {
			$this->user = $user;
		} elseif ( is_numeric( $user ) ) {
			$this->user = get_user_by( 'id', $user );
		} else {
			$this->user = wp_get_current_user();
		}

		$this->read_cards_count();
	}

	/**
	 * @param int $designer_id
	 * @param array $filters
	 *
	 * @return array List of product ids
	 */
	public static function get_products( $designer_id, array $filters = [] ) {
		$query = new WC_Product_Query();
		$query->set( 'status', 'publish' );
		$query->set( 'limit', - 1 );
		$query->set( 'orderby', 'ID' );
		$query->set( 'order', 'DESC' );
		$query->set( 'return', 'ids' );
		$query->set( 'visibility', 'visible' );
		$query->set( 'designer_id', $designer_id );

		if ( count( $filters ) ) {
			$query->set( 'designer_tax_query', $filters );
		}

		return $query->get_products();
	}

	public static function find_terms( $designer_id, array $filters = [] ) {
		if ( static::$terms_ids_read ) {
			return static::$terms_ids;
		}

		$product_ids            = static::get_products( $designer_id, $filters );
		static::$terms_ids_read = true;

		if ( count( $product_ids ) ) {
			global $wpdb;
			$ids       = array_map( 'intval', $product_ids );
			$sql       = "SELECT `term_taxonomy_id` FROM `{$wpdb->term_relationships}` WHERE `object_id` IN(" . implode( ", ", $ids ) . ")";
			$results   = $wpdb->get_results( $sql, ARRAY_A );
			$terms_ids = wp_list_pluck( $results, 'term_taxonomy_id' );
			$terms_ids = array_unique( array_map( 'intval', $terms_ids ) );

			static::$terms_ids = array_values( $terms_ids );
		}

		return static::$terms_ids;
	}

	/**
	 * Get array representation of current class
	 *
	 * @return array
	 */
	public function to_array() {
		$user = $this->get_user();

		$data = [
			'id'               => $user->ID,
			'email'            => $user->user_email,
			'display_name'     => $user->display_name,
			'first_name'       => $user->first_name,
			'last_name'        => $user->last_name,
			'description'      => $user->description,
			'user_url'         => $user->user_url,
			'user_login'       => $user->user_login,
			'avatar_url'       => $this->get_avatar_url(),
			'cover_photo_url'  => $this->get_cover_photo_url(),
			'total_cards'      => $this->get_total_cards_count(),
			'profile_base_url' => $this->get_profile_base_url(),
			'total_sales'      => 0,
		];

		$cards = $this->get_user_cards();
		if ( count( $cards ) ) {
			foreach ( $cards as $card ) {
				$data['total_sales'] += $card->get_all_sizes_total_sales();
			}
		}

		foreach ( $this->meta_data as $key => $value ) {
			$data[ $key ] = get_user_meta( $user->ID, '_' . $key, true );
		}

		$data['business_address']  = is_array( $data['business_address'] ) ? $data['business_address'] : $this->business_address;
		$data['formatted_address'] = '';

		if ( defined( 'WC_PLUGIN_FILE' ) ) {
			$address = WC()->countries->get_formatted_address( $data['business_address'] );

			$data['formatted_address'] = str_replace( '<br/>', ', ', $address );
		}

		return $data;
	}

	/**
	 * @return WP_User
	 */
	public function get_user() {
		return $this->user;
	}

	/**
	 * Get user Id
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_user()->ID;
	}

	/**
	 * @return string
	 */
	public function get_paypal_email() {
		$paypal_email = get_user_meta( $this->get_user_id(), '_paypal_email', true );

		return is_email( $paypal_email ) ? $paypal_email : '';
	}

	/**
	 * Get social share url
	 *
	 * @return array
	 */
	public function get_social_share_url() {
		$permalink = $this->get_products_url();
		$title     = wp_get_document_title();

		return [
			'facebook' => add_query_arg( array( 'u' => $permalink ), 'https://www.facebook.com/sharer/sharer.php' ),
			'twitter'  => add_query_arg( array( 'url' => $permalink, 'text' => $title ),
				'https://twitter.com/intent/tweet' ),
			'mailto'   => add_query_arg( array( 'subject' => $title, 'body' => $permalink ), 'mailto:' ),
		];
	}

	/**
	 * Get avatar url
	 *
	 * @param int $size
	 *
	 * @return string
	 */
	public function get_avatar_url( $size = 256 ) {
		$default   = get_avatar_url( $this->get_user_id(), [ 'size' => $size, 'default' => 'mystery' ] );
		$avatar_id = (int) get_user_meta( $this->get_user_id(), '_avatar_id', true );
		if ( ! $avatar_id ) {
			return $default;
		}

		$src = wp_get_attachment_image_src( $avatar_id, [ $size, $size ] );
		if ( ! is_array( $src ) ) {
			return $default;
		}

		return $src[0];
	}

	/**
	 * Get location
	 *
	 * @return string
	 */
	public function get_location() {
		$location = get_user_meta( $this->get_user_id(), '_location', true );

		return ! empty( $location ) ? $location : '';
	}

	/**
	 * @return string
	 */
	public function get_cover_photo_url() {
		$id  = (int) get_user_meta( $this->get_user_id(), '_cover_photo_id', true );
		$src = wp_get_attachment_image_src( $id, 'full' );

		if ( ! is_array( $src ) ) {
			return '';
		}

		return $src[0];
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function find( array $args = [] ) {

		$args['role'] = [ static::ROLE ];

		$user_search = new WP_User_Query( $args );

		$users               = (array) $user_search->get_results();
		static::$found_users = $user_search->get_total();

		$items = [];
		foreach ( $users as $user ) {
			$items[] = new self( $user );
		}

		return $items;
	}

	/**
	 * @param int $user_id
	 * @param array $data
	 *
	 * @return int|\WP_Error
	 */
	public function update( $user_id, $data ) {
		$data['ID'] = $user_id;

		return wp_update_user( $data );
	}

	/**
	 * Update user meta
	 *
	 * @param int $user_id
	 * @param array $data
	 */
	public function update_meta_data( $user_id, array $data ) {
		foreach ( $data as $key => $value ) {
			if ( array_key_exists( $key, $this->meta_data ) ) {
				if ( $key == 'business_address' ) {
					if ( ! is_array( $value ) ) {
						continue;
					}
					$_value = [];
					foreach ( $this->business_address as $field => $field_value ) {
						$_value[ $field ] = isset( $value[ $field ] ) ? sanitize_text_field( $value[ $field ] ) : '';
					}
					update_user_meta( $user_id, '_' . $key, $_value );
					continue;
				}
				update_user_meta( $user_id, '_' . $key, $value );
			}
		}
	}

	/**
	 * Read user card
	 *
	 * @param array $args
	 *
	 * @return array|DesignerCard[]
	 */
	public function get_user_cards( array $args = [] ) {
		if ( ! $this->card_read ) {
			$args['designer_user_id'] = $this->get_user_id();

			$this->cards     = ( new DesignerCard() )->find( $args );
			$this->card_read = true;
		}

		return $this->cards;
	}

	/**
	 * Read cards count data
	 */
	public function read_cards_count() {
		if ( ! $this->cards_count_read ) {
			$this->cards_count      = ( new DesignerCard() )->count_records( $this->get_user_id() );
			$this->cards_count_read = true;
		}
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Add role if not exists
	 */
	public static function add_role_if_not_exists() {
		$role_name = static::ROLE;

		$role = get_role( $role_name );
		if ( ! $role instanceof WP_Role ) {
			add_role( $role_name, __( 'Card Designer', 'stackonet-yousaidit-toolkit' ), static::CAPABILITIES );
		}
	}

	/**
	 * Get designer cards count
	 *
	 * @return array
	 */
	public function get_cards_count() {
		return $this->cards_count;
	}

	/**
	 * Get designer cards count
	 *
	 * @return array
	 */
	public function get_total_cards_count() {
		return $this->cards_count['all'];
	}

	/**
	 * Get products url
	 *
	 * @return string
	 */
	public function get_products_url() {
		return site_url( static::PROFILE_ENDPOINT . '/' . $this->get_user()->user_login );
	}

	/**
	 * @return string|void
	 */
	public function get_profile_base_url() {
		return site_url( static::PROFILE_ENDPOINT );
	}

	/**
	 * Get designer profile link url
	 *
	 * @return string
	 */
	public function get_profile_link_card(): string {
		$url  = $this->get_products_url();
		$html = '<a class="yousaidit-card yousaidit-card--creator" href="' . esc_url( $url ) . '">';
		$html .= '<span class="yousaidit-card__author">';
		$html .= "<span>" . __( 'Designed by', 'yousaidit-toolkit' ) . "</span>";
		$html .= "<span>" . $this->get_user()->display_name . "</span>";
		$html .= '</span>';
		$html .= '<span class="yousaidit-card__avatar shapla-image-container has-rounded-image">';
		$html .= "<img class='yousaidit-card__avatar-image' src='" . $this->get_avatar_url() . "' />";
		$html .= '</span>';
		$html .= '</a>';

		return $html;
	}
}
