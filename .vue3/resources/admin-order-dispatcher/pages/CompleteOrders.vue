<template>
  <div>
    <h1 class="wp-heading-inline">Complete Orders</h1>
    <hr class="wp-header-end">
    <Orders>
      <ShaplaButton @click="()=>store.getOrders(true)" :shadow="true" theme="primary">Refresh Orders</ShaplaButton>
      <ShaplaButton :shadow="true" theme="secondary" @click="state.showModal = true">
        Scan Order
      </ShaplaButton>
    </Orders>
    <ShaplaModal :active="true" v-if="state.showModal" @close="onCloseModal" type="box" content-size="large">
      <div class="shapla-modal-box-content">
        <BarcodeSearchForm v-model="state.search" @submit="scanBarcode"/>
        <p class="search-results-error" v-if="state.errorText" v-html="state.errorText"></p>
        <template v-if="hasActiveOrder">
          <OrderInfo :order="state.activeOrder" @shipped="state.showModal = false"/>
        </template>
      </div>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import Orders from "../components/Orders.vue";
import {ShaplaButton, ShaplaModal} from '@shapla/vue-components';
import OrderInfo from "../components/OrderInfo.vue";
import BarcodeSearchForm from "../components/BarcodeSearchForm.vue";
import useAdminOrderDispatcherStore from "../store.ts";
import {computed, onMounted, reactive} from "vue";

const store = useAdminOrderDispatcherStore();

const state = reactive({
  showModal: false,
  activeOrder: {},
  activeOrderFromServer: {},
  search: '',
  errorText: '',
})

const onCloseModal = () => {
  state.showModal = false;
  state.search = '';
  state.errorText = '';
  state.activeOrder = {};
}
const hasActiveOrder = computed(() => !!Object.keys(state.activeOrder).length);

onMounted(() => {
  store.current_page = 1;
  store.orderStatus = 'shipped';
  store.getOrders(true);
})


const scanBarcode = (search: string) => {
  state.errorText = '';
  state.activeOrder = {};
  let orderId = parseInt(search);
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
  })
}
</script>
