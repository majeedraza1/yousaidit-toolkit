import axios from "../utils/axios.ts";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {ServerErrorResponseInterface} from "../utils/CrudOperation.ts";

const validateEmail = (email: string) => {
  return String(email)
    .toLowerCase()
    .match(
      /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    );
}
const validateEmailFromServer = (email: string) => {
  return new Promise((resolve, reject) => {
    axios
      .post('designer-signup/validate', {email: email})
      .then(() => {
        resolve(true)
      })
      .catch((error) => {
        const responseData = error.response.data as ServerErrorResponseInterface;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
        reject(responseData.errors.email)
      })
  })
}
const validatePayPalEmailFromServer = (email: string) => {
  return new Promise((resolve, reject) => {
    axios
      .post('designer-signup/validate', {paypal_email: email})
      .then(() => {
        resolve(true)
      })
      .catch((error) => {
        const responseData = error.response.data as ServerErrorResponseInterface;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
        reject(responseData.errors.paypal_email)
      })
  })
}
const validateUsernameFromServer = (username: string) => {
  return new Promise((resolve, reject) => {
    axios
      .post('designer-signup/validate', {username: username})
      .then(() => {
        resolve(true)
      })
      .catch((error) => {
        const responseData = error.response.data as ServerErrorResponseInterface;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
        reject(responseData.errors.username)
      })
  })
}

const submitSignupRequest = (data: FormData) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post('designer-signup', data)
      .then(response => {
        resolve(response.data.data);
      })
      .catch((error) => {
        const responseData = error.response.data as ServerErrorResponseInterface;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

export {
  validateEmail,
  validateEmailFromServer,
  validatePayPalEmailFromServer,
  validateUsernameFromServer,
  submitSignupRequest,
}