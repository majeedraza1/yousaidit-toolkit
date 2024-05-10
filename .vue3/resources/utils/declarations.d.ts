import {InnerMessagePropsInterface} from "./interfaces";
import {ReminderGroupInterface, ReminderInterface} from "../interfaces/reminders.ts";
import {DesignerProfileFontInterface, DesignerProfileInlineDataInterface} from "../interfaces/designer.ts";

interface YousaiditFontsListInterface {
  label: string;
  fontFamily: string;
  for_public: boolean;
  for_designer: boolean;
}

declare global {
  interface JQuery {
    select2(): JQuery;
  }

  interface GlobalEventHandlersEventMap {
    "update.CardCategoryPopup": CustomEvent<InnerMessagePropsInterface>;
  }

  interface Window {
    StackonetToolkit: {
      homeUrl: string
      ajaxUrl: string
      restRoot: string
      designerProfileBaseUrl: string
      restNonce?: string
      occasions: { slug: string; label: string; menu_order?: number; }[];
      recipients: { slug: string; label: string; menu_order?: number; }[];
      topics: { slug: string; label: string; menu_order?: number; }[];
      common_holidays: { label: string; date_string: string; }[];
      special_holidays: Record<string, { label: string; date: string; }[]>;
      fonts: DesignerProfileFontInterface[],
      pdfSizes: Record<string, number[]>;
      isUserLoggedIn: boolean;
      isRecordingEnabled: boolean;
      maxUploadLimitText: string;
      fileUploaderTermsHTML: string;
      videoMessagePriceHTML: string;
      qrCodePlayInfo: string;
      placeholderUrlIM: string;
      placeholderUrlIML: string;
      placeholderUrlIMR: string;
      privacyPolicyUrl: string;
      termsAndConditionsUrl: string;
      lostPasswordUrl: string;
      signupUrl: string;
      logOutUrl: string;
      stability_ai_enabled: boolean;
      stability_ai?: {
        occasions: { slug: string; label: string; menu_order?: number; }[];
        recipients: { slug: string; label: string; menu_order?: number; }[];
        moods: { slug: string; label: string; menu_order?: number; }[];
        style_presets: { slug: string; label: string; icon: string; }[];
      }
    }
    DesignerProfile: DesignerProfileInlineDataInterface;
    YousaiditMyAccountReminders: {
      reminders: ReminderInterface[];
      groups: ReminderGroupInterface[];
      countries: Record<string, string>;
      states: Record<string, Record<string, string>>;
    },
    YousaiditFontsList: YousaiditFontsListInterface[];
    wp: {
      media: any
    },
    clipboardData?: DataTransfer;
  }
}