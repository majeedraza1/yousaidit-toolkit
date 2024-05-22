<template>
  <div>
    <h1 class="wp-heading-inline">Print Cards</h1>
    <hr class="wp-header-end mb-4">
    <div>
      <div class="mb-4 flex">
        <div class="flex-1"></div>
        <ShaplaButton theme="primary" size="small" @click="getItems(true)">Refresh</ShaplaButton>
      </div>
      <ShaplaTabs>
        <ShaplaTab name="PDF Merger" selected>
          <PdfSizeInfo
              :items="state.store_items"
              @need-force-refresh="getItems(true)"
          />
        </ShaplaTab>
        <ShaplaTab name="Trade Orders">
          <PdfSizeInfo
              :items="state.marketplace_items"
              @need-force-refresh="getItems(true)"
          />
        </ShaplaTab>
        <ShaplaTab name="Custom Cards">
          <PdfSizeInfo
              :items="state.custom_items"
              @need-force-refresh="getItems(true)"
          />
        </ShaplaTab>
        <ShaplaTab name="Other Products">
          <PdfSizeInfo
              :items="state.others_items"
              @need-force-refresh="getItems(true)"
          />
        </ShaplaTab>
      </ShaplaTabs>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaTab, ShaplaTabs} from '@shapla/vue-components';
import PdfSizeInfo from "../components/PdfSizeInfo.vue";
import {onMounted, reactive} from "vue";
import useAdminOrderDispatcherStore from "../store.ts";

const state = reactive({
  store_items: [],
  marketplace_items: [],
  others_items: [],
  custom_items: [],
})

const store = useAdminOrderDispatcherStore();


const getItems = (force = false) => {
  store.getOrdersCardSizes(force).then(data => {
    if (force) {
      state.store_items = [];
      state.marketplace_items = [];
      state.others_items = [];
      state.custom_items = [];
    }
    calculateItems(data.items);
  })
}

function calculateItems(items) {
  items.forEach(item => {
    if (item.is_other_products) {
      state.others_items.push(item);
    } else if (item.is_trade_order) {
      state.marketplace_items.push(item);
    } else if (item.is_custom_card) {
      state.custom_items.push(item);
    } else {
      state.store_items.push(item);
    }
  })
}

onMounted(() => {
  getItems(true);
})
</script>
