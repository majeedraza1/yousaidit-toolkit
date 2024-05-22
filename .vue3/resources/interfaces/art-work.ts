interface ProductArtWorkInterface {
  id: number;
  title?: string;
  url?: string;
  thumb_url?: string;
  width?: number;
  height?: number;
}

interface ArtWorkProductInterface {
  id: number;
  title: string;
  product_sku: string;
  product_type: 'simple',
  attributes: Record<string, any>,
  art_work: ProductArtWorkInterface;
  edit_product_url?: string;
}

interface ArtWorkOrderProductInterface {
  "id": number,
  "title": string,
  "product_sku": string,
  "edit_product_url": string,
  "options": { "name": string, "value": string }[      ],
  "product_thumbnail": string,
  "card_id": number,
  "designer_id": number,
  "commission": number,
  "total_commission": number,
  "quantity": number,
  "art_work": ProductArtWorkInterface,
  "attached_file": string,
  "pdf_id": number,
  "has_inner_message": boolean,
  "inner_message": null | string,
  "card_size": "square",
  "shipstation_item_id": number
}

interface ArtWorkOrderInterface {
  "orderId": number;
  "storeId": number;
  "storeName": number;
  "orderStatus": "awaiting_shipment",
  "order_date": string;
  "customer_full_name": string
  "customer_email": string
  "customer_phone": string
  "customer_notes": null | string,
  "internal_notes": string
  "shipping_address": string
  "shipping_service": "Standard Shipping",
  "products": ArtWorkOrderProductInterface[],
  "has_inner_message": boolean;
  "contain_mixed_items": string;
  "card_sizes": string[],
  "contain_mixed_card_sizes": boolean,
  "custom_info": string[],
  "door_delivery": "No info" | "Yes",
  "print_shipping_label_url": string;
}

export type {
  ProductArtWorkInterface,
  ArtWorkProductInterface,
  ArtWorkOrderProductInterface,
  ArtWorkOrderInterface,
}