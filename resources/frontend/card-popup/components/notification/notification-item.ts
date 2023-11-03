import {createEl} from "../../utils";
import {NotificationItemPropsInterface} from "./interfaces";

const notificationItemDefaults: NotificationItemPropsInterface = {
  type: 'info',
  title: '',
  message: '',
  showDismisses: true,
  timeout: 3000,
}

const notificationItem = (props: NotificationItemPropsInterface = {}): HTMLElement => {
  let timer = 0;
  const args = Object.assign(notificationItemDefaults, props);
  const removeEl = createEl('span', {class: 'shapla-delete-icon'})
  const titleEl = createEl('div', {class: 'shapla-notification__title'}, [args.title as string])
  const messageEl = createEl('div', {class: 'shapla-notification__message'}, [args.message as string])
  const item = createEl('div', {class: `shapla-notification has-${args.type}`}, [
    (args.showDismisses ? removeEl : '') as HTMLElement,
    (args.title?.length ? titleEl : '') as HTMLElement,
    (args.message?.length ? messageEl : '') as HTMLElement
  ])

  removeEl.addEventListener('click', () => {
    item.remove();
    if (timer) {
      clearTimeout(timer);
    }
  })

  if (args.timeout !== 0) {
    timer = window.setTimeout(() => {
      item.remove();
    }, args.timeout);
  }

  return item;
}

export default notificationItem;