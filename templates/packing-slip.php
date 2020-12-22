<?php

use YouSaidItCards\ShipStation\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var Order $_order */
$_order = $this->order;
?>

<table class="head container">
	<tr>
		<td class="header">

		</td>
		<td class="shop-info">
			<div class="shop-name"><h3><?php echo $_order->get_shop_name(); ?></h3></div>
		</td>
	</tr>
</table>

<table class="order-data-addresses" style="padding-left:40px;">
	<tr>
		<td class="address shipping-address">
			<!-- <h3><?php _e( 'Shipping Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
			<?php echo $_order->get_formatted_shipping_address(); ?>
		</td>
		<td class="address billing-address">
			<?php if ( isset( $this->settings['display_billing_address'] ) && $this->ships_to_different_address() ) { ?>
				<h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
				<?php // $this->billing_address(); ?>
			<?php } ?>
		</td>
		<td class="order-data">
			<table>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php echo $_order->get_id(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php echo $_order->get_formatted_date(); ?></td>
				</tr>
				<tr class="shipping-method">
					<th><?php _e( 'Shipping Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php echo $_order->requested_shipping_service(); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="order-details">
	<thead>
	<tr>
		<th class="product"><?php _e( 'Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
		<th class="quantity"><?php _e( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php $items = $_order->get_order_items();
	if ( sizeof( $items ) > 0 ) : foreach ( $items as $item_id => $item ) : ?>
		<tr class="">
			<td class="product">
				<?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
				<span class="item-name"><?php echo $item->get_product()->get_title(); ?></span>
				<span class="item-meta"><?php // echo $item['meta']; ?></span>
				<dl class="meta">
					<?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
					<?php if ( ! empty( $item->get_sku() ) ) : ?>
						<dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt>
						<dd class="sku"><?php echo $item->get_sku(); ?></dd><?php endif; ?>
				</dl>
			</td>
			<td class="quantity"><?php echo $item->get_quantity(); ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
</table>
