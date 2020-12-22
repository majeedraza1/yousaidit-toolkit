<?php

namespace Yousaidit\Modules\TradeSite;

use Yousaidit\Modules\TradeSite\REST\CardToProduct;

defined( 'ABSPATH' ) || die;

class TradeSiteManager {

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

			CardToProduct::init();
		}

		return self::$instance;
	}
}
