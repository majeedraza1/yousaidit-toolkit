type TYPE_CARD_SIZE = "square" | "a4";
type TYPE_CARD_TYPE = "dynamic" | "static";
type TYPE_MARKETPLACE = "amazon" | "ebay" | "etsy" | "yousaidit" | "yousaidit-trade";

interface UploadedAttachmentInterface {
  attachment_url: string;
  full: { src: string, width: number; height: number }
  id: number;
  thumbnail: { src: string; width: number; height: number }
  title: string;
  token: string;
}

interface DesignerInterface {
  id: number;
  email: string;
  display_name: string;
  first_name: string;
  last_name: string;
  description: string;
  user_url: string;
  user_login: string;
  instagram_url: string;
  avatar_url: string;
  cover_photo_url: string;
  card_logo_url: string;
  total_cards: number;
  profile_base_url: string;
  maximum_allowed_card: string;
  can_add_dynamic_card: string;
  total_sales: number;
  paypal_email: string;
  location: string;
  business_name: string;
  vat_registration_number: string;
  vat_certificate_issue_date: string;
  avatar_id: number;
  cover_photo_id: number;
  card_logo_id: number;
  business_address: {
    address_1: string;
    address_2: string;
    city: string;
    post_code: string;
    country: string;
    state: string;
  },
  formatted_address: string;
  total_commission?: number;
  unpaid_commission?: number;
  paid_commission?: number;
}

interface CardStatusInterface {
  key: string,
  label: string;
  count: number
  color: string;
}

interface DesignerServerResponseInterface {
  designer: DesignerInterface;
  statuses: CardStatusInterface[],
  total_commission: string,
  unpaid_commission: string,
  paid_commission: string,
  unique_customers: number,
  total_orders: number;
  maximum_allowed_card: number;
  total_cards: number;
  can_add_dynamic_card: boolean,
}

interface DesignerCardBaseInterface {
  card_type: TYPE_CARD_TYPE;
  card_sizes: TYPE_CARD_SIZE[];
  card_title: string;
  description: string;
}

interface DesignerStandardCardBaseInterface {
  image_id: number,
  image: null | UploadedAttachmentInterface,
  title: string,
  description: string,
  sizes: string[],
  categories_ids: string[],
  tags_ids: string[],
  attributes: Record<string, number[]>,
  gallery_images_ids: [],
  market_places: ['yousaidit'],
  pdf_ids: {},
  rude_card: 'no' | 'yes',
  has_suggest_tags: 'no' | 'yes',
  suggest_tags: '',
}

interface DesignerCardAttributeInterface {
  attribute_name: string;
  attribute_label: string;
  options: { id: number, title: string; }[]
}

interface DesignerCardInterface extends DesignerCardBaseInterface {
  id: number;
  categories: { id: number, title: string }[],
  tags: { id: number, title: string }[],
  sizes: { id: number, title: string }[],
  attributes: DesignerCardAttributeInterface[],
  image: { id: number; title: string; url: string; path: string; width: number; height: number; },
  product_thumbnail: { id: number; title: string; url: string; path: string; width: number; height: number; },
  gallery_images: any[],
  pdf_data: Record<(keyof TYPE_CARD_SIZE | string), { "id": number, "title": string, "url": string }[]>,
  "total_sale": number,
  "commission": {
    "commission_type": "fix",
    "commission_amount": {
      "square": number
    }
  },
  marketplace_commission: Record<keyof TYPE_MARKETPLACE, Record<keyof TYPE_CARD_SIZE, string | number>>,
  "product_id": number,
  "designer_user_id": number,
  "rude_card": false,
  "status": "accepted" | "processing" | "trash",
  "card_sku": string,
  "suggest_tags": string,
  "market_place": TYPE_MARKETPLACE[],
  "comments_count": number,
  "created_at": string,
  "updated_at": string,
  "product_url": string,
  "product_edit_url": string,
  designer: DesignerInterface
}

interface DesignerProfileFontInterface {
  key: string;
  label: string;
  fontUrl: string;
  for_public: boolean;
  for_designer: boolean;
}

interface DesignerProfileInlineGeneralDataInterface {
  restRoot: string;
  restNonce: false | string;
  siteTitle: string;
  logoUrl: string;
  card_sizes: { term_id: number; slug: string; name: string }[];
  categories: { id: number; name: string; parent: number }[];
  mug_categories: { id: number; name: string; parent: number }[];
  tags: { id: number; name: string; }[];
  attributes: { attribute_name: string; attribute_label: string; options: { id: number; name: string }[] }[];
  privacyPolicyUrl: string;
}

interface DesignerProfileInlineDataInterface extends DesignerProfileInlineGeneralDataInterface {
  termsUrl: string;
  lostPasswordUrl: string;
  logOutUrl: string;
  enabled_card_types: string[];
  user: { id: number; display_name: string; avatar_url: string; author_posts_url: string; };
  user_card_categories: number[];
  order_statuses: Record<string, string>;
  marketPlaces: { key: string; label: string; logo: string; storeId: number }[];
  fonts: DesignerProfileFontInterface[];
  templates: { ps: string; ai: string };
  sampleCards: { standardCardUrl: string; textCardUrl: string; photoCardUrl: string; mugUrl: string; }
}

interface CommissionInterface {
  commission_id: number;
  card_id: number;
  designer_id: number;
  customer_id: number;
  order_id: number;
  order_item_id: number;
  order_quantity: number;
  item_commission: number;
  total_commission: number;
  created_at: string;
  updated_at: string;
}

interface StaticCardArgumentsInterface {
  card_type: 'static';
  card_title: string;
  card_sizes: TYPE_CARD_SIZE[];
  categories_ids: number[];
  image_id: number;
  description?: string;
  tags_ids?: number[];
  attributes?: number[];
  rude_card?: 'yes' | 'no' | boolean;
  suggest_tags?: string;
  market_places?: string[];
}

export type {
  TYPE_CARD_SIZE,
  DesignerProfileInlineGeneralDataInterface,
  DesignerProfileInlineDataInterface,
  DesignerInterface,
  DesignerCardBaseInterface,
  DesignerCardInterface,
  CardStatusInterface,
  DesignerServerResponseInterface,
  CommissionInterface,
  UploadedAttachmentInterface,
  DesignerStandardCardBaseInterface,
  StaticCardArgumentsInterface,
  DesignerProfileFontInterface
}
