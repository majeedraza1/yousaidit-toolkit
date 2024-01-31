<?php

namespace YouSaidItCards\Modules\Designers\REST;

use Stackonet\WP\Framework\Traits\ApiPermissionChecker;

class ApiController extends \Stackonet\WP\Framework\REST\ApiController {
	use ApiPermissionChecker;

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $namespace = 'yousaidit/v1';
}
