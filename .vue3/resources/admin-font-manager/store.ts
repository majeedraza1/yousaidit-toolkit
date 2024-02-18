import axios from "../utils/axios";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";

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
const getExtraFonts = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('fonts/custom')
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

const createNewFont = (data: Record<string, string>) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post('fonts/custom', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
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

const deleteExtraFont = (slug: string) => {
  return new Promise(resolve => {
    Dialog.confirm('Are you sure to delete it?').then(() => {
      Spinner.show();
      axios.delete('fonts/custom', {
        params: {slug}
      })
        .then(response => {
          resolve(response.data.data);
          Notify.success('Font has been deleted.', 'Success!');
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
  })
}

export {
  getItems,
  updatePreInstalledFont,
  getExtraFonts,
  createNewFont,
  deleteExtraFont
}