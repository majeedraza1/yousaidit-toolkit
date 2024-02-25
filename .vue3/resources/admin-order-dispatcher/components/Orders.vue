<template>
  <div>
    <ShaplaColumns :multiline="true">
      <ShaplaColumn :tablet="12">
        <div class="flex">
          <div class="flex-1"></div>
          <div class="space-x-2">
            <slot name="actions"></slot>
            <ShaplaButton @click="()=> store.refreshFromShipStation()" :shadow="true"
                          theme="default" size="small"> Refresh Orders
            </ShaplaButton>
            <ShaplaButton @click="()=>store.mergePackingSlip()" :shadow="true" theme="primary"
                          :disabled="!(store.checked_items.length > 1)" size="small">Packing Slip
            </ShaplaButton>
          </div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="2">
        <ShaplaSelect
            label="Card size"
            v-model="store.card_size"
            :options="card_sizes"
            :clearable="false"
            @change="filterOrderData"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="4">
        <ShaplaSelect
            label="Inner Message"
            v-model="store.inner_message"
            :options="filterOptions"
            :clearable="false"
            @change="filterOrderData"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="6">
        <ShaplaTablePagination :total-items="store.order_pagination.totalCount"
                               :per-page="store.order_pagination.limit"
                               :current-page="store.order_pagination.currentPage" @paginate="store.paginate"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTable
            :columns="[
                {key: 'art_work', label: 'Art Work'},
                {key: 'customer', label: 'Customer Details'},
                {key: 'orderId', label: 'ShipStation Order ID'},
                {key: 'door_delivery', label: 'Door Delivery'},
                {key: 'invoice', label: 'Invoice'},
              ]"
            :items="store.orders"
            :actions="[
                {key: 'dispatch', label: 'Dispatch'},
                {key: 'packing_slip', label: 'Packing Slip'},
                {key: 'upload_pdf', label: 'Upload PDF'},
              ]"
            index="orderId"
            action-column="art_work"
            :selectedItems="store.checked_items"
            @select:item="selected => store.checked_items = selected"
            @click:action="onActionClick"
        >
          <template v-slot:orderId="data">
            <a target="_blank"
               :href="`/wp-admin/admin-ajax.php?action=yousaidit_ship_station_order&order_id=${data.row.orderId}`"
            ><strong>{{ data.row.orderId }}</strong></a>
            <br>
            <span>{{ data.row.storeName }}</span>
          </template>
          <template v-slot:invoice="data">
            <div class="flex flex-col space-y-1">
              <ShaplaButton size="small" :shadow="true" :href="invoiceUrl(data.row.orderId)"
                            @click.prevent="store.showInvoice(data.row.orderId)">Packing Slip
              </ShaplaButton>
              <ShaplaButton size="small" :shadow="true" @click.prevent="dispatchOrder(data.row)">Dispatch
              </ShaplaButton>
            </div>
          </template>
          <template v-slot:customer="data">
            <strong>{{ data.row.customer_full_name }}</strong><br>
            <span v-if="data.row.customer_email">
            <strong>Email:</strong> {{ data.row.customer_email }}<br>
          </span>
            <span v-if="data.row.customer_phone">
            <strong>Phone:</strong>
            {{ data.row.customer_phone }}
            <br>
          </span>
            <span v-html="formatAddress(data.row.shipping_address)"> </span>
          </template>
          <template v-slot:art_work="data">
            <art-work-items :item="data.row"/>
          </template>
        </ShaplaTable>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTablePagination :total-items="store.order_pagination.totalCount"
                               :per-page="store.order_pagination.limit"
                               :current-page="store.order_pagination.currentPage" @paginate="store.paginate"/>
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaModal v-if="state.activeOrder" :active="state.showPdfUploadModal" title="Upload PDF" @close="state.showPdfUploadModal = false">
      <template v-for="_product in state.activeOrder.products">
        <div v-if="_product.product_sku" :key="_product.shipstation_item_id"
             class="flex items-center w-full border border-solid border-gray-200 p-2 rounded mb-2">
          <div class="w-2/4">{{ _product.title }}</div>
          <div class="w-1/4 flex items-center space-x-1">
            <svg v-if="_product.pdf_id" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
              <path fill="none" d="M0 0h24v24H0z"/>
              <path
                  d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/>
            </svg>
            <span>{{ _product.product_sku }}</span>
          </div>
          <div class="w-1/4">
            <ShaplaButton v-if="!_product.pdf_id" theme="primary" size="small" outline @click="addPdf(_product)">Add
              PDF
            </ShaplaButton>
          </div>
        </div>
      </template>
    </ShaplaModal>
  </div>
</template>
<script lang="ts" setup>
import useAdminArtWorkOrderStore from "../store.ts";
import ArtWorkItems from "../components/ArtWorkItems.vue";
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaModal,
  ShaplaSelect,
  ShaplaTable,
  ShaplaTablePagination
} from '@shapla/vue-components'
import {onMounted, reactive} from "vue";
import wpMediaUploader from "../../utils/WpMediaUploader.ts";
import axios from "../../utils/axios.ts";

const store = useAdminArtWorkOrderStore();
const emit = defineEmits<{
  dispatch: [value: number];
}>()

const state = reactive({
  inner_message: 'any',
  card_size: 'any',
  activeOrder: null,
  showPdfUploadModal: false,
})

const card_sizes = [
  {value: 'any', label: 'Any'},
  {value: 'square', label: 'Square'},
  {value: 'a4', label: 'A4'},
  {value: 'a5', label: 'A5'},
  {value: 'a6', label: 'A6'},
];

const filterOptions = [
  {value: 'any', label: 'Any'},
  {value: 'no', label: 'Card without inner message'},
  {value: 'yes', label: 'Card with inner message'},
]

const invoiceUrl = (orderId: number) => window.StackonetToolkit.ajaxUrl + '?action=stackonet_order_packing_slip&id=' + orderId;
const formatAddress = (address) => address.replace(/<br>\u21b5/g, ', ');

function filterOrderData(value: string) {
  if (card_sizes.map(_size => _size.value).includes(value)) {
    store.card_size = value;
  } else {
    store.inner_message = value
  }
  store.checked_items = [];
  store.getOrders();
}

const dispatchOrder = (item) => emit('dispatch', item.orderId);

const onActionClick = (action: string, item) => {
  if ('packing_slip' === action) {
    store.showInvoice(item.orderId);
  }
  if ('dispatch' === action) {
    dispatchOrder(item)
  }
  if ('upload_pdf' === action) {
    state.activeOrder = item;
    state.showPdfUploadModal = true;
  }
}

const addPdf = (product) => {
  state.activeOrderItem = {
    product_id: product.id,
    product_sku: product.product_sku,
    order_id: state.activeOrder.orderId,
    order_item_id: product.shipstation_item_id,
    store_id: state.activeOrder.storeId,
    card_size: product.card_size,
    pdf_id: 0,
    pdf_width: 0,
    pdf_height: 0,
  }
  wpMediaUploader().then((id: number) => {
    state.activeOrderItem.pdf_id = id;
    axios.post('order-item-pdf', state.activeOrderItem).then(response => {
      window.console.log(response);
    })
  })
}

onMounted(() => {
  store.getOrders();
})
</script>
