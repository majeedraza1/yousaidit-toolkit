<template>
  <div>
    <h1 class="wp-heading-inline">Dispatch Orders</h1>
    <hr class="wp-header-end">
    <Orders @dispatch="onDispatch">
      <template v-slot:actions>
        <ShaplaButton :shadow="true" size="small" theme="secondary" @click="state.showModal = true">Scan Order</ShaplaButton>
      </template>
    </Orders>
    <ShaplaModal :active="true" v-if="state.showModal" @close="state.showModal = false" type="box" content-size="large">
      <div class="shapla-modal-box-content">
        <BarcodeSearchForm
            v-model="state.search"
            @submit="scanBarcode"
        />
        <p class="search-results-error" v-if="state.errorText" v-html="state.errorText"></p>
        <template v-if="hasActiveOrder">
          <OrderInfo :order="state.activeOrder" @shipped="state.showModal = false"/>
        </template>
      </div>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaModal} from '@shapla/vue-components';
import Orders from "../components/Orders.vue";
import OrderInfo from "../components/OrderInfo.vue";
import {computed, onMounted, reactive, watch} from "vue";
import useAdminOrderDispatcherStore from "../store.ts";
import BarcodeSearchForm from "../components/BarcodeSearchForm.vue";

const store = useAdminOrderDispatcherStore();

const state = reactive({
  showModal: false,
  activeOrder: null,
  activeOrderFromServer: null,
  search: '',
  errorText: '',
});

const hasActiveOrder = computed(() => !!(state.activeOrder && Object.keys(state.activeOrder).length))

watch(() => state.showModal, (newValue: boolean) => {
  state.search = '';
  state.errorText = '';
  state.activeOrder = {};
  if (newValue) {
    const inputEl = document.querySelector<HTMLInputElement>('.input--search');
    if (inputEl) {
      setTimeout(() => inputEl.focus(), 10);
    }
  }
})


const scanBarcode = () => {
  state.errorText = '';
  state.activeOrder = {};
  let orderId = parseInt(state.search);
  if (Number.isNaN(orderId)) {
    state.errorText = 'Invalid number';
    return;
  }
  let order = store.orders.find(order => order.orderId === orderId);
  if (!(typeof order === 'object' && Object.keys(order))) {
    state.errorText = 'No order found.';
    getOrder(orderId);
    return;
  }
  state.activeOrder = order;
}
const getOrder = (orderId: number) => {
  store.getOrder(orderId).then(data => {
    state.activeOrderFromServer = data;
    state.activeOrder = data;
    state.errorText = '';
  });
}
const onDispatch = (orderId: number) => {
  state.showModal = true;
  state.search = orderId.toString();
  getOrder(orderId);
}

onMounted(() => {
  store.current_page = 1;
  store.orderStatus = 'awaiting_shipment';
  store.getOrders();
})
</script>
