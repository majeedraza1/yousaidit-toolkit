<?php

namespace YouSaidItCards\Modules\TreePlanting;

/**
 * TreePlantingManager class
 */
class TreePlantingManager {
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

			add_action( 'wp_ajax_test_tree_planting', [ self::$instance, 'test_tree_planting' ] );
			add_action( 'woocommerce_new_order', [ self::$instance, 'woocommerce_new_order' ] );
			add_action( 'admin_init', [ TreePlanting::class, 'create_tables' ] );
			add_action( 'admin_init', [ ShipStationOrder::class, 'create_tables' ] );
			add_filter( 'yousaidit_toolkit/settings/sections', [ self::$instance, 'add_setting_sections' ] );
			add_filter( 'yousaidit_toolkit/settings/fields', [ self::$instance, 'add_setting_fields' ] );
			add_action( 'wp', [ self::$instance, 'schedule_cron_event' ] );
			add_action( 'yousaidit/sync_tree_planting', [ self::$instance, 'sync_tree_planting' ] );
			BackgroundPurchaseTree::init();
			Admin::init();
			AdminApiController::init();
		}

		return self::$instance;
	}

	public function add_setting_sections( array $sections ): array {
		$sections[] = [
			'id'          => 'section_ecologi_api_settings',
			'title'       => __( 'Ecologi Api', 'dialog-contact-form' ),
			'description' => __( 'Ecologi Api settings', 'dialog-contact-form' ),
			'panel'       => 'integrations',
			'priority'    => 50,
		];

		return $sections;
	}

	public function add_setting_fields( array $fields ): array {
		$fields[] = [
			'section'           => 'section_ecologi_api_settings',
			'id'                => 'ecologi_api_key',
			'type'              => 'text',
			'title'             => __( 'Api key' ),
			'description'       => __( 'Ecologi api key' ),
			'default'           => '',
			'priority'          => 10,
			'sanitize_callback' => 'sanitize_text_field',
		];
		$fields[] = [
			'section'           => 'section_ecologi_api_settings',
			'id'                => 'ecologi_is_test_mode',
			'type'              => 'checkbox',
			'title'             => __( 'Is sandbox mode?' ),
			'priority'          => 15,
			'default'           => '1',
			'sanitize_callback' => 'sanitize_text_field',
		];
		$fields[] = [
			'section'           => 'section_ecologi_api_settings',
			'id'                => 'ecologi_funded_by',
			'type'              => 'text',
			'title'             => __( 'Funded By' ),
			'description'       => __( 'Will be used by API. Leave empty to use site title.' ),
			'default'           => '',
			'priority'          => 20,
			'sanitize_callback' => 'sanitize_text_field',
		];
		$fields[] = [
			'section'           => 'section_ecologi_api_settings',
			'id'                => 'ecologi_purchase_tree_after_total_orders',
			'type'              => 'number',
			'title'             => __( 'Purchase tree after' ),
			'description'       => __( 'Set after how many orders it should purchase tree' ),
			'default'           => '20',
			'priority'          => 30,
			'sanitize_callback' => 'sanitize_text_field',
		];
		$fields[] = [
			'section'           => 'section_ecologi_api_settings',
			'id'                => 'ecologi_number_of_tree_to_purchase',
			'type'              => 'number',
			'title'             => __( 'How many trees to purchase' ),
			'default'           => '1',
			'priority'          => 40,
			'sanitize_callback' => 'sanitize_text_field',
		];

		return $fields;
	}

	/**
	 * @param  int  $order_id
	 *
	 * @return void
	 */
	public function woocommerce_new_order( int $order_id ) {
		$purchase_orders_count = Setting::purchase_tree_after_total_orders();

		$orders_ids   = Setting::get_cumulative_orders_ids();
		$orders_ids[] = $order_id;

		if ( count( $orders_ids ) >= $purchase_orders_count ) {
			$id = TreePlanting::create( [
				'orders_ids' => $orders_ids,
			] );
			if ( $id ) {
				$orders_ids = [];
			}
		}
		Setting::update_cumulative_orders_ids( $orders_ids );
	}

	/**
	 * Schedule cron event
	 */
	public static function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'yousaidit/sync_tree_planting' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'yousaidit/sync_tree_planting' );
		}
	}

	/**
	 * Sync tree planting
	 *
	 * @return void
	 */
	public function sync_tree_planting() {
		BackgroundPurchaseTree::sync();
	}

	/**
	 * Tree Planting test
	 *
	 * @return void
	 */
	public function test_tree_planting() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry. This link only for developer to do some testing.', 'yousaidit-toolkit' ) );
		}

		$response = BackgroundPurchaseTree::sync();

		var_dump( $response );
		wp_die();
	}
}
