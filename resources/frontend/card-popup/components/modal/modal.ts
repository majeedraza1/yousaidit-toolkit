import {createEl} from "../../utils";

declare global {
  interface ElementEventMap {
    "close.ShaplaModal": CustomEvent<{ via: string }>
  }
}

const refreshBodyClass = (active: boolean = false) => {
  const body = document.querySelector("body") as HTMLBodyElement;
  if (active) {
    return body.classList.add("has-shapla-modal");
  }
  setTimeout(() => {
    if (body.querySelectorAll(".shapla-modal.is-active").length === 0) {
      body.classList.remove("has-shapla-modal");
    }
  }, 50);
};

const closeModal = (modal: Element | null, detail = {}) => {
  if (modal && modal.classList.contains('is-active')) {
    modal.classList.remove('is-active');
    modal.dispatchEvent(new CustomEvent('close.ShaplaModal', {detail: detail}));
    refreshBodyClass(false);
  }
}

const createModal = (appendTo: HTMLElement | null = null, id: null | string = null, type: string = 'box') => {
  const modalId = id ? id.replace('#', '') : 'shapla-modal';
  const bgEl = createEl('div', {class: 'shapla-modal-background is-dark'});
  const closeEl = createEl('span', {class: 'shapla-delete-icon is-large is-fixed', 'aria-label': 'close'});
  const modal = createEl(
    'div',
    {id: modalId, class: 'shapla-modal',},
    [
      bgEl,
      createEl('div', {class: 'shapla-modal-content is-large shapla-modal-box'}),
    ]
  );

  if (appendTo) {
    appendTo.append(modal)
  } else {
    document.body.append(modal);
  }

  bgEl.addEventListener('click', () => closeModal(modal, {via: 'background'}))
  closeEl.addEventListener('click', () => closeModal(modal, {via: 'close-button'}))
  return modal;
}

/**
 * Allow to close modal by clicking any child element with attribute 'data-close'
 */
document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  if (element.hasAttribute('data-close')) {
    event.preventDefault();
    const closestModal = element.closest('.shapla-modal.is-active') as HTMLElement;
    closeModal(closestModal, {via: 'data-close-attribute'})
  }
});

/**
 * Close last modal when press 'Escape' key
 */
document.addEventListener('keydown', (event: KeyboardEvent) => {
  if (['Escape', 'Esc'].includes(event.key)) {
    const modals = document.body.querySelectorAll('.shapla-modal.is-active');
    if (modals.length) {
      closeModal(modals[modals.length - 1], {via: 'escape-key-press'})
    }
  }
})

export {
  refreshBodyClass,
  closeModal
}
export default createModal;