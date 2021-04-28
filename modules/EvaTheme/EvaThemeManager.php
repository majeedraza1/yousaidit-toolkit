<?php


namespace YouSaidItCards\Modules\EvaTheme;


class EvaThemeManager {
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

			add_action( 'eva_before_header_left_end', [ self::$instance, 'banner' ] );
			add_filter( 'wc_get_template', [ self::$instance, 'get_template' ], 10, 3 );

			// Modify title design
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
			add_action( 'woocommerce_single_product_summary', [ self::$instance, 'single_title' ] );
			add_action( 'woocommerce_after_add_to_cart_quantity', [ self::$instance, 'inner_message' ] );
		}

		return self::$instance;
	}

	public function single_title() {
		echo 'Title';
	}

	public static function banner() {
		?>
		<div class="pinkbar">
			<div class="innerbar">
				<ul>
					<li>BUY ANY 5 CARDS FOR <strong>£9.99</strong></li>
					<li>
						<Strong>
							<i class="fa fa-star"></i>
							<i class="fa fa-star"></i>
							<i class="fa fa-star"></i>
							<i class="fa fa-star"></i>
							<i class="fa fa-star"></i>
						</Strong>
						5 STAR RATING
					</li>
					<li><strong>FREE DELIVERY</strong> OVER £20</li>
				</ul>
			</div>
		</div>
		<?php
	}

	public function get_template( $template, $template_name, $args ) {
		$path = YOUSAIDIT_TOOLKIT_PATH . '/templates/woocommerce/' . $template_name;
		if ( file_exists( $path ) ) {
			$template = $path;
		}

		return $template;
	}

	public function inner_message() {
		global $product;
		echo '<button type="submit" class="button btn1 bshadow button--add-inner-message"><span>Add inner message</span></button>';
		echo '<span class="inner-message-cost">+ £0.49</span>';
	}
}
