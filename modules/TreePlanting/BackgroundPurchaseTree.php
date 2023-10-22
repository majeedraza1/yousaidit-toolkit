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
}