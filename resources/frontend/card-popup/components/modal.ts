import {createEl} from "../utils";

const createModal = (id: string = null, type: string = 'box') => {
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
    }
  }
});

export default createModal;