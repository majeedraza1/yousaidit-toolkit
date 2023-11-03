import axios from "../../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";

const getPurchases = (page: number = 1) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('tree-planting', {params: {page: page}})
      .then(response => {
        resolve(response.data.data);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const getPendingOrders = (page: number = 1) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('tree-planting/pending-orders', {params: {page: page}})
      .then(response => {
        resolve(response.data.data);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const syncPurchase = (id: number) => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post(`tree-planting/${id}/sync`)
      .then(response => {
        resolve(response.data.data);
      })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.errors) {
          Notify.error(responseData.errors.error, responseData.errors.responseCode);
        } else if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const syncPurchases = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios.post('tree-planting/sync', {force: 1})
      .then(response => {
        resolve(response.data.data);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

export {
  getPurchases,
  getPendingOrders,
  syncPurchase,
  syncPurchases
}