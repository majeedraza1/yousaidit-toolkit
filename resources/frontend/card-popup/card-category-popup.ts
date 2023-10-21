import {getRequest} from './utils';
import {createModal, Spinner} from "./components";

const showSpinner = () => {
  Spinner.show('card-category-spinner');
}

const hideSpinner = () => {
  Spinner.hide('card-category-spinner')
}

const openContentOnModal = (content: string) => {
  let modal = document.querySelector('#card-category-modal');
  if (!modal) {
    modal = createModal('card-category-modal');
  }

  const modalContent = modal.querySelector('.shapla-modal-content') as HTMLElement;
  modalContent.innerHTML = content;
  modal.classList.add('is-active');
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
