<?php
/**
 * @var WC_Product $product WooCommerce product object.
 */

use YouSaidItCards\Modules\CardPopup\Wishlist;
use YouSaidItCards\Modules\DispatchTimer\Settings;

defined( 'ABSPATH' ) || exit;

$wishlist_button = Wishlist::get_wishlist_button( $product->get_id() );
?>

<div class="card-category-popup-content">
    <div class="flex items-center">
        <div>
			<?php
			if ( class_exists( 'YITH_WCWL' ) ) {
				echo $wishlist_button; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
			?>
        </div>
        <div class="flex-grow"></div>
        <div>
            <span class="shapla-delete-icon is-medium" data-close="shapla-modal"></span>
        </div>
    </div>
    <div class="max-w-md mx-auto">
		<?php
		$image = wp_get_attachment_image( $product->get_image_id(), 'medium_large', false );
		echo $image; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
    </div>

    <div class="flex font-bold justify-center mb-2 uppercase">
		<?php echo $product->get_title(); ?>
    </div>

    <div class="flex justify-center">
		<?php
		$dispatch_time = Settings::get_next_dispatch_timer_message();
		echo $dispatch_time; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
    </div>
    <div class="flex justify-center">
        <a href="<?php echo $product->get_permalink() ?>" class="button btn1 bshadow">
            <span><?php esc_html_e( 'Choose This Card', 'yousaidit-toolkit' ); ?></span>
        </a>
    </div>
</div>