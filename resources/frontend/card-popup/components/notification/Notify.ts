import {NOTIFICATION_TYPE, NotificationItemPropsInterface} from "./interfaces";

class Notify {
  /**
   * Listen event
   *
   * @param callback
   */
  static on(callback: EventListener | ((options: NotificationItemPropsInterface) => void)) {
    document.addEventListener("show.ShaplaNotification", ((e: CustomEvent) =>
      callback(e.detail)) as EventListener);
  }

  /**
   * Dispatch event
   *
   * @param data
   */
  static dispatch(data: NotificationItemPropsInterface) {
    document.dispatchEvent(
      new CustomEvent<NotificationItemPropsInterface>(
        "show.ShaplaNotification",
        {detail: data}
      )
    );
  }

  /**
   * Create UUID
   *
   * @returns {string}
   */
  static createUUID(): string {
    const pattern = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx";
    return pattern.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      const v = c === "x" ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  /**
   * Get parameters
   *
   * @param {string|object} message
   * @param args
   * @return {NotificationItemPropsInterface}
   */
  static getParams(message: string | NotificationItemPropsInterface, ...args: (string | number)[]): NotificationItemPropsInterface {
    let params: NotificationItemPropsInterface = {
      id: Notify.createUUID(),
      type: "primary",
      message: "Please add some message.",
      title: "",
      timeout: 3000,
    };

    if (typeof message === "object") {
      params = Object.assign(params, message);
      return params;
    }

    params.message = message;

    if (!args.length) {
      return params;
    }

    if (args.length > 1) {
      params.title = typeof args[0] === "string" ? args[0] : "";
      params.timeout = typeof args[1] === "number" ? args[1] : 3000;
    } else {
      if (typeof args[0] === "number") {
        params.timeout = args[0];
      } else {
        params.title = args[0];
      }
    }

    return params;
  }

  /**
   * Create notification
   *
   * @param type
   * @param {object} params
   */
  private static create(type: NOTIFICATION_TYPE, params: NotificationItemPropsInterface) {
    params.type = type;
    Notify.dispatch(params);
  }

  /**
   * Create primary notification
   *
   * @param message
   * @param params
   */
  static default(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('primary', Notify.getParams(message, ...params));
  }

  /**
   * Create primary notification
   *
   * @param message
   * @param params
   */
  static primary(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('primary', Notify.getParams(message, ...params));
  }

  /**
   * Create success notification
   *
   * @param message
   * @param params
   */
  static success(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('success', Notify.getParams(message, ...params));
  }

  /**
   * Create info notification
   *
   * @param message
   * @param params
   */
  static info(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('info', Notify.getParams(message, ...params));
  }

  /**
   * Create warning notification
   *
   * @param message
   * @param params
   */
  static warning(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('warning', Notify.getParams(message, ...params));
  }

  /**
   * Create warning notification
   *
   * @param message
   * @param params
   */
  static error(message: string | NotificationItemPropsInterface, ...params: (string | number)[]) {
    Notify.create('error', Notify.getParams(message, ...params));
  }
}

export default Notify;
