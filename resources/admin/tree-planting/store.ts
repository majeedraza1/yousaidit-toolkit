import axios from "../../utils/axios";

const getPurchases = (page: number = 1) => {
  return new Promise(resolve => {
    axios.get('tree-planting', {params: {page: page}})
      .then(response => {
        resolve(response.data.data);
      })
  })
}

const getPendingOrders = (page: number = 1) => {
  return new Promise(resolve => {
    axios.get('tree-planting/pending-orders', {params: {page: page}})
      .then(response => {
        resolve(response.data.data);
      })
  })
}

const syncPurchase = (id: number) => {
  return new Promise(resolve => {
    axios.post(`tree-planting/${id}/sync`)
      .then(response => {
        resolve(response.data.data);
      })
  })
}

const syncPurchases = () => {
  return new Promise(resolve => {
    axios.post('tree-planting/sync', {force: 1})
      .then(response => {
        resolve(response.data.data);
      })
  })
}

export {
  getPurchases,
  getPendingOrders,
  syncPurchase,
  syncPurchases
}