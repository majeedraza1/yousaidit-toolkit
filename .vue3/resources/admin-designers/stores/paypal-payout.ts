import {defineStore} from "pinia";
import {reactive, toRefs} from "vue";
import {Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../../utils/axios.ts";
import {DesignerPaymentInterface, PaymentStatusInterface} from "../../interfaces/designer-payment.ts";
import {PaginationDataInterface} from "../../utils/CrudOperation.ts";

const useAdminDesignerPayPalPayoutStore = defineStore('admin-designer-card', () => {
  const state = reactive<{
    items: DesignerPaymentInterface[],
    pagination: PaginationDataInterface,
    statuses: PaymentStatusInterface[],
    payment: null | DesignerPaymentInterface,
    min_amount: number;
    statuses_to_pay: string[];
    payment_items: any[];
    [key: string]: any;
  }>({
    items: [],
    pagination: {per_page: 20, current_page: 1, total_items: 0},
    statuses: [],
    min_amount: 1,
    payment: null,
    payment_items: [],
    statuses_to_pay: [],
  })

  const getItems = () => {
    Spinner.show();
    let params = {};
    axios.get('paypal-payouts', {
      params: params
    })
      .then(response => {
        state.items = response.data.data.items;
        state.pagination = response.data.data.pagination;
        state.statuses = response.data.data.statuses;
        state.min_amount = response.data.data.min_amount;
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
  }

  const getItem = (payment_id: string) => {
    Spinner.show();
    axios.get('paypal-payouts/' + payment_id)
      .then(response => {
        let data = response.data.data;
        state.payment = data.payment;
        state.payment_items = data.items;
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
  }

  const syncFromPayPal = (payment_id: string) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('paypal-payouts/' + payment_id + '/sync')
        .then((response) => {
          resolve(response.data.data)
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

  const payByWcOrderStatuses = () => {
    Spinner.show();
    axios.post('paypal-payouts', {order_status: state.statuses_to_pay})
      .then(() => {
        Notify.success('Payout has been run successfully.')
        getItems();
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
  }

  return {
    ...toRefs(state),
    getItems,
    getItem,
    syncFromPayPal,
    payByWcOrderStatuses,
  }
})

export default useAdminDesignerPayPalPayoutStore;
