<?php

namespace YouSaidItCards\REST;

use Stackonet\WP\Framework\Traits\ApiPermissionChecker;
use Stackonet\WP\Framework\Traits\ApiResponse;
use Stackonet\WP\Framework\Traits\ApiUtils;
use WP_Error;
use WP_REST_Request;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Class ApiController
 * If you are using `stackonet/wp-helpers` package, you can also extend to the following class
 * \Yousaidit\WP\Framework\REST\ApiController
 *
 * @package Yousaidit\REST
 */
class ApiController extends \WP_REST_Controller {
	use ApiResponse, ApiUtils, ApiPermissionChecker;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'yousaidit/v1';

	/**
	 * Get permission error message
	 *
	 * @param  WP_REST_Request  $request  Full detail of request.
	 *
	 * @return true|WP_Error True on success, WP_Error object otherwise.
	 */
	public function auth_user_permissions_check( WP_REST_Request $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				'Sorry, you are not allowed to access this resource.',
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}
