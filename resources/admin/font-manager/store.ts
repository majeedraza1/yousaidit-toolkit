import axios from "../../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";

const getItems = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('fonts')
      .then(response => {
        resolve(response.data.data);
      })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const updatePreInstalledFont = (data) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post('fonts', data)
      .then(response => {
        resolve(response.data.data);
        Notify.success('Font setting has been updated.', 'Success!');
      })
      .catch(error => {
        const responseData = error.response.data;
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
  getItems,
  updatePreInstalledFont
}