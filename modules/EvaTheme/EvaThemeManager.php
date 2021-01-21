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
		}

		return self::$instance;
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
}
