<template>
  <div>
    <div class="order-information">
      <div class="section section--header">
        <div class="flex">
          <div>
            <div>&nbsp;</div>
            <div><strong>Order Date:</strong> {{ formatDate(order.order_date) }}</div>
          </div>
          <div class="spacer"></div>
          <div>
            <div><strong>Order Status:</strong> {{ getStatusText(order.orderStatus) }}</div>
            <div><strong>Order Number:</strong> {{ order.orderId }}</div>
            <div><strong>Store:</strong> {{ order.storeName }}({{ order.storeId }})</div>
            <div v-if="order.print_shipping_label_url">
              <strong>{{ order.storeName }} Shipping Label: </strong>
              <a :href="order.print_shipping_label_url" target="_blank">Print</a>
            </div>
          </div>
        </div>
      </div>
      <div class="section section--products">
        <ShaplaTable :columns="[
        {key: 'title', label: 'Product'},
        {key: 'quantity', label: 'Qty', numeric: true},
      ]" :items="order.products" :mobile-width="300" :show-cb="false">
          <template v-slot:title="data">
            <div class="order-information__product flex">
              <ShaplaImage container-width="96px" container-height="96px">
                <img :src="data.row.product_thumbnail" alt=""/>
              </ShaplaImage>
              <div>
                <div class="product-title">{{ data.row.title }}</div>
                <div class="product-product_sku">
                  <span>SKU: </span>{{ data.row.product_sku }}
                </div>
                <div class="product-product_options" v-if="data.row.options">
                  <div class="product-product_option" :key="index"
                       v-for="(_option,index) in data.row.options">
                    <span>{{ _option.name }}: </span>{{ _option.value }}
                  </div>
                </div>
              </div>
            </div>
          </template>
        </ShaplaTable>
      </div>
      <div class="section section--customer_notes" v-if="order.customer_notes">
        <div class="customer_notes flex">
          <span>Customer Note: </span>
          <span>{{ order.customer_notes }}</span>
        </div>
      </div>
      <div class="section section--customer_notes" v-if="order.internal_notes">
        <div class="customer_notes flex">
          <span>Internal Note: </span>
          <span>{{ order.internal_notes }}</span>
        </div>
      </div>
      <div class="section section--address">
        <div class="shipping-address flex">
          <span>Address: </span>
          <span>{{ address }}</span>
        </div>
      </div>
      <div class="section section--footer">
        <div class="actions flex">
          <ShaplaButton theme="secondary" @click="()=> store.printAddress(order.orderId)">
            Print Address
          </ShaplaButton>
          <div class="spacer"></div>
          <div class="door_delivery">
            <div><strong>Door Delivery:</strong></div>
            {{ order.door_delivery }}
          </div>
          <div class="spacer"></div>
          <div class="shipping_service">
            <div>Shipping Method:</div>
            <div>{{ order.shipping_service }}</div>
          </div>
          <div class="spacer"></div>
          <ShaplaButton theme="primary" @click="showDispatchModal = true">Dispatch</ShaplaButton>
        </div>
      </div>
    </div>
    <ShaplaModal :active="showDispatchModal" @close="showDispatchModal = false" type="box" content-size="small">
      <MarkAsShipped :order-id="order.orderId" @shipped="shipped"/>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaImage, ShaplaModal, ShaplaTable} from '@shapla/vue-components';
import MarkAsShipped from "./MarkAsShipped.vue";
import {computed, PropType, ref} from "vue";
import {ArtWorkOrderInterface} from "../../interfaces/art-work.ts";
import useAdminOrderDispatcherStore from "../store.ts";

const store = useAdminOrderDispatcherStore();
const emit = defineEmits<{
  shipped: [order: ArtWorkOrderInterface]
}>()
const props = defineProps({
  order: {type: Object as PropType<ArtWorkOrderInterface>}
})
const showDispatchModal = ref<boolean>(false)


const address = computed(() => {
  if (!Object.keys(props.order).length) {
    return '';
  }

  return props.order.shipping_address.replace(/<br\/>/g, ", ");
})


const formatDate = (dateString: string) => {
  let date = new Date(dateString), month = date.getMonth();
  let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

  return `${date.getDate()} ${months[month]}, ${date.getFullYear()}`;
}
const getStatusText = (key: string) => {
  let statuses = {
    awaiting_payment: 'Awaiting Payment',
    awaiting_shipment: 'Awaiting Shipment',
    shipped: 'Shipped',
    on_hold: 'On Hold',
    cancelled: 'Cancelled',
  }

  return statuses[key];
}
const shipped = (data) => {
  store.dispatch(data).then(() => {
    emit('shipped', props.order);
  });
  showDispatchModal.value = false;
}
</script>
