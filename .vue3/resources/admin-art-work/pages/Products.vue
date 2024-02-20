<template>
  <div class="wrap">
    <h1 class="wp-heading-inline">Products</h1>
    <hr class="wp-header-end">
    <ShaplaColumns :multiline="true">
      <ShaplaColumn :tablet="8">&nbsp;</ShaplaColumn>
      <ShaplaColumn :tablet="4">
        <search-form @search="searchProduct"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <data-table
            :columns="[
        {key: 'title', label: 'Product Title'},
        {key: 'product_type', label: 'Product Type'},
        {key: 'product_sku', label: 'Product SKU'},
        {key: 'art_work', label: 'Art Work'},]"
            :actions="[{key: 'pdf_cards', label: 'PDF Cards'}]"
            :items="state.items"
            @action:click="handleActionClick"
            :show-cb="false"
        >
          <template v-slot:product_sku="data">
            <span v-html="getProductSku(data.row)"/>
          </template>
          <template v-slot:art_work="data">
            <template v-if="data.row.product_type === 'variable'">
              <div v-for="variation in data.row.variations">
                <shapla-chip v-if="variation.art_work">{{ variation.art_work.title }}</shapla-chip>
                <span v-else> - </span>
              </div>
            </template>
            <template v-else>
              <shapla-chip v-if="data.row.art_work.title">{{ data.row.art_work.title }}</shapla-chip>
              <span v-else> - </span>
            </template>
          </template>
        </data-table>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTablePagination
            :total-items="state.total_items"
            :per-page="50"
            :current-page="state.current_page"
            @paginate="paginate"
        />
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaModal :active="state.showPdfModal" @close="hidePdfModal" title="PDF Cards">
      <table class="shapla-data-table shapla-data-table--fullwidth" v-if="Object.keys(state.active_item).length">
        <thead>
        <tr class="shapla-data-table__header-row">
          <th class="shapla-data-table__cell--non-numeric">SKU</th>
          <th class="shapla-data-table__cell--non-numeric">PDF Card</th>
        </tr>
        </thead>
        <tbody>
        <template v-if="state.active_item.product_type === 'variable'">
          <tr v-for="variation in state.active_item.variations" :key="variation.id">
            <td class="shapla-data-table__cell--non-numeric">{{ variation.sku }}</td>
            <td class="shapla-data-table__cell--non-numeric">
              <pdf-uploader v-model="variation.art_work" :id="variation.id"/>
            </td>
          </tr>
        </template>
        <template v-else>
          <tr>
            <td class="shapla-data-table__cell--non-numeric">{{ state.active_item.product_sku }}</td>
            <td class="shapla-data-table__cell--non-numeric">
              <pdf-uploader v-model="state.active_item.art_work" :id="state.active_item.id"/>
            </td>
          </tr>
        </template>
        </tbody>
      </table>
      <template v-slot:foot>
        <shapla-button theme="default" shadow @click="hidePdfModal">Close</shapla-button>
      </template>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import PdfUploader from "./PdfUploader.vue";
import {
  ShaplaButton,
  ShaplaChip,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaModal,
  ShaplaTablePagination
} from '@shapla/vue-components';
import {getProducts as _getProducts, searchProduct as _searchProduct} from "../store.ts";
import {onMounted, reactive} from "vue";

const state = reactive({
  items: [],
  total_items: 0,
  current_page: 1,
  active_item: {},
  showPdfModal: false,
})

function getProductSku(item) {
  if ('variable' === item.product_type) {
    let html = '';
    item.variations.forEach(variation => {
      html += variation.sku + '<br/>';
    });
    return html;
  }
  return item.product_sku;
}

function handleActionClick(action, item) {
  if ('pdf_cards' === action) {
    state.active_item = item;
    state.showPdfModal = true;
  }
}

function hidePdfModal() {
  state.active_item = {};
  state.showPdfModal = false;
}

const paginate = (page) => {
  state.current_page = page;
  getProducts();
}

function getProducts() {
  _getProducts(this.current_page).then(data => {
    state.items = data.items;
    state.total_items = data.total_items;
  })
}

const searchProduct = (query) => {
  _searchProduct(query).then(data => {
    state.items = data.items;
    state.total_items = data.total_items;
  })
}

onMounted(() => {
  getProducts();
})
</script>
