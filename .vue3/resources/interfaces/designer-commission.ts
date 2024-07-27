interface DesignerCommissionInterface {
  commission_id: number;
  card_id: number;
  designer_id: number;
  customer_id: number;
  order_id: number;
  order_item_id: number;
  wc_order_id: number;
  wc_order_item_id: number;
  order_quantity: number;
  item_commission: number;
  total_commission: number;
  card_size: string | 'square';
  marketplace: string | 'yousaidit';
  payment_status: string;
  payment_id: null | string;
  order_status: string;
  created_via: string;
  created_at: string;
  updated_at: string;
  product_title: string;
  designer_name: string;
  order_edit_url: string;
  pdf_url: string;
}

export type {
  DesignerCommissionInterface
}