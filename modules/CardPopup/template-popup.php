<?php
/**
 * @var WC_Product $product WooCommerce product object.
 * @var WEPO_Product_Field[] | WEPO_Product_Field_Select[] $extra_fields extra product options.
 */

use YouSaidItCards\Modules\CardPopup\WishlistList;
use YouSaidItCards\Modules\DispatchTimer\Settings;

defined( 'ABSPATH' ) || exit;
?>

<div class="card-category-popup-content">
    <div class="flex items-center">
        <div>
			<?php
			if ( class_exists( 'YITH_WCWL' ) ) {
				$wishlist_button = WishlistList::get_wishlist_button( $product->get_id() );
				echo $wishlist_button; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
			?>
        </div>
        <div class="flex-grow"></div>
        <div>
            <span class="shapla-delete-icon is-medium" data-close="shapla-modal"></span>
        </div>
    </div>
    <div class="flex flex-wrap">
        <div class="w-full lg:w-1/2">
            <div class="max-w-md mx-auto">
				<?php
				$image = wp_get_attachment_image( $product->get_image_id(), 'medium_large', false );
				echo $image; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
				?>
            </div>
        </div>
        <div class="w-full lg:w-1/2">
            <div class="flex font-medium mb-2 uppercase text-3xl">
				<?php echo $product->get_title(); ?>
            </div>
            <div class="text-3xl">
				<?php echo $product->get_price_html() ?>
            </div>
            <div class="flex justify-center my-4">
				<?php
				try {
					$dispatch_time = Settings::get_next_dispatch_timer_message();
					echo $dispatch_time; // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
				} catch ( Exception $e ) {
				}
				?>
            </div>
            <div class="mb-2">
				<?php
				$html = '';
				foreach ( $extra_fields as $field ) {
					if ( $field instanceof \WEPO_Product_Field_Select ) {
						?>
                        <div class="flex space-x-2 items-center">
                            <div class="font-bold"><?php echo esc_html( $field->title ); ?></div>
                            <div class="flex-grow">
                                <select class="m-0" name="<?php echo esc_attr( $field->name ); ?>"
                                        id="<?php echo esc_attr( $field->id ); ?>">
									<?php
									foreach ( $field->options as $option ) {
										echo '<option value="' . esc_attr( $option['key'] ) . '">' . esc_attr( $option['text'] ) . '</option>';
									}
									?>
                                </select>
                            </div>
                        </div>
						<?php
					}
				}
				echo $html;
				?>
            </div>
            <div class="my-4 flex justify-center items-center space-x-2">
                <div class="max-w-[50px]">
                    <input type="number" name="product_qty" class="input-text qty text h-[56px] text-center mb-0"
                           min="1" step="1" value="1">
                </div>
                <a href="<?php echo $product->get_permalink() ?>" class="button btn1 bshadow">
                    <span><?php esc_html_e( 'Add a message', 'yousaidit-toolkit' ); ?></span>
                </a>
                <a href="<?php echo $product->get_permalink() ?>" class="button btn1 checkout wc-forward bshadow">
                    <span><?php esc_html_e( 'Add to Basket', 'yousaidit-toolkit' ); ?></span>
                </a>
            </div>
            <div class="flex justify-center">
                <a href="<?php echo $product->get_permalink() ?>" class="font-medium">
                    <span><?php esc_html_e( 'More information', 'yousaidit-toolkit' ); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
