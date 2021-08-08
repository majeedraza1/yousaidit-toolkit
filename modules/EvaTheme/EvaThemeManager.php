<?php


namespace YouSaidItCards\Modules\EvaTheme;


use WC_Product;

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
			add_action( 'woocommerce_after_add_to_cart_quantity', [ self::$instance, 'inner_message' ] );
			add_filter( 'woocommerce_product_single_add_to_cart_text',
				[ self::$instance, 'single_add_to_cart_text' ], 10, 2 );
		}

		return self::$instance;
	}

	/**
	 * Add banner
	 */
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

	/**
	 * Inner message
	 */
	public function inner_message() {
		global $product;
		$options = (array) get_option( '_stackonet_toolkit' );
		$price   = isset( $options['inner_message_price'] ) ? floatval( $options['inner_message_price'] ) : 0;

		if ( ! self::should_show_inner_message( $product ) ) {
			return;
		}

		$html = '<div id="_inner_message_fields" style="visibility: hidden; position: absolute; width: 1px; height: 1px">';
		$html .= '<textarea id="_inner_message_content" name="_inner_message[content]"></textarea>';
		$html .= '<input type="text" id="_inner_message_font" name="_inner_message[font]"/>';
		$html .= '<input type="text" id="_inner_message_size" name="_inner_message[size]"/>';
		$html .= '<input type="text" id="_inner_message_align" name="_inner_message[align]"/>';
		$html .= '<input type="text" id="_inner_message_color" name="_inner_message[color]"/>';
		$html .= '</div>';

		$html .= '<button type="submit" class="button btn1 bshadow button--add-inner-message"><span>Add a message</span></button>';
		if ( $price > 0 ) {
			$html .= '<span class="inner-message-cost">+ ' . wc_price( $price ) . '</span>';
		}
		echo $html;
	}

	public function single_add_to_cart_text( $text, $product ) {
		if ( self::should_show_inner_message( $product ) ) {
			return 'Add to basket and continue shopping';
		}

		return $text;
	}

	/**
	 * Should show inner message
	 *
	 * @param WC_Product|null $product
	 *
	 * @return bool
	 */
	public static function should_show_inner_message( ?WC_Product $product = null ): bool {
		if ( ! $product instanceof WC_Product ) {
			$product = $GLOBALS['product'];
		}
		$cats    = $product->get_category_ids();
		$options = (array) get_option( '_stackonet_toolkit' );
		$cat_ids = isset( $options['inner_message_visible_on_cat'] ) && is_array( $options['inner_message_visible_on_cat'] ) ?
			$options['inner_message_visible_on_cat'] : [];

		$all_cats = [];
		foreach ( $cat_ids as $id ) {
			$categories = get_terms( [ 'parent' => $id, 'taxonomy' => 'product_cat', ] );
			$categories = wp_list_pluck( $categories, 'term_id' );
			$all_cats   = array_merge( $all_cats, $categories, [ $id ] );
		}

		$should_show = false;
		foreach ( $cats as $cat ) {
			if ( in_array( $cat, $all_cats ) ) {
				$should_show = true;
			}
		}

		return $should_show;
	}
}
