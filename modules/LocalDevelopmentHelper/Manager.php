<?php

namespace YouSaidItCards\Modules\LocalDevelopmentHelper;

use WP_Query;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Session\Session;

class Manager {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->init_hook();
		}

		return self::$instance;
	}

	public function init_hook() {
		if ( 'production' === wp_get_environment_type() ) {
			return;
		}
		add_action( 'pre_get_posts', array( self::$instance, 'query_with_metadata' ) );
		add_action( 'wp_footer', [ self::$instance, 'add_footer_content' ], 999 );
		add_action( 'wp_ajax_yousaidit_debug_settings', [ self::$instance, 'debug_settings' ] );
		add_action( 'wp_ajax_nopriv_yousaidit_debug_settings', [ self::$instance, 'debug_settings' ] );
	}

	public function debug_settings() {
		if ( ! wp_verify_nonce( $_REQUEST['_token'] ?? '', 'yousaidit_local_development' ) ) {
			wp_send_json_error( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ),
				403 );
		}
		$card_type = $_POST['card_type'] ? sanitize_text_field( $_POST['card_type'] ) : '';
		$session   = Session::get_instance();
		if ( ! empty( $card_type ) ) {
			$session->add( 'local_development_card_type', $card_type );
		} else {
			$session->remove( 'local_development_card_type' );
		}

		wp_send_json_success();
	}

	/**
	 * Footer content
	 *
	 * @return void
	 */
	public function add_footer_content() {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() ) ) {
			$dynamic_cards_products = DesignerCard::get_dynamic_card_product_ids();
			$session                = Session::get_instance();
			$card_type              = $session->get( 'local_development_card_type', 'all' );
			$ajax_url               = wp_nonce_url(
				add_query_arg( [ 'action' => 'yousaidit_debug_settings' ], admin_url( 'admin-ajax.php' ) ),
				'yousaidit_local_development',
				'_token'
			);
			include 'template-debug-bar.php';
		}
	}

	/**
	 * Hide rude product from shop
	 *
	 * @param  WP_Query  $query
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
		$show_rude_card = $session->get( 'local_development_card_type' );
		if ( 'customized' == $show_rude_card ) {
			$meta_query   = (array) $query->get( 'meta_query' );
			$meta_query[] = array( 'key' => '_card_type', 'value' => 'dynamic' );
			$query->set( 'meta_query', $meta_query );
		}

		return $query;
	}
}