import axios from '../utils/axios'
import {Spinner} from "@shapla/vanilla-components";

interface ServerPaginationResponseInterface {
  current_page: number;
  per_page: number;
  total_items: number;
  total_pages: number;
}

interface ServerResponseProductInterface {
  items: Record<string, any>[];
  total_items: number;
}

interface ServerResponseOrderInterface {
  items: Record<string, any>[];
  total_items: number;
  pagination: Record<string, number>;
}

const getProducts = (page: number = 1): Promise<ServerResponseProductInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('products?page=' + page).then(response => {
      resolve(response.data.data);
    }).catch(error => {
      console.log(error);
    }).finally(() => {
      Spinner.hide();
    })
  })
}

const searchProduct = (query: string): Promise<ServerResponseProductInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('products', {params: {search: query}})
      .then(response => {
        resolve(response.data.data)
      })
      .catch(error => {
        console.log(error);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const refreshFromShipStation = (page: number = 1): Promise<ServerResponseOrderInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios
      .get('orders?page=' + page + '&force=1')
      .then(response => {
        resolve(response.data.data)
      })
      .catch(error => {
        console.log(error);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

const getOrders = (params): Promise<ServerResponseOrderInterface> => {
  return new Promise(resolve => {
    Spinner.show();
    axios.get('orders', {params: params})
      .then(response => {
        resolve(response.data.data)
      })
      .catch(error => {
        console.log(error);
      })
      .finally(() => {
        Spinner.hide();
      })
  })
}

export {
  getProducts,
  searchProduct,
  getOrders,
  refreshFromShipStation
}