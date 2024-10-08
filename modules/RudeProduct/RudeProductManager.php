<?php

namespace YouSaidItCards\Modules\RudeProduct;

use Stackonet\WP\Framework\Supports\Validate;
use WP_Post;
use WP_Query;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\Session\Session;

defined( 'ABSPATH' ) || exit;

class RudeProductManager {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the classes can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'wp_enqueue_scripts', [ self::$instance, 'load_frontend_scripts' ] );

			add_action( 'pre_get_posts', array( self::$instance, 'query_with_metadata' ) );
			add_filter( 'woocommerce_related_products', array( self::$instance, 'related_products' ) );

			add_action( 'clear_auth_cookie', array( self::$instance, 'clear_auth_cookie' ) );

			add_action( 'woocommerce_before_shop_loop_result_count', [ self::$instance, 'rude_card_content' ], 20 );
			add_action( 'woocommerce_no_products_found', [ self::$instance, 'no_products_found' ], 8 );
			add_action( 'wp_footer', [ self::$instance, 'rude_card_dialog' ] );

			add_action( 'add_meta_boxes', array( self::$instance, 'add_meta_boxes' ) );
			add_action( 'save_post', array( self::$instance, 'save_meta_boxes' ), 10, 2 );

			add_action( 'wp_ajax_show_rude_card', array( self::$instance, 'show_rude_card' ) );
			add_action( 'wp_ajax_nopriv_show_rude_card', array( self::$instance, 'show_rude_card' ) );

			add_action( 'wp_ajax_show_rude_card_dialog', array( self::$instance, 'show_rude_card_dialog' ) );
			add_action( 'wp_ajax_nopriv_show_rude_card_dialog', array( self::$instance, 'show_rude_card_dialog' ) );

			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'setting_section' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'setting_fields' ] );
		}

		return self::$instance;
	}

	public function setting_section( array $sections ) {
		$sections[] = [
			'id'       => 'section_rude_card',
			'title'    => __( 'Rude Card Settings' ),
			'panel'    => 'general',
			'priority' => 20,
		];

		return $sections;
	}

	public function setting_fields( array $fields ) {
		$fields[] = [
			'id'                => 'enable_rude_card_popup',
			'type'              => 'checkbox',
			'title'             => __( 'Enable popup' ),
			'description'       => __( 'Enable one time popup for rude card preview.' ),
			'priority'          => 10,
			'default'           => '1',
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'section_rude_card',
		];

		return $fields;
	}

	/**
	 * Handle rude card ajax functionality
	 */
	public function show_rude_card() {
		$value         = isset( $_POST['value'] ) ? $_POST['value'] : null;
		$value         = in_array( $value, [ 'yes', 'no' ] ) ? $value : null;
		$session       = Session::get_instance();
		$should_reload = false;
		if ( $value !== $session->get( 'show_rude_card' ) ) {
			$should_reload = true;
		}

		if ( in_array( $value, [ 'yes', 'no' ] ) ) {
			$session->add( 'show_rude_card', $value );
		}

		wp_send_json( [ 'should_reload' => $should_reload ], 200 );
	}

	/**
	 * Check if we should show rude card dialog
	 */
	public function show_rude_card_dialog() {
		//Set a cookie now to see if they are supported by the browser.
		$secure = ( 'https' === parse_url( get_site_url(), PHP_URL_SCHEME ) );
		setcookie( '_show_rude_card_dialog', 'no', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure );

		$value         = isset( $_POST['value'] ) ? $_POST['value'] : null;
		$value         = in_array( $value, [ 'yes', 'no' ] ) ? $value : null;
		$session       = Session::get_instance();
		$should_reload = false;
		if ( $value !== $session->get( 'show_rude_card' ) ) {
			$should_reload = true;
		}

		if ( in_array( $value, [ 'yes', 'no' ] ) ) {
			$session->add( 'show_rude_card', $value );
		}

		wp_send_json( [ 'should_reload' => $should_reload ], 200 );
	}

	/**
	 * Load frontend scripts
	 */
	public function load_frontend_scripts() {
		wp_enqueue_style( YOUSAIDIT_TOOLKIT . '-frontend' );
		wp_enqueue_script( YOUSAIDIT_TOOLKIT . '-frontend' );
	}

	/**
	 * Hide rude product from shop
	 *
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public function query_with_metadata( $query ) {
		// It's the main query for a front end page of your site.
		// Not a query for an admin page.
		if ( ! $query->is_main_query() || is_admin() ) {
			return $query;
		}

		$session        = Session::get_instance();
		$show_rude_card = $session->get( 'show_rude_card' );
		if ( 'no' == $show_rude_card ) {
//			$meta_query   = (array) $query->get( 'meta_query' );
//			$meta_query[] = array(
//				'relation' => 'OR',
//				array(
//					'key'     => '_is_rude_card',
//					'compare' => 'NOT EXISTS',
//				),
//				array(
//					'key'     => '_is_rude_card',
//					'value'   => 'yes',
//					'compare' => '!=',
//				)
//			);
//			$query->set( 'meta_query', $meta_query );

			$ids          = static::get_rude_products_ids();
			$existing_ids = (array) $query->get( 'post__not_in' );
			$new_ids      = array_filter( array_merge( $existing_ids, $ids ) );

			if ( count( $new_ids ) ) {
				$query->set( 'post__not_in', $new_ids );
			}
		}

		return $query;
	}

	/**
	 * Get rude products ids
	 *
	 * @return array
	 */
	public static function get_rude_products_ids(): array {
		$ids = get_transient( 'rude_products_ids' );
		if ( ! is_array( $ids ) ) {
			global $wpdb;
			$sql    = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_is_rude_card' AND meta_value = 'yes'";
			$result = $wpdb->get_results( $sql, ARRAY_A );
			$ids    = [];
			if ( is_array( $result ) && count( $result ) ) {
				$_ids = wp_list_pluck( $result, 'post_id' );
				$ids  = array_map( 'intval', $_ids );
			}

			set_transient( 'rude_products_ids', $ids, YEAR_IN_SECONDS );
		}

		return $ids;
	}

	/**
	 * Modify related product ids
	 *
	 * @param array $related_products
	 *
	 * @return array
	 */
	public function related_products( $related_products ) {
		$ids     = static::get_rude_products_ids();
		$session = Session::get_instance();

		if ( 'no' == $session->get( 'show_rude_card' ) && count( $ids ) > 0 ) {
			foreach ( $related_products as $index => $product_id ) {
				if ( in_array( $product_id, $ids, false ) ) {
					unset( $related_products[ $index ] );
				}
			}
		}

		return $related_products;
	}

	/**
	 * Regenerate cookie on login/logout
	 */
	public function clear_auth_cookie() {
		$session = Session::get_instance();
		$_show   = $session->get( 'show_rude_card' );
		$session->regenerate_id( true );
		$session->add( 'show_rude_card', $_show );
	}

	/**
	 * Show rude card
	 */
	public function rude_card_content() {
		?>
		<div id="yousaidit-top-bar" class="yousaidit-top-bar">
			<div class="sidebar-top">
				<?php
				if ( is_active_sidebar( 'widgets-product-listing' ) ) {
					dynamic_sidebar( 'widgets-product-listing' );
				}
				?>
			</div>
			<?php $this->rude_card(); ?>
		</div>
		<?php
	}

	/**
	 * No product rude products
	 */
	public function no_products_found() {
		?>
		<div id="yousaidit-top-bar" class="yousaidit-top-bar no-products-found">
			<div class="sidebar-top">
				<?php
				if ( is_active_sidebar( 'widgets-product-listing' ) ) {
					dynamic_sidebar( 'widgets-product-listing' );
				}
				?>
			</div>
			<?php $this->rude_card(); ?>
		</div>
		<?php
	}

	/**
	 * Rude product filter html
	 */
	public function rude_card() {
		$session = Session::get_instance();
		$_show   = $session->get( 'show_rude_card' );
		$show    = in_array( $_show, array( 'yes', 'no' ) ) ? $_show : 'yes';
		?>
		<div class="show-rude-card">
			<span class="show-rude-card__label">Rude Products?</span>
			<label class="mdl-checkbox">
				<input type="radio" name="_show_rude_card" id="_show_rude_card_yes"
					   class="mdl-checkbox__input" value="yes" <?php checked( $show, 'yes' ) ?>>
				<span class="mdl-checkbox__mark"></span>
				<span class="mdl-checkbox__label">Yes</span>
			</label>
			<label class="mdl-checkbox">
				<input type="radio" name="_show_rude_card" id="_show_rude_card_no"
					   class="mdl-checkbox__input" value="no" <?php checked( $show, 'no' ) ?>>
				<span class="mdl-checkbox__mark"></span>
				<span class="mdl-checkbox__label">No</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Rude card dialog
	 */
	public function rude_card_dialog() {
		$should_show_popup = is_shop() || is_product_category() || is_product_tag();
		if ( ! $should_show_popup ) {
			return;
		}

		$should_show = Validate::checked( SettingPage::get_option( 'enable_rude_card_popup', '1' ) );
		if ( false === $should_show ) {
			return;
		}

		if ( 'no' === ( $_COOKIE['_show_rude_card_dialog'] ?? 'yes' ) ) {
			return;
		}
		?>
		<div class="shapla-modal is-active rude-cards-modal">
			<div class="shapla-modal-background"></div>
			<div class="shapla-modal-content is-small">
				<div class="line-1">Would you like to see</div>
				<div class="line-2">Rude Products?</div>
				<div class="line-3">
					<label class="mdl-checkbox">
						<input type="radio" name="_show_rude_card_dialog" id="_show_rude_card_yes"
							   class="mdl-checkbox__input" value="yes">
						<span class="mdl-checkbox__mark"></span>
						<span class="mdl-checkbox__label">Yes Please</span>
					</label>
				</div>
				<div class="line-4">
					<label class="mdl-checkbox">
						<input type="radio" name="_show_rude_card_dialog" id="_show_rude_card_no"
							   class="mdl-checkbox__input" value="no">
						<span class="mdl-checkbox__mark"></span>
						<span class="mdl-checkbox__label">No Thanks, Keep Them Clean!</span>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add carousel slider meta box
	 */
	public function add_meta_boxes() {
		add_meta_box( "is-rude-card", __( "Rude Card?" ),
			array( $this, 'rude_card_callback' ), 'product', "side", "high" );
	}

	/**
	 * Metabox callback
	 *
	 * @param WP_Post $post
	 */
	public function rude_card_callback( $post ) {
		$is_rude_card = get_post_meta( $post->ID, '_is_rude_card', true );
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Is rude card?</span></legend>
			<label for="_is_rude_card_yes">
				<input name="_is_rude_card" type="radio" id="_is_rude_card_yes"
					   value="yes" <?php echo $is_rude_card == 'yes' ? 'checked' : '' ?>> Yes
			</label>
			<span>&nbsp;&nbsp;</span>
			<label for="_is_rude_card_no">
				<input name="_is_rude_card" type="radio" id="_is_rude_card_no"
					   value="no" <?php echo $is_rude_card == 'no' ? 'checked' : '' ?>> No
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( isset( $_POST['_is_rude_card'] ) && in_array( $_POST['_is_rude_card'], [ 'yes', 'no' ] ) ) {
			update_post_meta( $post_id, '_is_rude_card', $_POST['_is_rude_card'] );
		} else {
			delete_post_meta( $post_id, '_is_rude_card' );
		}

		if ( 'product' == get_post_type( $post ) ) {
			delete_transient( 'rude_products_ids' );
		}
	}
}
