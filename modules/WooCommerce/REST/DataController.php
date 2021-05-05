<?php

namespace YouSaidItCards\Modules\WooCommerce\REST;

use ArrayObject;
use Stackonet\WP\Framework\Supports\Validate;
use WC_Product;
use WP_REST_Server;
use YouSaidItCards\Admin\SettingPage;
use YouSaidItCards\Modules\WooCommerce\SquarePaymentRestClient;
use YouSaidItCards\Modules\WooCommerce\Utils;
use YouSaidItCards\Modules\WooCommerce\WcRestClient;
use YouSaidItCards\REST\ApiController;
use YouSaidItCards\Utilities\WcUtils;

class DataController extends ApiController {
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
		register_rest_route( $this->namespace, 'data', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => [ $this, 'is_logged_in' ],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {
		$force = Validate::checked( $request->get_param( 'force' ) );

		$rest_client                       = new WcRestClient();
		$response                          = $rest_client->list_general_data( $force );
		$response['card_sizes']            = Utils::get_formatted_size_attribute();
		$response['currency_symbol']       = get_woocommerce_currency_symbol();
		$response['square_application_id'] = SquarePaymentRestClient::get_setting( 'application_id' );
		$response['postcard_product']      = static::get_postcard_product();

		return $this->respondOK( $response );
	}

	/**
	 * Get postcard product
	 *
	 * @return array|ArrayObject
	 */
	public static function get_postcard_product() {
		$postcard_product_data = get_transient( 'postcard_product' );
		if ( ! is_array( $postcard_product_data ) ) {
			$postcard_product_data = new ArrayObject;

			$postcard_product_id = (int) SettingPage::get_option( 'postcard_product_id' );

			if ( $postcard_product_id ) {
				$postcard_product = wc_get_product( $postcard_product_id );
				if ( $postcard_product instanceof WC_Product ) {
					$postcard_product_data = WcUtils::format_product_data( $postcard_product );
					set_transient( 'postcard_product', $postcard_product_data, HOUR_IN_SECONDS );
				}
			}
		}

		return $postcard_product_data;
	}
}
