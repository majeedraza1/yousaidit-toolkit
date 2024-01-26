import {InnerMessagePropsInterface} from "./interfaces";
import {ReminderGroupInterface, ReminderInterface} from "../interfaces/reminders.ts";


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
      lostPasswordUrl: string;
      logoUrl: string;
      logOutUrl: string;
      termsUrl: string;
      restNonce: string;
      categories: { id: number; name: string; parent: number }[]
      tags: { id: number; name: string; }[]
      attributes: { attribute_name: string; attribute_label: string; options: { id: number; name: string }[] }[]
      marketPlaces: { key: string; label: string; logo: string; storeId: number }[]
      card_sizes: { term_id: number; slug: string; name: string }[];
      user: { id: number; display_name: string; avatar_url: string; author_posts_url: string; };
      user_card_categories: number[];
    }
    YousaiditMyAccountReminders: {
      reminders: ReminderInterface[];
      groups: ReminderGroupInterface[];
      countries: Record<string, string>;
      states: Record<string, Record<string, string>>;
    }
  }
}
