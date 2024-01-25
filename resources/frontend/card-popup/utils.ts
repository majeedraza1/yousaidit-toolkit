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
  getRequest,
  postRequest
}