<?php

namespace YouSaidItCards\Modules\TreePlanting;

use Stackonet\WP\Framework\BackgroundProcessing\BackgroundProcessWithUiHelper;

/**
 * BackgroundPurchaseTree class
 */
class BackgroundPurchaseTree extends BackgroundProcessWithUiHelper {

	protected static $instance = null;

	protected $admin_notice_heading = 'A background task is running to purchase {{total_items}} trees.';

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$id            = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
		$tree_planting = TreePlanting::find_single( $id );
		if ( ! $tree_planting instanceof TreePlanting ) {
			return false;
		}

		$response = EcologiClient::purchase_tree();
		if ( is_wp_error( $response ) ) {
			TreePlanting::update( [
				'id'            => $tree_planting->get_id(),
				'status'        => 'error',
				'error_message' => $response->get_error_message(),
			] );

			return false;
		}

		TreePlanting::update( [
			'id'              => $tree_planting->get_id(),
			'status'          => 'complete',
			'amount'          => $response['amount'],
			'currency'        => $response['currency'],
			'tree_url'        => $response['treeUrl'],
			'name'            => $response['name'],
			'project_details' => $response['projectDetails'],
		] );

		return false;
	}

	/**
	 * Sync tree planting
	 *
	 * @return array
	 */
	public static function sync(): array {
		$orders                = ShipStationOrder::find_for_tree_planting();
		$purchase_orders_count = Setting::purchase_tree_after_total_orders();
		$in_queue              = [];
		$in_sync               = [];
		if ( count( $orders ) >= $purchase_orders_count ) {
			$chunks = array_chunk( $orders, $purchase_orders_count );
			/** @var ShipStationOrder[] $chunk */
			foreach ( $chunks as $chunk ) {
				$orders_ids = wp_list_pluck( $chunk, 'shipstation_order_id' );
				if ( count( $chunk ) === $purchase_orders_count ) {
					$id = TreePlanting::create( [
						'orders_ids' => $orders_ids,
					] );

					if ( $id ) {
						$to_update = [];
						foreach ( $chunk as $item ) {
							$to_update[] = [ 'id' => $item->get_id(), 'tree_planting_id' => $id ];
						}
						if ( count( $to_update ) ) {
							ShipStationOrder::update_multiple( $to_update );
						}
						static::init()->push_to_queue( [ 'id' => $id ] );
					}
					$in_sync = array_merge( $in_sync, $orders_ids );
				} else {
					$in_queue = array_merge( $in_queue, $orders_ids );
				}
			}
		}

		return [
			'in_sync'  => $in_sync,
			'in_queue' => $in_queue,
		];
	}
}
