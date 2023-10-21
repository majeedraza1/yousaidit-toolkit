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

const getAjaxUrl = (action: string, args: Record<string, string> = {}) => {
  const url = new URL(window.StackonetToolkit.ajaxUrl);
  url.searchParams.append('action', action);
  for (const [key, value] of Object.entries(args)) {
    url.searchParams.append(key, value);
  }

  return url.toString();
}

const getRequest = (url: string) => {
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

const postRequest = (url: string, formData: FormData | Record<string, any>) => {
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
    xhr.open("POST", url);
    xhr.send(formData);
  })
}

export {
  createEl,
  getAjaxUrl,
  getRequest,
  postRequest
}