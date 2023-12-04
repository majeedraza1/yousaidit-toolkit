type TYPE_SECTION_TYPE = 'input-image' | 'static-image';
type TYPE_ALIGNMENT = "left" | "center" | 'right';

interface DynamicCardTextOptionsInterface {
  fontFamily: string,
  size: string | number,
  align: TYPE_ALIGNMENT,
  color: string,
  rotation: number,
  spacing: number
}

interface DynamicCardImageOptionsInterface {
  img: {
    "src": string,
    "width": number,
    "height": number,
    "id": number
  },
  width: number | string,
  height: number | string,
  align: TYPE_ALIGNMENT
}

interface DynamicCardItemInterface {
  label: string,
  section_type: TYPE_SECTION_TYPE,
  position: {
    left: string | number,
    top: string | number
  },
  text: string,
  placeholder: string,
  textOptions: DynamicCardTextOptionsInterface,
  imageOptions: DynamicCardImageOptionsInterface;
  image?: {};
}

interface DynamicCardBackgroundImageInterface {
  src: string;
}

interface DynamicCardPayloadInterface {
  card_size: 'square';
  card_bg_type: 'color' | 'image';
  card_bg_color: string;
  card_background: [] | DynamicCardBackgroundImageInterface;
  card_items: DynamicCardItemInterface[];
}

export type {
  DynamicCardPayloadInterface
}