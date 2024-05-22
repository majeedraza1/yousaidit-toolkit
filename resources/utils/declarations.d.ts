import {InnerMessagePropsInterface} from "./interfaces";


declare global {
  interface GlobalEventHandlersEventMap {
    "update.CardCategoryPopup": CustomEvent<InnerMessagePropsInterface>;
  }

  interface Window {
    StackonetToolkit: {
      ajaxUrl: string
      restRoot: string
      restNonce?: string
      occasions: { slug: string; label: string; menu_order?: number; }[];
      recipients: { slug: string; label: string; menu_order?: number; }[];
      topics: { slug: string; label: string; menu_order?: number; }[];
      common_holidays: { label: string; date_string: string; }[];
      special_holidays: Record<string, { label: string; date: string; }[]>;
      fonts: { key: string }[],
    }
    DesignerProfile: {
      fonts: { key: string }[],
      privacyPolicyUrl: string;
      termsUrl: string;
      restNonce: string;
    }
  }
}
