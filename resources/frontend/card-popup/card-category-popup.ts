import {getRequest} from './utils';
import {createModal, Spinner} from "./components";

const openContentOnModal = (content: string) => {
  let modal = document.querySelector('#card-category-modal');
  let container = document.querySelector('#card-category-popup-container') as HTMLElement;
  if (!modal) {
    modal = createModal(container, 'card-category-modal');
    modal.addEventListener('close',()=>{
      document.dispatchEvent(new CustomEvent('close.CardCategoryModal'));
    })
  }

  const modalContent = modal.querySelector('.shapla-modal-content') as HTMLElement;
  modalContent.innerHTML = content;
  modal.classList.add('is-active');

  document.dispatchEvent(new CustomEvent('show.CardCategoryPopup', {
    detail: {
      card_type: (modalContent.querySelector('[name="card_type"]') as HTMLInputElement).value,
      card_size: (modalContent.querySelector('[name="attribute_pa_size"]') as HTMLInputElement).value,
      product_id: (modalContent.querySelector('[name="product_id"]') as HTMLInputElement).value,
    }
  }))
}
const getAjaxUrl = (productId: number) => {
  const url = new URL(window.StackonetToolkit.ajaxUrl);
  url.searchParams.append('action', 'yousaidit_loop_product_popup');
  url.searchParams.append('product_id', productId.toString());

  return url.toString();
}

document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  if (element.classList.contains('card-category-popup')) {
    event.preventDefault();
    const productId = element.dataset.productId
    if (productId) {
      Spinner.show();
      getRequest(getAjaxUrl(productId))
        .then(data => {
          openContentOnModal(data.popup as string);
        })
        .finally(() => {
          Spinner.hide();
        })
    }
  }

  if (element.classList.contains('yousaidit_wishlist')) {
    event.preventDefault();
    const url = ((element as HTMLAnchorElement)
      .getAttribute('href') ?? '').replace(/&amp;/g, "&");
    getRequest(url)
      .then(response => {
        const data = response.data as { href: string; cssClass: string[]; title: string }
        element.setAttribute('class', '');
        element.setAttribute('title', data.title);
        element.setAttribute('href', decodeURIComponent(data.href.replace(/&amp;/g, "&")));
        element.classList.add(...data.cssClass);
        window.jQuery(document.body).trigger('yith_wcwl_reload_fragments');
      })
      .finally(() => {
        Spinner.hide();
      })
  }
})
