<?php

namespace Yousaidit\Modules\PackingSlip;

use Dompdf\Dompdf;
use Exception;
use Yousaidit\Integration\ShipStation\Order;

class PackingSlip {

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * PackingSlip constructor.
	 *
	 * @param $order
	 */
	public function __construct( $order = null ) {
		if ( $order instanceof Order ) {
			$this->order = $order;
		}
	}

	/**
	 * Get packing slip content
	 *
	 * @param Order $order
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_content( Order $order ) {
		ob_start();
		$items = $order->get_order_items();
		?>
		<table class="head container">
			<tr>
				<td class="header">

				</td>
				<td class="shop-info">
					<div class="shop-name"><h3><?php echo $order->get_shop_name(); ?></h3></div>
				</td>
			</tr>
		</table>

		<table class="order-data-addresses" style="padding-left:40px;">
			<tr>
				<td class="address shipping-address">
					<!-- <h3><?php _e( 'Shipping Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
					<?php echo $order->get_formatted_shipping_address(); ?>
				</td>
				<td class="address billing-address">

				</td>
				<td class="order-data">
					<table>
						<tr class="order-number">
							<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
							<td><?php echo $order->get_id(); ?></td>
						</tr>
						<tr class="order-date">
							<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
							<td><?php echo $order->get_formatted_date(); ?></td>
						</tr>
						<tr class="shipping-method">
							<th><?php _e( 'Shipping Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
							<td><?php echo $order->requested_shipping_service(); ?></td>
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
			<?php
			if ( $order->has_product() ) :
				foreach ( $items as $item ) :
					if ( ! $item->has_product() ) {
						continue;
					}
					?>
					<tr class="">
						<td class="product">
							<span class="item-name"><?php echo $item->get_product()->get_title(); ?></span>
							<dl class="meta">
								<?php if ( ! empty( $item->get_sku() ) ) : ?>
									<dt class="sku">SKU:</dt>
									<dd class="sku"><?php echo $item->get_sku(); ?></dd><?php endif; ?>
							</dl>
							<?php foreach ( $item->get_option() as $option ): ?>
								<dl class="meta">
									<?php if ( ! empty( $option ) ) : ?>
										<dt class="sku"><?php echo $option['name']; ?>:</dt>
										<dd class="sku"><?php echo $option['value']; ?></dd>
									<?php endif; ?>
								</dl>
							<?php endforeach; ?>
						</td>
						<td class="quantity"><?php echo $item->get_quantity(); ?></td>
					</tr>
				<?php
				endforeach;
			endif;
			?>
			</tbody>
		</table>
		<table class="order-notes">
			<?php if ( $order->has_customer_notes() ): ?>
				<tr>
					<th>Customer Note:</th>
					<td><?php echo $order->get_customer_notes(); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $order->has_internal_notes() ): ?>
				<tr>
					<th>Internal Note:</th>
					<td><?php echo $order->get_internal_notes(); ?></td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get document
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function get_document( $content ) {
		$self = new static();
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php echo $self->get_title(); ?></title>
			<style type="text/css"><?php $self->template_styles(); ?></style>
		</head>
		<body class="<?php echo $self->get_type(); ?>">
		<?php echo $content; ?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate PDF
	 *
	 * @throws Exception
	 */
	public function generate_pdf() {
		$content = self::get_content( $this->order );
		$html    = self::get_document( $content );

		$file_name = sprintf( 'packing-slip-%ss.pdf', $this->order->get_id() );
		self::get_pdf( $html, $file_name );
		die();
	}

	public static function get_pdf( $html, $file_name ) {
		$dompdf = new Dompdf();
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();
		$dompdf->stream( $file_name );
	}


	public function get_title() {
		return 'Packing Slip';
	}

	public function template_styles() {
		?>
		@font-face {
		font-family: 'Open Sans';
		font-weight: normal;
		font-style: normal;
		src: local('Open Sans'), local('Open-Sans'), url(<?php echo STACKONET_TOOLKIT_PATH; ?>/templates/OpenSans-Regular.ttf) format('truetype');
		}
		<?php
		include STACKONET_TOOLKIT_PATH . '/templates/style.css';
	}

	/**
	 * Type
	 */
	public function get_type() {
		return 'packing-slip';
	}
}
