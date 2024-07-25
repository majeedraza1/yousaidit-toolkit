interface DesignerCommission {
  commission_id: number;
  designer_id: number;
  customer_id: number;
  card_id: number;
  order_id: number;
  order_item_id: number;
  order_quantity: number;
  card_size: string | 'square';
  order_status: string;
  order_edit_url: string;
}


export type {
  DesignerCommission
}