<?php

namespace YouSaidItCards\ShipStation;

use JsonSerializable;
use Stackonet\WP\Framework\Abstracts\DatabaseModel;
use WC_Order_Item_Product;
use WC_Product;
use YouSaidItCards\Modules\Designers\Models\DesignerCard;
use YouSaidItCards\Utilities\MarketPlace;

class OrderItem implements JsonSerializable {

	/**
	 * @var array
	 */
	protected $data = [
		"orderItemId"       => "",
		"lineItemKey"       => "",
		"sku"               => "",
		"name"              => "",
		"imageUrl"          => "",
		"weight"            => "",
		"quantity"          => "",
		"unitPrice"         => "",
		"taxAmount"         => "",
		"shippingAmount"    => "",
		"warehouseLocation" => "",
		"options"           => [],
		"productId"         => "",
		"fulfillmentSku"    => "",
		"adjustment"        => "",
		"upc"               => "",
		"createDate"        => "",
		"modifyDate"        => "",
	];

	/**
	 * Product SKU
	 *
	 * @var string
	 */
	protected $sku = '';

	/**
	 * Product id
	 *
	 * @var int
	 */
	protected $product_id = 0;

	/**
	 * Product object
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * PDF id
	 *
	 * @var int
	 */
	private $pdf_id = 0;
	private $pdf_width = 0;
	private $pdf_height = 0;

	/**
	 * @var string
	 */
	private $inner_message = null;

	/**
	 * @var bool
	 */
	private $has_inner_message = false;

	/**
	 * Card size
	 *
	 * @var string
	 */
	protected $card_size = 'square';

	/**
	 * Get order item id
	 *
	 * @var int
	 */
	protected $order_item_id = 0;

	/**
	 * @var WC_Order_Item_Product
	 */
	protected $order_item_product;

	/**
	 * @var array
	 */
	protected $inner_message_info = [];

	protected $ship_station_order_id = 0;
	/**
	 * @var int
	 */
	protected $total_quantities_in_order;

	protected $store_id = 0;
	protected $card_id = 0;
	protected $designer_id = 0;
	protected $designer_commission = 0;

	/**
	 * OrderItem constructor.
	 *
	 * @param array $data
	 * @param int $ship_station_order_id
	 * @param int $quantities_in_order
	 */
	public function __construct( $data = [], $ship_station_order_id = 0, $quantities_in_order = 0, $store_id = 0 ) {
		$this->data                      = $data;
		$this->ship_station_order_id     = $ship_station_order_id;
		$this->total_quantities_in_order = $quantities_in_order;
		$this->store_id                  = $store_id;
		$this->sku                       = $data['sku'] ?? '';
		$this->product_id                = wc_get_product_id_by_sku( $this->sku );
		if ( $this->product_id ) {
			$this->product = wc_get_product( $this->product_id );

			$pdf_id           = $this->product->get_meta( '_pdf_id', true );
			$this->pdf_id     = is_numeric( $pdf_id ) ? intval( $pdf_id ) : 0;
			$this->pdf_width  = (int) get_post_meta( $this->pdf_id, '_pdf_width_millimeter', true );
			$this->pdf_height = (int) get_post_meta( $this->pdf_id, '_pdf_height_millimeter', true );

			$this->designer_id = (int) $this->product->get_meta( '_card_designer_id', true );
			$this->card_id     = (int) $this->product->get_meta( '_card_id', true );
		}

		$this->order_item_id = $this->get_order_item_id();

		// Check if item contains inner message
		$this->read_inner_message();
		$this->read_inner_message_info_for_web();

		// Check item card size
		$this->read_card_size();
	}

