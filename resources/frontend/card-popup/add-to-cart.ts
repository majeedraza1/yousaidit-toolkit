import {Notify, Spinner} from "./components";
import {postRequest} from "./utils";

interface LeftInnerMessagePropsInterface {
  alignment: string;
  color: string;
  font_family: string;
  font_size: string;
  message: string;
}

interface RightInnerMessagePropsInterface extends LeftInnerMessagePropsInterface {
  type: string
  video_id: number;
}

interface InnerMessagePropsInterface {
  left: LeftInnerMessagePropsInterface,
  right: RightInnerMessagePropsInterface
}

declare global {
  interface GlobalEventHandlersEventMap {
    "update.CardCategoryPopup": CustomEvent<InnerMessagePropsInterface>;
  }
}


const addToCart = (element: HTMLElement) => {
  const form = element.querySelector('.card-popup-form') as HTMLFormElement;
  const formData = new FormData(form);
  const url = form.getAttribute('action');
  Spinner.show();
  postRequest(url, formData).then(() => {
    Notify.primary('Product has been added to cart', 'Success!');
    const modal = form.closest('.shapla-modal');
    if (modal) {
      if (modal.classList.contains('is-active')) {
        modal.classList.remove('is-active');
      }
    }
  }).catch(error => {
    Notify.warning('Something went wrong', 'Error!');
  }).finally(() => {
    window.jQuery(document.body).trigger('wc_fragment_refresh');
    Spinner.hide();
  })
}

const addAMessage = (element: HTMLElement) => {
  // window.console.log('Add a message', element);
}

document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  const popup = element.closest('.card-category-popup-content');
  if (element.classList.contains('card-popup-add-to-cart')) {
    event.preventDefault();
    addToCart(popup);
  } else if (element.closest('.card-popup-add-to-cart')) {
    event.preventDefault();
    addToCart(popup);
  } else if (element.closest('.card-popup-add-a-message')) {
    event.preventDefault();
    addAMessage(popup);
  }
});

const key_to_field = {
  message: 'content',
  font_family: 'font',
  font_size: 'size',
  alignment: 'align',
  color: 'color',
}

document.body.addEventListener('update.CardCategoryPopup', (event: CustomEvent) => {
  const data = event.detail as InnerMessagePropsInterface;
  const form = document.querySelector('.card-popup-form') as HTMLFormElement;
  for (const [key, value] of Object.entries(data.right)) {
    let suffix = Object.keys(key_to_field).includes(key) ? key_to_field[key] : key,
      idAttribute = `#_inner_message_${suffix}`;
    const inputEl = form.querySelector(idAttribute) as HTMLInputElement;
    if (inputEl) {
      inputEl.value = value;
    }
  }
  for (const [key, value] of Object.entries(data.left)) {
    let suffix = Object.keys(key_to_field).includes(key) ? key_to_field[key] : key,
      idAttribute = `#_inner_message2_${suffix}`;
    const inputEl = form.querySelector(idAttribute) as HTMLInputElement;
    if (inputEl) {
      inputEl.value = value;
    }
  }
  const formData = new FormData(form);
  const url = form.getAttribute('action');
  Spinner.show();
  postRequest(url, formData).then(response => {
    Notify.primary('Product has been added to cart', 'Success!');
    const modal = form.closest('.shapla-modal');
    if (modal) {
      if (modal.classList.contains('is-active')) {
        modal.classList.remove('is-active');
      }
    }
  }).catch(error => {
    Notify.warning('Something went wrong', 'Error!');
  }).finally(() => {
    window.jQuery(document.body).trigger('wc_fragment_refresh');
    Spinner.hide();
  })
})