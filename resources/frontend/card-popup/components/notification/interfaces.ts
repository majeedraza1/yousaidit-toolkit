type NOTIFICATION_TYPE = "primary" | "success" | "info" | "warning" | "error";
type NOTIFICATION_POSITION =
  "top-left"
  | "top-center"
  | "top-right"
  | "bottom-left"
  | "bottom-center"
  | "bottom-right"
  | "center-center";

interface NotificationItemPropsInterface {
  message?: string;
  id?: string;
  type?: NOTIFICATION_TYPE;
  title?: string;
  timeout?: number;
  showDismisses?: boolean;
}

interface NotificationContainerPropsInterface {
  showDismisses?: boolean;
  timeout?: number;
  position?: NOTIFICATION_POSITION;
}

export type {
  NOTIFICATION_TYPE,
  NotificationItemPropsInterface,
  NotificationContainerPropsInterface
}