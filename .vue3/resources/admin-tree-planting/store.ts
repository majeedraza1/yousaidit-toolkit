import axios from "../utils/axios";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import {ServerCollectionResponseDataInterface} from "../utils/CrudOperation.ts";

const getPurchases = (page: number = 1, status: string = 'complete'):Promise<ServerCollectionResponseDataInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('tree-planting', {params: {page: page, per_page: 20, status: status}})
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
    axios.get('tree-planting/pending-orders', {params: {page: page, per_page: 50}})
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

const deleteItems = (ids: number[]) => {
  return new Promise(resolve => {
    Dialog.confirm({
      title: 'Are you sure?',
      message: 'Items will be deleted permanently and cannot be recovered.'
    }).then(() => {
      Spinner.show();
      axios.post('tree-planting/batch', {action: 'delete', payload: ids})
        .then(response => {
          resolve(response.data.data);
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  })
}

export {
  getPurchases,
  getPendingOrders,
  syncPurchase,
  syncPurchases,
  deleteItems
}