	/**
	 * Get property
	 *
	 * @param string $key
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get_prop( string $key, $default = null ) {
		return $this->data[ $key ] ?? $default;
	}

	/**
	 * @return array
	 */
	public function to_array(): array {
		$data = [];

		if ( $this->has_product() ) {
			$product_image = $this->get_product()->get_image_id();
			$src           = wp_get_attachment_image_src( $product_image, 'thumbnail' );
			$url           = is_array( $src ) && filter_var( $src[0], FILTER_VALIDATE_URL ) ? $src[0] : '';

			$data['id']                = $this->get_product()->get_id();
			$data['title']             = $this->get_product()->get_title();
			$data['product_sku']       = $this->get_product()->get_sku();
			$data['edit_product_url']  = $this->get_product_edit_url();
			$data['quantity']          = $this->get_quantity();
			$data['options']           = $this->get_option();
			$data['product_thumbnail'] = $url;
			$data['card_id']           = $this->card_id;
			$data['designer_id']       = $this->designer_id;
			$data['commission']        = $this->designer_commission;
			$data['total_commission']  = ( $this->designer_commission * $this->get_quantity() );
		}

		$data = array_merge( $data, [
			'art_work'          => $this->get_art_work(),
			'attached_file'     => $this->get_attached_file(),
			'pdf_id'            => $this->get_pdf_id(),
			'has_inner_message' => $this->has_inner_message(),
			'inner_message'     => $this->get_inner_message(),
			'card_size'         => $this->get_card_size(),
		] );

		return $data;
	}

	/**
	 * Get PDF info
	 *
	 * @return array
	 */
	public function get_pdf_info(): array {
		return [
			'id'     => $this->get_pdf_id(),
			'url'    => $this->get_pdf_url(),
			'width'  => $this->get_pdf_width(),
			'height' => $this->get_pdf_height(),
		];
	}

	/**
	 * Check if item contain commissions
	 *
	 * @return bool
	 */
	public function has_designer_commission(): bool {
		return ! ! ( $this->designer_id && $this->card_id );
	}

	/**
	 * Get designer commission
	 *
	 * @return float
	 */
	public function get_designer_commission(): float {
		if ( $this->has_designer_commission() ) {
			$store_info = MarketPlace::get( $this->store_id );
			if ( is_array( $store_info ) ) {
				$designer_card             = ( new DesignerCard )->find_by_id( $this->designer_id );
				$this->designer_commission = $designer_card->get_commission( $this->get_card_size(), $store_info['key'] );
			}
		}

		return (float) $this->designer_commission;
	}

	/**
	 * Get pdf ID
	 *
	 * @return int
	 */
	public function get_pdf_id() {
		return $this->pdf_id;
	}

	/**
	 * Get PDF URL
	 *
	 * @return string
	 */
	public function get_pdf_url() {
		return wp_get_attachment_url( $this->get_pdf_id() );
	}

	/**
	 * Get PDF width
	 *
	 * @return int
	 */
	public function get_pdf_width() {
		return $this->pdf_width;
	}

	/**
	 * Get PDF height
	 *
	 * @return int
	 */
	public function get_pdf_height() {
		return $this->pdf_height;
	}

	/**
	 * Get options
	 *
	 * @return array
	 */
	public function get_option() {
		return isset( $this->data['options'] ) && is_array( $this->data['options'] ) ? $this->data['options'] : [];
	}

	/**
	 * Get product edit url
	 *
	 * @return string
	 */
	public function get_product_edit_url() {
		return add_query_arg( [
			'post'   => $this->get_product()->get_parent_id() ? $this->get_product()->get_parent_id() : $this->get_product()->get_id(),
			'action' => 'edit'
		], admin_url( 'post.php' ) );
	}

	/**
	 * Get attached file
	 *
	 * @return false|string
	 */
	public function get_attached_file() {
		return get_attached_file( $this->pdf_id );
	}

	/**
	 * Get art work data
	 *
	 * @return array
	 */
	public function get_art_work(): array {
		if ( empty( $this->pdf_id ) ) {
			return [ 'id' => 0, 'url' => '' ];
		}

		return [
			'id'        => $this->pdf_id,
			'title'     => get_the_title( $this->pdf_id ),
			'url'       => $this->get_pdf_url(),
			'thumb_url' => wp_get_attachment_thumb_url( $this->pdf_id ),
			'width'     => $this->get_pdf_width(),
			'height'    => $this->get_pdf_height(),
		];
	}

	/**
	 * Get item sku
	 *
	 * @return string
	 */
	public function get_sku() {
		return $this->sku;
	}

	/**
	 * Get item quantity
	 *
	 * @return int
	 */
	public function get_quantity() {
		return ! empty( $this->data['quantity'] ) ? intval( $this->data['quantity'] ) : 0;
	}

