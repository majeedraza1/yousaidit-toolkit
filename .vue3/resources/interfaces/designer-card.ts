type TYPE_SECTION_TYPE = 'input-image' | 'static-image' | 'static-text' | 'input-text';
type TYPE_ALIGNMENT = "left" | "center" | 'right';

interface UploadedAttachmentInterface {
  attachment_url: string;
  full: { src: string, width: number; height: number }
  id: number;
  thumbnail: { src: string; width: number; height: number }
  title: string;
  token: string;
}

interface PaginationDataInterface {
  total_items: number;
  per_page: number;
  current_page: number;
  total_pages?: number;
}

interface StatusDataInterface {
  active: boolean;
  count: number;
  key: string;
  label: string;
}

interface CardOptionInterface {
  title: string,
  description: string,
  sizes: string[],
  categories_ids: string[],
  tags_ids: string[],
  attributes: Record<string, (number | string)[]>,
  market_places: string[],
  rude_card: 'no' | 'yes',
  has_suggest_tags: 'no' | 'yes',
  suggest_tags: '',
}

interface StandardCardBaseInterface extends CardOptionInterface {
  image_id: number,
  image: null | UploadedAttachmentInterface,
}

interface DynamicCardTextOptionsInterface {
  fontFamily: string,
  size: string | number,
  align: TYPE_ALIGNMENT,
  color: string,
  rotation: number,
  spacing: number
}

interface DynamicCardImageOptionsInterface {
  img?: {
    id: number,
    src: string,
    width: number,
    height: number,
  },
  width: number | string,
  height: number | string,
  align: TYPE_ALIGNMENT
}

interface DynamicCardItemBaseInterface {
  label: string,
  section_type: TYPE_SECTION_TYPE,
  position: {
    left: string | number,
    top: string | number
  },
}

interface DynamicCardTextSectionInterface extends DynamicCardItemBaseInterface {
  text: string,
  placeholder: string,
  textOptions: DynamicCardTextOptionsInterface,
}

interface DynamicCardImageSectionInterface extends DynamicCardItemBaseInterface {
  imageOptions: DynamicCardImageOptionsInterface;
  image?: {};
}

type DynamicCardItemInterface = {
  label: string;
  section_type: string;
  text: string,
  placeholder: string,
  textOptions: DynamicCardTextOptionsInterface,
  imageOptions: DynamicCardImageOptionsInterface;
  image?: {};
}

interface DynamicCardBackgroundImageInterface {
  src: string;
  id?: number;
  width?: number;
  height?: number;
}

interface DynamicCardPayloadInterface {
  card_size: 'square';
  card_bg_type: 'color' | 'image';
  card_bg_color: string;
  card_background: [] | DynamicCardBackgroundImageInterface;
  card_items: DynamicCardItemInterface[];
}

interface PhotoCardBaseInterface extends CardOptionInterface {
  main_image_id: number;
  demo_image_id: number;
  main_image: null | UploadedAttachmentInterface;
  demo_image: null | UploadedAttachmentInterface;
  dynamic_card_payload?: DynamicCardPayloadInterface;
}

interface TextCardBaseInterface extends CardOptionInterface {
  main_image_id: number;
  main_image: null | UploadedAttachmentInterface;
  dynamic_card_payload?: DynamicCardPayloadInterface;
}

interface DesignerCardImageInterface {
  height: number;
  id: number;
  path: string;
  title: string;
  url: string;
  width: number;
}

interface DesignerCardTermInterface {
  id: number;
  title: string;
}

interface DesignerCardAttributeInterface {
  attribute_id: number;
  attribute_name: string;
  attribute_label: string;
  options: DesignerCardTermInterface[];
}

interface DesignerCardModelInterface {
  id: number;
  card_type: 'dynamic' | 'static';
  card_title: string;
  description: string;
  card_sizes: string[];
  categories: DesignerCardTermInterface[];
  tags: DesignerCardTermInterface[];
  sizes: DesignerCardTermInterface[];
  attributes: DesignerCardAttributeInterface[];
  image_id: number;
  product_thumbnail_id: number;
  image: DesignerCardImageInterface;
  product_thumbnail: DesignerCardImageInterface;
  status: string;
  product_id: number;
  product_url?: string;
  product_edit_url?: string;

  [key: string]: any;
}

interface ServerCardCollectionResponseInterface {
  items: DesignerCardModelInterface[];
  pagination: PaginationDataInterface;
  maximum_allowed_card: number;
  can_add_dynamic_card: boolean;
  total_cards: number;
  statuses: StatusDataInterface[];
  counts?: Record<string, number>;
}

export type {
  TYPE_SECTION_TYPE,
  UploadedAttachmentInterface,
  CardOptionInterface,
  StandardCardBaseInterface,
  PhotoCardBaseInterface,
  TextCardBaseInterface,
  DynamicCardItemInterface,
  DynamicCardTextSectionInterface,
  DynamicCardImageSectionInterface,
  DynamicCardPayloadInterface,
  ServerCardCollectionResponseInterface,
  PaginationDataInterface,
  StatusDataInterface,
  DesignerCardModelInterface,
  DynamicCardImageOptionsInterface
}