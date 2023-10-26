import {createEl} from "../../utils";

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

  [bgEl, closeEl].forEach(element => {
    element.addEventListener('click', () => {
      if (modal.classList.contains('is-active')) {
        modal.classList.remove('is-active');
        modal.dispatchEvent(new CustomEvent('close'));
        refreshBodyClass(false);
      }
    })
  })
  return modal;
}

/**
 * Allow to close modal by clicking any element with attribute 'data-close'
 */
document.addEventListener('click', (event: MouseEvent) => {
  const element = event.target as HTMLElement;
  if (element.hasAttribute('data-close')) {
    event.preventDefault();
    const closestModal = element.closest('.shapla-modal');
    if (closestModal && closestModal.classList.contains('is-active')) {
      closestModal.classList.remove('is-active');
      refreshBodyClass(false);
    }
  }
});

export {refreshBodyClass}
export default createModal;