	/**
	 * Get order item id
	 *
	 * @return int
	 */
	public function get_order_item_id(): int {
		return isset( $this->data['lineItemKey'] ) ? intval( $this->data['lineItemKey'] ) : 0;
	}

	/**
	 * Get wc order item product
	 *
	 * @return WC_Order_Item_Product
	 */
	public function get_wc_order_item() {
		if ( ! $this->order_item_product instanceof WC_Order_Item_Product ) {
			$id = $this->get_order_item_id();
			if ( $id ) {
				try {
					$order_item_product       = new WC_Order_Item_Product( $id );
					$this->order_item_product = $order_item_product;
				} catch ( \Exception $exception ) {

				}
			}
		}

		return $this->order_item_product;
	}

	/**
	 * Read inner message info for web
	 */
	public function read_inner_message_info_for_web() {
		$order_item = $this->get_wc_order_item();
		if ( $order_item instanceof WC_Order_Item_Product ) {
			$meta                     = $order_item->get_meta( '_inner_message', true );
			$this->inner_message_info = is_array( $meta ) ? $meta : [];
		}
	}

	/**
	 * Get item product
	 *
	 * @return WC_Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @return bool
	 */
	public function has_product() {
		return $this->get_product() instanceof WC_Product;
	}

	/**
	 * @return bool
	 */
	public function has_inner_message(): bool {
		if ( $this->has_inner_message ) {
			return true;
		}

		$content = is_array( $this->inner_message_info ) && isset( $this->inner_message_info['content'] ) ?
			$this->inner_message_info['content'] : '';

		return ! empty( $content );
	}

	/**
	 * @return string
	 */
	public function get_inner_message() {
		return $this->inner_message;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Read card inner message
	 */
	public function read_inner_message() {
		if ( count( $this->get_option() ) < 1 ) {
			return;
		}

		$meta_keys = [ 'inner message' ];

		foreach ( $this->get_option() as $option ) {
			if ( ! isset( $option['name'], $option['value'] ) ) {
				continue;
			}
			if ( in_array( strtolower( $option['name'] ), $meta_keys ) ) {
				$this->inner_message     = $option['value'];
				$this->has_inner_message = true;
			}
		}
	}

	/**
	 * Read card size
	 */
	protected function read_card_size() {
		$sku_start        = strtolower( substr( $this->sku, 0, 2 ) );
		$valid_card_sizes = [ 'a4', 'a5', 'a6' ];

		foreach ( $valid_card_sizes as $size ) {
			if ( $size == $sku_start ) {
				$this->card_size = $size;
			}
		}
	}

	/**
	 * Get card size
	 *
	 * @return string
	 */
	public function get_card_size(): string {
		return $this->card_size;
	}

	/**
	 * Check if square card
	 *
	 * @return bool
	 */
	public function is_square_card(): bool {
		return $this->get_card_size() == 'square';
	}

	/**
	 * Check if a4 card
	 *
	 * @return bool
	 */
	public function is_a4_card(): bool {
		return $this->get_card_size() == 'a4';
	}

	/**
	 * Check if a5 card
	 *
	 * @return bool
	 */
	public function is_a5_card(): bool {
		return $this->get_card_size() == 'a5';
	}

	/**
	 * Check if a6 card
	 *
	 * @return bool
	 */
	public function is_a6_card(): bool {
		return $this->get_card_size() == 'a6';
	}

	/**
	 * @return array
	 */
	public function get_inner_message_info(): array {
		$data    = [];
		$content = is_array( $this->inner_message_info ) && isset( $this->inner_message_info['content'] ) ?
			$this->inner_message_info['content'] : '';
		if ( ! empty( $content ) ) {
			$data              = $this->inner_message_info;
			$data['page_size'] = $this->get_card_size();
		}

		return $data;
	}

	/**
	 * @return int
	 */
	public function get_ship_station_order_id(): int {
		return $this->ship_station_order_id;
	}

	/**
	 * @return int
	 */
	public function get_total_quantities_in_order(): int {
		return (int) $this->total_quantities_in_order;
	}

	/**
	 * @return int
	 */
	public function get_card_id(): int {
		return $this->card_id;
	}

	/**
	 * @return int
	 */
	public function get_designer_id(): int {
		return $this->designer_id;
	}
}
