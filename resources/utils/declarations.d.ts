import {InnerMessagePropsInterface} from "./interfaces";


declare global {
  interface GlobalEventHandlersEventMap {
    "update.CardCategoryPopup": CustomEvent<InnerMessagePropsInterface>;
  }
}
