interface SelectOptionInterface {
  label: string;
  value: number | string;
}

interface ReminderUserInterface {
  id: number;
  display_name: string;
  edit_url: string;
}

interface ReminderInterface {
  id: number;
  user_id: number;
  reminder_group_id: number;
  name: string;
  occasion_date: string;
  remind_days_count: number;
  remind_date: string;
  first_name: string;
  last_name: string;
  address_line1: string;
  address_line2: string;
  postal_code: string;
  city: string;
  state: string;
  country_code: string;
  is_recurring: boolean | 0 | 1;
  has_custom_address: boolean | 0 | 1;
  is_in_queue: boolean | 0 | 1;
}

interface ReminderExtendedInterface extends ReminderInterface {
  user: ReminderUserInterface;
  email_template_url: string;
}

interface ReminderGroupInterface {
  id: number;
  title: string;
  product_categories: number[];
  cta_link: string;
  menu_order: number;
  occasion_date: string;
  primary_category_url?: string;
  email_template_url?: string;
}

interface ReminderQueueInterface {
  id: number;
}

interface PaginationDataInterface {
  total_items: number;
  per_page: number;
  current_page: number;
  total_pages?: number;
}

export type {
  ReminderInterface,
  ReminderExtendedInterface,
  ReminderGroupInterface,
  ReminderQueueInterface,
  ReminderUserInterface,
  SelectOptionInterface,
  PaginationDataInterface
}
