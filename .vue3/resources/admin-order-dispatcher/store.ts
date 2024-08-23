import axios from "../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {defineStore} from "pinia";
import {reactive, toRefs} from "vue";
import printPage from "./print.ts";

const useAdminOrderDispatcherStore = defineStore('order-dispatcher-admin', () => {
  const state = reactive({
    orders: [],
    checked_items: [],
    order_pagination: {currentPage: 1, totalCount: 0, limit: 100},
    current_page: 1,
    card_size: 'square',
    inner_message: false,
    carriers: [],
    orderStatus: 'awaiting_shipment',
  })


  function getOrders(force = false) {
    Spinner.show();
    axios.get('orders', {
      params: {
        page: state.current_page,
        card_size: state.card_size,
        inner_message: state.inner_message,
        orderStatus: state.orderStatus,
        force: force
      }
    }).then(response => {
      let data = response.data.data;
      state.orders = data.items;
      state.order_pagination = data.pagination;
      Spinner.hide();
    }).catch(error => {
      console.log(error);
      Spinner.hide();
    })
  }

  function getCarriers() {
    Spinner.show();
    axios.get('carriers').then(response => {
      let data = response.data.data;
      state.carriers = data.items;
      Spinner.hide();
    }).catch(error => {
      console.log(error);
      Spinner.hide();
    })
  }

  function getOrder(orderId: number) {
    Spinner.show();
    return new Promise(resolve => {
      axios.get('orders/' + orderId).then(response => {
        let data = response.data.data;
        resolve(data);
      }).catch(error => {
        console.log(error);
      }).finally(() => {
        Spinner.hide();
      })
    })
  }

  function refreshFromShipStation() {
    getOrders(true);
  }

  function paginate(page = 1) {
    state.order_pagination.currentPage = page;
    getOrders();
  }

  function mergePackingSlip() {
    let url = window.StackonetToolkit.ajaxUrl + '?action=stackonet_order_packing_slips&ids=' + state.checked_items.toString();
    window.open(url, '_blank');
  }

  function showInvoice(orderId: number) {
    let url = window.StackonetToolkit.ajaxUrl + '?action=stackonet_order_packing_slip&id=' + orderId;
    window.open(url, '_blank');
  }

  function printAddress(orderId: number) {
    let url = window.StackonetToolkit.ajaxUrl + '?action=print_order_address&id=' + orderId;
    printPage.printPage(url);
  }

  function dispatch(data) {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('dispatch', data).then(response => {
        let data = response.data.data;
        resolve(data);
        Spinner.hide();
        Notify.success('Order marked as shipped.')
      }).catch(error => {
        if (error.response.data.message) {
          Notify.error(error.response.data.message, 'Error!')
        }
        Spinner.hide();
      })
    })
  }

  const getOrdersCardSizes = (force = false) => {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .get('orders/card-sizes', {params: {force: force}})
        .then(response => {
          let data = response.data.data;
          resolve(data);
        })
        .catch(error => {
          if (error.response.data.message) {
            Notify.error(error.response.data.message, 'Error!')
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  return {
    ...toRefs(state),
    getOrders,
    getCarriers,
    getOrder,
    refreshFromShipStation,
    paginate,
    mergePackingSlip,
    showInvoice,
    printAddress,
    dispatch,
    getOrdersCardSizes,
  }
});

export default useAdminOrderDispatcherStore;
