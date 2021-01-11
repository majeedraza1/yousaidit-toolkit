<?php

namespace YouSaidItCards\Modules\MultistepCheckout;

class MultistepCheckout {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			// Remove default checkout login form
			add_action( 'woocommerce_init', function () {
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
				add_action( 'woocommerce_checkout_before_checkout_form',
					[ self::$instance, 'checkout_login_form' ], 15 );
			} );

			add_filter( 'woocommerce_locate_template', array( self::$instance, 'locate_template' ), 20, 2 );
			add_action( 'woocommerce_checkout_before_checkout_form', [ self::$instance, 'step_progress_bar' ], 11 );
			add_action( 'woocommerce_checkout_before_customer_details', [ self::$instance, 'tabs_content' ], 12 );
			add_action( 'tab_shipping_content', [ self::$instance, 'tab_shipping_content' ] );
			add_action( 'woocommerce_checkout_update_order_meta', [ self::$instance, 'update_order_meta' ] );
			add_action( 'woocommerce_admin_order_data_after_shipping_address',
				[ self::$instance, 'after_shipping_address' ] );

			add_filter( 'woocommerce_shipstation_export_custom_field_2', function () {
				return '_stackonet_custom_info';
			} );

			add_filter( 'woocommerce_shipstation_export_custom_field_2_value',
				[ self::$instance, 'shipstation_custom_field_value' ], 10, 2 );
		}

		return self::$instance;
	}

	public function shipstation_custom_field_value( $value, $order_id ) {
		$order         = wc_get_order( $order_id );
		$delivery_info = static::straight_to_door_delivery_info( $order );

		$value = wp_json_encode( [
			'straight_to_door_delivery' => $delivery_info,
		] );

		return $value;
	}

	/**
	 * @param int $order_id
	 */
	public function update_order_meta( $order_id ) {
		$item_sent_to = isset( $_POST['item_sent_to'] ) ? sanitize_text_field( $_POST['item_sent_to'] ) : '';
		if ( in_array( $item_sent_to, [ 'me', 'them' ] ) ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_item_sent_to', $_POST['item_sent_to'] );
			$order->save_meta_data();
		}
	}

	/**
	 * @param \WC_Order $order
	 */
	public function after_shipping_address( $order ) {
		$door_delivery = static::straight_to_door_delivery_info( $order );
		?>
		<p class="form-field form-field-wide">
			Straight to Their Door delivery?: <strong><?php echo $door_delivery; ?></strong>
		</p>
		<?php
	}

	/**
	 * Locate template
	 *
	 * @param string $template
	 * @param string $template_name
	 *
	 * @return string
	 */
	public function locate_template( $template, $template_name ) {
		if ( 'checkout/form-checkout.php' == $template_name ) {
			$template = dirname( __FILE__ ) . '/templates/form-checkout.php';
		}
		if ( 'checkout/review-order.php' == $template_name ) {
			$template = dirname( __FILE__ ) . '/templates/review-order.php';
		}
		if ( 'checkout/form-register.php' == $template_name ) {
			$template = dirname( __FILE__ ) . '/templates/form-register.php';
		}

		return $template;
	}

	/**
	 * Tab progress content
	 */
	public function step_progress_bar() {
		$tabs = [];

		if ( ! is_user_logged_in() ) {
			$tabs[] = [ 'key' => 'login', 'number_text' => '1', 'tab_text' => 'Login' ];
		}

		$tabs[] = [ 'key' => 'shipping', 'number_text' => '1', 'tab_text' => 'Shipping' ];
		$tabs[] = [ 'key' => 'billing', 'number_text' => '2', 'tab_text' => 'Billing' ];
		$tabs[] = [ 'key' => 'order-payment', 'number_text' => '3', 'tab_text' => 'Order & Payment' ];
		?>
		<div class="checkout-tabs-wrapper" id="yousaidit-checkout-tabs">
			<ul class="checkout-tabs">
				<?php
				foreach ( $tabs as $index => $tab ) {
					if ( $index === 0 ) {
						$tab['current'] = true;
					}
					$tab['number_text'] = ( $index + 1 );
					echo static::tab_item( $tab );
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public static function tab_item( $args = [] ) {
		$args = wp_parse_args( $args, [
			'key'         => uniqid(),
			'number_text' => '',
			'tab_text'    => '',
			'completed'   => false,
			'current'     => false
		] );

		$item_classes = [ 'checkout-tab-item' ];
		if ( $args['completed'] ) {
			$item_classes[] = 'is-completed';
		}
		if ( $args['current'] ) {
			$item_classes[] = 'is-current';
		}

		$html = '<li class="' . implode( ' ', $item_classes ) . '" data-target="tab-' . esc_attr( $args['key'] ) . '">';

		$html .= '<div class="checkout-tab-item__outer">';
		$html .= '<div class="checkout-tab-item__inner">';
		$html .= '<div class="checkout-tab-item__number-wrapper">';
		$html .= '<div class="checkout-tab-item__number">';
		$html .= '<span class="checkout-tab-item__number-text">' . esc_html( $args['number_text'] ) . '</span>';
		$html .= '<span class="checkout-tab-item__completed-icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
			<path d="M0 0h24v24H0z" fill="none"/>
			<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
			</svg>
			</span>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="checkout-tab-item__text"><span>' . esc_html( $args['tab_text'] ) . '</span></div>';
		$html .= '</div>';
		$html .= '</div>'; //.checkout-tab-item-outer

		$html .= '</li>';

		return $html;
	}

	/**
	 * Shipping tab content
	 */
	public function tab_shipping_content() {
		?>
		<div class="section-item_sent_to">
			<h3 class="section-title">Where would you like your order sent?</h3>
			<div class="input-item_sent_to">
				<input class="radio-item_sent_to" type="radio" id="box--sent-to-me" value="me" name="item_sent_to"/>
				<label class="box box-item_sent_to box--sent-to-me" for="box--sent-to-me">
					<strong>Sent to me</strong>
					<span>All items will be sent to your address & cards come with a spare envelope</span>
				</label>
				<input class="radio-item_sent_to" type="radio" id="box--sent-to-them" value="them" name="item_sent_to"/>
				<label class="box box-item_sent_to box--sent-to-them" for="box--sent-to-them">
					<strong>Sent to them</strong>
					<span>We'll send all items straight to their door. ready for them to open</span>
				</label>
			</div>
		</div>
		<div class="field--checkout_shipping">
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
		</div>

		<?php
	}

	/**
	 * Tab content
	 */
	public function tabs_content() {
		?>
		<div class="checkout-notification">Please fill all required fields.</div>
		<div class="checkout-tabs-content">
			<div data-tab="tab-shipping" style="display: none">
				<?php do_action( 'tab_shipping_content' ); ?>
			</div>
			<div data-tab="tab-billing" class="field--checkout_billing" style="display: none">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>
			<div data-tab="tab-order-payment" style="display: none">
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>
		</div>
		<div class="checkout-nav first-step <?php echo ! is_user_logged_in() ? 'has-login-form' : ''; ?>">
			<button class="button button--checkout-pre">Previous</button>
			<button class="button button--checkout-next">Next</button>
		</div>
		<?php
	}

	/**
	 * Checkout login form
	 *
	 * @param \WC_Checkout $checkout
	 */
	public function checkout_login_form( $checkout ) {
		if ( is_user_logged_in() ) {
			return;
		}
		?>
		<div data-tab="tab-login" class="field--checkout_login" style="display: none">
			<div class="checkout-inline-tabs">
				<h3 class="checkout-inline-tabs__item is-current" data-inline_target="checkout-form-login">Login</h3>
				<span class="checkout-inline-tabs__sep">/</span>
				<h3 class="checkout-inline-tabs__item" data-inline_target="checkout-form-register">Register</h3>
			</div>
			<div class="multistep-checkout--form-login" data-inline_tab="checkout-form-login" style="display: block">
				<?php wc_get_template( 'checkout/form-login.php', array( 'checkout' => $checkout, ) ); ?>
			</div>
			<div class="multistep-checkout--form-register" data-inline_tab="checkout-form-register"
				 style="display: none">
				<?php wc_get_template( 'checkout/form-register.php', array( 'checkout' => $checkout ) ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function straight_to_door_delivery_info( \WC_Order $order ) {
		$_item_sent_to = $order->get_meta( '_item_sent_to', true );
		$sent_to       = in_array( $_item_sent_to, [ 'me', 'them' ] ) ? $_item_sent_to : '';
		$door_delivery = 'No info';
		if ( 'them' == $sent_to ) {
			$door_delivery = 'Yes';
		}
		if ( 'me' == $sent_to ) {
			$door_delivery = 'No';
		}

		return $door_delivery;
	}
}
