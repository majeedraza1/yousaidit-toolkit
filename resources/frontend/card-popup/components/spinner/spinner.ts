import {createEl} from "../../utils";

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

const createSpinner = (id: string = '') => {
  const spinner = createEl('div',
    {
      id: id ? id.replace('#', '') : 'shapla-spinner-container',
      class: 'shapla-spinner-container is-fixed'
    },
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

const showSpinner = (id: string = '') => {
  createSpinner(id);
}

const hideSpinner = (id: string = '') => {
  const spinner = document.querySelector(id ? `#${id}` : '#shapla-spinner-container');
  if (spinner) {
    spinner.remove();
  }
}

class Spinner {
  static show(id: string = '') {
    showSpinner(id)
  }

  static hide(id: string = '') {
    hideSpinner(id);
  }
}

export {showSpinner, hideSpinner}
export default Spinner;