import {Notify, Spinner} from "./components";
import {postRequest} from "./utils";


const addToCart = (element: HTMLElement) => {
  const form = element.querySelector('.card-popup-form') as HTMLFormElement;
  const formData = new FormData(form);
  const url = form.getAttribute('action');
  Spinner.show();
  postRequest(url, formData).then(response => {
    Notify.primary('Product has been added to cart', 'Success!');
  }).catch(error => {
    Notify.warning('Something went wrong', 'Error!');
  }).finally(() => {
    window.jQuery(document.body).trigger('wc_fragment_refresh');
    Spinner.hide();
  })
}

const addAMessage = (element: HTMLElement) => {
  window.console.log('Add a message', element);
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