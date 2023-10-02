/**
 * Create dynamic element
 *
 * @param {string} tagName
 * @param {object} attributes
 * @param {array} children
 * @returns {HTMLElement}
 */
const createEl = (tagName: string, attributes: Record<string, string> = {}, children: string[] | Node[] = []): HTMLElement => {
  let el = document.createElement(tagName);
  if (Object.keys(attributes).length) {
    Object.entries(attributes).forEach(([key, value]) => {
      el.setAttribute(key, value);
    })
  }
  if (children.length) {
    el.append(...children);
  }
  return el;
}
const createModal = () => {
  const bgEl = createEl('div', {class: 'shapla-modal-background is-dark'});
  const closeEl = createEl('span', {class: 'shapla-delete-icon is-large is-fixed', 'aria-label': 'close'});
  const modal = createEl(
    'div',
    {id: 'card-category-modal', class: 'shapla-modal',},
    [
      bgEl,
      createEl('div', {class: 'shapla-modal-content is-large shapla-modal-box'}),
    ]
  );

  document.body.append(modal);

  [bgEl, closeEl].forEach(element => {
    element.addEventListener('click', () => {
      if (modal.classList.contains('is-active')) {
        modal.classList.remove('is-active');
      }
    })
  })
  return modal;
}

const createSpinnerLayer = (index: number) => {
  return createEl('div', {class: `shapla-spinner__layer shapla-spinner__layer-${index}`}, [
    createEl('div', {class: 'shapla-spinner__circle-clipper shapla-spinner__left'}, [
      createEl('div', {class: 'shapla-spinner__circle'})
    ]),
    createEl('div', {class: 'shapla-spinner__gap-patch'}, [
      createEl('div', {class: 'shapla-spinner__circle'})
    ]),
    createEl('div', {class: 'shapla-spinner__circle-clipper shapla-spinner__right'}, [
      createEl('div', {class: 'shapla-spinner__circle'})
    ]),
  ])
}

const showSpinner = () => {
  const spinner = createEl('div',
    {id: 'card-category-spinner', class: 'shapla-spinner-container is-fixed'},
    [
      createEl('div', {class: 'shapla-spinner-inner'}, [
        createEl('div', {class: 'shapla-spinner is-default'}, [
          createSpinnerLayer(1),
          createSpinnerLayer(2),
          createSpinnerLayer(3),
          createSpinnerLayer(4),
        ])
      ])
    ]
  );

  document.body.append(spinner);
}

const hideSpinner = () => {
  const spinner = document.querySelector('#card-category-spinner');
  if (spinner) {
    spinner.remove();
  }
}

const openContentOnModal = (content: string) => {
  let modal = document.querySelector('#card-category-modal');
  if (!modal) {
    modal = createModal();
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

const request = (url: string) => {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.addEventListener("load", function () {
      const data = JSON.parse(xhr.responseText);
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(data);
      } else {
        reject(data);
      }
    });
    xhr.open("GET", url);
    xhr.send();
  })
}

document.addEventListener('DOMContentLoaded', () => {
  const allCookies = document.cookie.split("; ");
// .find((row) => row.startsWith("test2="));
  window.console.log(allCookies);
})

document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  if (element.classList.contains('card-category-popup')) {
    event.preventDefault();
    const productId = element.dataset.productId
    if (productId) {
      showSpinner();
      request(getAjaxUrl(productId))
        .then(data => {
          openContentOnModal(data.popup as string);
        })
        .finally(() => {
          hideSpinner();
        })
    }
  }
  if (element.hasAttribute('data-close')) {
    event.preventDefault();
    const closestModal = element.closest('.shapla-modal');
    if (closestModal && closestModal.classList.contains('is-active')) {
      closestModal.classList.remove('is-active');
    }
  }
  if (element.classList.contains('yousaidit_wishlist')) {
    event.preventDefault();
    const url = ((element as HTMLAnchorElement)
      .getAttribute('href') ?? '').replace(/&amp;/g, "&");
    request(url)
      .then(response => {
        const data = response.data as { href: string; cssClass: string[]; title: string }
        element.setAttribute('class', '');
        element.setAttribute('title', data.title);
        element.setAttribute('href', decodeURIComponent(data.href.replace(/&amp;/g, "&")));
        element.classList.add(...data.cssClass);
        window.jQuery(document.body).trigger('yith_wcwl_reload_fragments');
      })
      .finally(() => {
        hideSpinner();
      })
  }
})
