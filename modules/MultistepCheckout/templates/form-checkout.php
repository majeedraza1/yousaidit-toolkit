<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );

	return;
}

?>
<div class="row row-multistep-checkout">
	<div class="xxlarge-9 xlarge-10 large-12 large-centered columns">
		<?php do_action( 'woocommerce_checkout_before_checkout_form', $checkout ); ?>
		<form name="checkout" method="post" class="checkout woocommerce-checkout"
			  action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

			<?php if ( sizeof( $checkout->checkout_fields ) > 0 ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
			<?php endif; ?>

		</form>

	</div><!-- .columns -->
</div><!-- .row -->

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>