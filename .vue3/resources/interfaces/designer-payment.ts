interface PaymentStatusInterface {
  key: string;
  label: string;
  count: number;
  status?: string;
}

interface DesignerPaymentInterface {
  payment_id: string;
}

export type {
  PaymentStatusInterface,
  DesignerPaymentInterface
}
