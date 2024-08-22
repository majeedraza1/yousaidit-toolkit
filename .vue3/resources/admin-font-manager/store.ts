import axios from "../utils/axios";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import {ServerCollectionResponseDataInterface} from "../utils/CrudOperation.ts";
import {DesignerFontInfoInterface} from "../interfaces/custom-font.ts";

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
const getDesignerFonts = (page: number = 1, per_page: number = 20): Promise<ServerCollectionResponseDataInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('fonts/designers', {params: {page, per_page}})
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

const updateDesignerFont = (data: DesignerFontInfoInterface) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post(`fonts/designers/${data.id}`, data)
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

const deleteDesignerFont = (id: number) => {
  return new Promise(resolve => {
    Dialog.confirm('Are you sure to delete designer font?').then(() => {
      Spinner.show();
      axios.delete(`fonts/designers/${id}`)
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
  deleteExtraFont,
  getDesignerFonts,
  updateDesignerFont,
  deleteDesignerFont
}