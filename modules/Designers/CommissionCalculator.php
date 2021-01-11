<?php

namespace YouSaidItCards\Modules\Designers;

use Stackonet\WP\Framework\Supports\Logger;
use WC_Order;
use WC_Order_Item_Product;
use YouSaidItCards\Modules\Designers\Models\CardDesigner;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Modules\Designers\Models\DesignerCommission;

defined( 'ABSPATH' ) || exit;

class CommissionCalculator {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// set an designer commission cost when added to an order (WC 3.0+)
			add_action( 'woocommerce_new_order_item', [ self::$instance, 'add_new_order_commission' ], 10, 3 );
			// Display commission info on order item
			add_action( 'woocommerce_after_order_itemmeta', [ self::$instance, 'display_item_commission' ], 10, 3 );

			// Add commission meta data on product item
			add_filter( 'woocommerce_hidden_order_itemmeta', [ self::$instance, 'hidden_order_itemmeta' ] );

			add_action( 'woocommerce_order_status_changed', [ self::$instance, 'update_commission_status' ], 99, 4 );
		}

		return self::$instance;
	}

	/**
	 * @param int $order_id
	 * @param string $status_from
	 * @param string $status_to
	 * @param WC_Order $order
	 */
	public function update_commission_status( $order_id, $status_from, $status_to, $order ) {
		if ( 'yes' == $order->get_meta( '_has_designer_commissions', true ) ) {
			Logger::log( [ 'Working', $order_id, $status_from, $status_to ] );
			/** @var WC_Order_Item_Product[] $items */
			$items = $order->get_items( 'line_item' );
			foreach ( $items as $item ) {
				$commission_id = $item->get_meta( '_card_designer_commission_id', true );
				if ( ! empty( $commission_id ) ) {
					( new DesignerCommission )->update( [
						'commission_id' => $commission_id,
						'order_status'  => $order->get_status( 'edit' )
					] );
				}
			}
		}
	}

	/**
	 * Add designer commission
	 *
	 * @param int $item_id
	 * @param WC_Order_Item_Product $item
	 * @param int $order_id
	 */
	public function add_new_order_commission( $item_id, $item, $order_id ) {
		$commission = $item->get_meta( '_card_designer_commission', true );
		if ( ! $item instanceof WC_Order_Item_Product || ! empty( $commission ) ) {
			return;
		}

		$product_id  = $item->get_product_id();
		$product     = wc_get_product( $product_id );
		$designer_id = $product->get_meta( '_card_designer_id', true );
		$card_id     = $product->get_meta( '_card_id', true );

		if ( empty( $designer_id ) || empty( $card_id ) ) {
			return;
		}

		$card = ( new DesignerCard )->find_by_id( $card_id );
		if ( ! $card instanceof DesignerCard ) {
			return;
		}

		try {
			wc_update_order_item_meta( $item_id, '_card_designer_id', $designer_id );
			wc_update_order_item_meta( $item_id, '_card_id', $card_id );

			$size = $product->get_meta( '_card_size', true );
			if ( empty( $size ) ) {
				$size = DesignerCard::get_order_item_card_size( $item );
			}

			$commission_per_sale = $product->get_meta( '_card_designer_commission', true );
			if ( empty( $commission_per_sale ) ) {
				$commission_per_sale = $card->get_commission( $size );
			}

			$commission_amount = floatval( $commission_per_sale ) * $item->get_quantity();

			wc_update_order_item_meta( $item_id, '_card_designer_commission', $commission_amount );

			$order = wc_get_order( $order_id );

			$order->update_meta_data( '_has_designer_commissions', 'yes' );
			$order->save_meta_data();;

			$commission_id = DesignerCommission::createIfNotExists( [
				'card_id'          => $card_id,
				'designer_id'      => $designer_id,
				'customer_id'      => $order->get_customer_id(),
				'order_id'         => $order_id,
				'order_item_id'    => $item->get_id(),
				'order_quantity'   => $item->get_quantity(),
				'item_commission'  => $commission_per_sale,
				'total_commission' => $commission_amount,
				'card_size'        => $size,
				'order_status'     => $order->get_status( 'edit' ),
				'payment_status'   => 'unpaid',
			] );

			wc_update_order_item_meta( $item_id, '_card_designer_commission_id', $commission_id );

			$card->increase_sales_count( $item );

		} catch ( \Exception $e ) {
			Logger::log( $e->getMessage() );
		}
	}

	/**
	 * @param int $item_id
	 * @param WC_Order_Item_Product $item
	 * @param \WC_Product $product
	 */
	public function display_item_commission( $item_id, $item, $product ) {
		if ( $item instanceof WC_Order_Item_Product ) {
			$designer_id       = $item->get_meta( '_card_designer_id', true );
			$card_id           = $item->get_meta( '_card_id', true );
			$commission_amount = $item->get_meta( '_card_designer_commission', true );

			if ( ! empty( $designer_id ) && ! empty( $card_id ) ) {
				$designer = new CardDesigner( $designer_id );
				?>
				<div class="view">
					<table class="display_meta">
						<tr>
							<th>Designer:</th>
							<td>
								<p><?php echo $designer->get_user()->display_name; ?></p>
							</td>
						</tr>
						<tr>
							<th>Designer Commission:</th>
							<td>
								<p><?php echo wc_price( $commission_amount ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				<?php
			}
		}
	}

	/**
	 * @param array $item_metas
	 *
	 * @return array
	 */
	public function hidden_order_itemmeta( array $item_metas ) {
		$item_metas[] = '_card_designer_id';
		$item_metas[] = '_card_id';
		$item_metas[] = '_card_designer_commission';

		return $item_metas;
	}
}
