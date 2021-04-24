<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @package    WooCommerce\Templates
 * @version    1.6.4
 */

use YouSaidItCards\Modules\Designers\Models\CardDesigner;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $product;

echo '<div class="product_title_container">';
the_title( '<h1 class="product_title entry-title">', '</h1>' );

$designer_id = $product->get_meta( '_card_designer_id', true );
if ( is_numeric( $designer_id ) ) {
	$designer = new CardDesigner( $designer_id );
	echo $designer->get_profile_link_card();
}
echo '</div>';
