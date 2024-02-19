<script lang="ts" setup>
import {getPendingOrders, syncPurchases} from '../store.ts'
import {ShaplaButton,ShaplaTable,ShaplaTablePagination} from '@shapla/vue-components'
import {onMounted, reactive} from "vue";

const state = reactive({
  items: [],
  pagination: {current_page: 1, per_page: 20, total_items: 0}
})

const getItems = (page: number = 1) => {
  getPendingOrders(page).then(data => {
    state.items = data.items;
    state.pagination = data.pagination;
  })
}

onMounted(() => {
  getItems();
})
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">In Queue</h1>
    <hr class="wp-header-end">
    <div class="flex mb-4 space-x-2">
      <div class="flex-grow"></div>
      <shapla-button size="small" theme="primary" outline @click="()=> getItems()">Refresh</shapla-button>
      <shapla-button size="small" theme="primary" @click="syncPurchases">Sync for new Purchase</shapla-button>
    </div>
    <div class="mb-4">
      <ShaplaTablePagination :current-page="state.pagination.current_page" :per-page="state.pagination.per_page"
                  :total-items="state.pagination.total_items" @paginate="getItems"/>
    </div>
    <div class="my-4">
      <ShaplaTable
          :columns="[
              {key:'shipstation_order_id',label:'ShipStation Order ID'},
              {key:'store_name',label:'Store'},
              {key:'created_at',label:'Date'}
              ]"
          :items="state.items"
      >
        <template v-slot:message="data">
          <div class="max-w-xs md:max-w-sm xl:max-w-xl 2xl:max-w-2xl text-ellipsis overflow-hidden">{{
              data.row.message
            }}
          </div>
        </template>
      </ShaplaTable>
    </div>
    <div class="mt-4">
      <ShaplaTablePagination :current-page="state.pagination.current_page" :per-page="state.pagination.per_page"
                  :total-items="state.pagination.total_items" @paginate="getItems"/>
    </div>
  </div>
</template>
