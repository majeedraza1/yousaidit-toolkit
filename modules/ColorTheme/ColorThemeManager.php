<?php

namespace YouSaidItCards\Modules\ColorTheme;

class ColorThemeManager {
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

			CustomizerSettings::init();
			Frontend::init();
		}

		return self::$instance;
	}
}
