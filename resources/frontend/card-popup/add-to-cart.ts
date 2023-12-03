import Vue from "vue";
import SingleProductDynamicCard from "../dynamic-card/SingleProductDynamicCard.vue";
import dynamicCardStore from "../dynamic-card/store";
import {closeModal, refreshBodyClass} from "./modal";

import {createEl, Notify, Spinner} from "@shapla/vanilla-components";
import {postRequest} from "./utils";
import {
  DynamicCardPropsInterface,
  InnerMessagePropsInterface,
  LeftInnerMessagePropsInterface,
  RightInnerMessagePropsInterface
} from "../../utils/interfaces";
import axios from "axios";

if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
  axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
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
    refreshBodyClass();
  }).catch(error => {
    Notify.warning('Something went wrong', 'Error!');
  }).finally(() => {
    window.jQuery(document.body).trigger('wc_fragment_refresh');
    Spinner.hide();
  })
}

const dynamicCardContainer = (product_id: number | string, card_size: string): Promise<HTMLElement> => {
  return new Promise(resolve => {
    let el = document.querySelector('#dynamic-card-container');
    if (el) {
      resolve(el);
    } else {
      el = createEl('div',
        {
          id: 'dynamic-card-container',
          'data-card-size': card_size,
          'data-product-id': product_id.toString(),
        },
        [
          createEl('div', {id: 'dynamic-card'})
        ]
      )

      document.body.append(el);
      resolve(el);
    }
  })
}

const removeDynamicCardContainerIfExists = () => {
  let el = document.body.querySelector('#dynamic-card-container');
  if (el) {
    el.remove();
  }
}

const addAMessage = (element: HTMLElement) => {
  // window.console.log('Add a message', element);
}

const personalise = (element: HTMLElement) => {
  const product_id = (element.querySelector('[name="product_id"]') as HTMLInputElement).value;
  const card_type = (element.querySelector('[name="card_type"]') as HTMLInputElement).value;
  const card_size = (element.querySelector('[name="attribute_pa_size"]') as HTMLInputElement).value;
  let dynamicCardEl = document.querySelector('#dynamic-card');
  dynamicCardContainer(product_id, card_size).then(() => {
    new Vue({
      el: '#dynamic-card',
      store: dynamicCardStore,
      render: h => h(SingleProductDynamicCard)
    });

    document.dispatchEvent(new CustomEvent('show.DynamicCardModal', {
      detail: {
        card_type: card_type,
        card_size: card_size,
        product_id: product_id,
      }
    }))
  });

  document.dispatchEvent(new CustomEvent('show.DynamicCardModal', {
    detail: {
      card_type: card_type,
      card_size: card_size,
      product_id: product_id,
    }
  }))
}

document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  const popup = element.closest('.card-category-popup-content') as HTMLElement;
  if (element.classList.contains('card-popup-add-to-cart')) {
    event.preventDefault();
    addToCart(popup);
  } else if (element.closest('.card-popup-add-to-cart')) {
    event.preventDefault();
    addToCart(popup);
  } else if (element.closest('.card-popup-add-a-message')) {
    event.preventDefault();
    addAMessage(popup);
  } else if (element.closest('.card-popup-customize-dynamic-card')) {
    event.preventDefault();
    personalise(popup);
  }
});

const key_to_field = {
  message: 'content',
  font_family: 'font',
  font_size: 'size',
  alignment: 'align',
  color: 'color',
}

const submitFormToServer = (data: InnerMessagePropsInterface | DynamicCardPropsInterface) => {
  const form = document.querySelector('.card-popup-form') as HTMLFormElement;
  for (const [key, value] of Object.entries(data.right)) {
    let suffix = Object.keys(key_to_field).includes(key) ? key_to_field[key as keyof RightInnerMessagePropsInterface] : key,
      idAttribute = `#_inner_message_${suffix}`;
    const inputEl = form.querySelector(idAttribute) as HTMLInputElement;
    if (inputEl) {
      inputEl.value = value;
    }
  }
  for (const [key, value] of Object.entries(data.left)) {
    let suffix = Object.keys(key_to_field).includes(key) ? key_to_field[key as keyof LeftInnerMessagePropsInterface] : key,
      idAttribute = `#_inner_message2_${suffix}`;
    const inputEl = form.querySelector(idAttribute) as HTMLInputElement;
    if (inputEl) {
      inputEl.value = value;
    }
  }
  if (data.payload) {
    let fieldsContainer = form.querySelector('#_dynamic_card_fields');
    data.payload.card_items.forEach((item, index) => {
      let inputId = `#_dynamic_card_input-${index}`
      if (['static-text', 'input-text'].indexOf(item.section_type) !== -1) {
        fieldsContainer.querySelector(inputId).value = item.text;
      }
      if (['static-image', 'input-image'].indexOf(item.section_type) !== -1) {
        fieldsContainer.querySelector(inputId).value = item.image.id || item.imageOptions.img.id;
      }
    });
  }
  const formData = new FormData(form);
  const url = form.getAttribute('action');
  Spinner.show();
  postRequest(url, formData).then(response => {
    Notify.primary('Product has been added to cart', 'Success!');
    const modal = form.closest('.shapla-modal');
    closeModal(modal);
    refreshBodyClass();
    removeDynamicCardContainerIfExists();
  }).catch(() => {
    Notify.warning('Something went wrong', 'Error!');
  }).finally(() => {
    window.jQuery(document.body).trigger('wc_fragment_refresh');
    Spinner.hide();
  })
}

document.body.addEventListener('update.CardCategoryPopup', (event: CustomEvent) => {
  const data = event.detail as InnerMessagePropsInterface;
  submitFormToServer(data);
})

document.addEventListener('submit.DynamicCard', (event: CustomEvent) => {
  const detail = event.detail as DynamicCardPropsInterface;
  submitFormToServer(detail);
})

document.addEventListener('show.CardCategoryPopup', (event: CustomEvent) => {
  const detail = event.detail as {
    product_id: string,
    card_type: string,
    card_size: string
  }
  if ("dynamic" === detail.card_type) {
    dynamicCardContainer(detail.product_id, detail.card_size).then(() => {

    });
  }
})

document.addEventListener('close.CardCategoryModal', () => {
  removeDynamicCardContainerIfExists()
})
