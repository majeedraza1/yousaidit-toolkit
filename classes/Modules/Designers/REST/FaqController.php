<?php

namespace Yousaidit\Modules\Designers\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yousaidit\Modules\Designers\FAQ;

defined( 'ABSPATH' ) || exit;

class FaqController extends ApiController {
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

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/designer-faqs', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
			],
		] );
		register_rest_route( $this->namespace, '/designer-faqs/(?P<id>\d+)', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$query = FAQ::find();
		$items = $query->get_posts();

		return $this->respondOK( $this->format_for_rest_response( $items ) );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = FAQ::find_by_id( $id );
		if ( ! $post instanceof FAQ ) {
			return $this->respondNotFound();
		}

		return $this->respondOK( $post );
	}

	/**
	 * @param \WP_Post[] $posts
	 *
	 * @return array
	 */
	private function format_for_rest_response( $posts ) {
		$items = [];

		foreach ( $posts as $post ) {
			$items[] = new FAQ( $post );
		}

		return $items;
	}
}
