<?php

namespace YouSaidItCards\REST;

// If this file is called directly, abort.
use Stackonet\WP\Framework\Traits\ApiPermissionChecker;
use Stackonet\WP\Framework\Traits\ApiResponse;
use Stackonet\WP\Framework\Traits\ApiUtils;

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
}
