<?php

namespace YouSaidItCards\Modules\Faq;

use YouSaidItCards\Modules\Faq\Models\Faq;
use YouSaidItCards\Modules\Faq\REST\FaqController;

class FaqManager {
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

			add_action( 'init', [ self::$instance, 'register_post_type' ] );
			FaqController::init();
		}

		return self::$instance;
	}

	/**
	 * Register post type
	 */
	public function register_post_type() {
		register_post_type( Faq::POST_TYPE, Faq::get_post_type_args( 'Customer FAQs', 'Posts', 'Post', [
			'menu_position' => 4,
		] ) );
	}
}
