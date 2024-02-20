import axios from "../utils/axios";
import {defineStore} from 'pinia'
import {Notify, Spinner} from "@shapla/vanilla-components";
import {reactive, toRefs} from "vue";
import {ArtWorkOrderInterface} from "../interfaces/art-work.ts";

const useAdminArtWorkOrderStore = defineStore('admin-art-work-order', () => {
  const state = reactive<{
    orders: ArtWorkOrderInterface[],
    checked_items: number[],
    order_pagination: { currentPage: number, totalCount: number, limit: number },
    current_page: number,
    card_size: 'square',
    inner_message: boolean | string,
    carriers: [],
    orderStatus: 'awaiting_shipment',
  }>({
    orders: [],
    checked_items: [],
    order_pagination: {currentPage: 1, totalCount: 0, limit: 100},
    current_page: 1,
    card_size: 'square',
    inner_message: false,
    carriers: [],
    orderStatus: 'awaiting_shipment',
  });

  const getOrders = (force = false) => {
    Spinner.show();
    axios
      .get('orders', {
        params: {
          page: state.current_page,
          card_size: state.card_size,
          inner_message: state.inner_message,
          orderStatus: state.orderStatus,
          force: force
        }
      })
      .then(response => {
        let data = response.data.data;
        state.orders = data.items;
        state.order_pagination = data.pagination;
      })
      .catch((error) => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }

  const getCarriers = () => {
    Spinner.show();
    axios
      .get('carriers')
      .then(response => {
        state.carriers = response.data.data.items;
      })
      .catch((error) => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }

  function getOrder(orderId: number) {
    Spinner.show();
    return new Promise(resolve => {
      axios.get('orders/' + orderId)
        .then(response => {
          let data = response.data.data;
          resolve(data);
        })
        .catch((error) => {
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

  function refreshFromShipStation() {
    getOrders(true);
  }

  function paginate(page: number = 1) {
    state.current_page = page;
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
    window.open(url, '_blank');
    // printPage.printPage(url);
  }

  function dispatch(data) {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('dispatch', data)
        .then(response => {
          resolve(response.data.data);
          Notify.success('Order marked as shipped.')
        })
        .catch(error => {
          if (error.response.data.message) {
            Notify.error(error.response.data.message, 'Error!')
          }
          Spinner.hide();
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }


  return {
    ...toRefs(state),
    getOrders,
    refreshFromShipStation,
    mergePackingSlip,
    getCarriers,
    getOrder,
    paginate,
    showInvoice,
    dispatch,
    printAddress
  }
})

export default useAdminArtWorkOrderStore;
