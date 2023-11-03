<script>
import {getPendingOrders, syncPurchases} from '../store.ts'
import {dataTable, pagination, shaplaButton} from 'shapla-vue-components'

export default {
  name: "Purchases",
  components: {dataTable, shaplaButton, pagination},
  data() {
    return {
      items: [],
      pagination: {current_page: 1, per_page: 20, total_items: 0}
    }
  },
  methods: {
    getItems(page = 1) {
      getPendingOrders(page).then(data => {
        this.items = data.items;
        this.pagination = data.pagination;
      })
    },
    syncPurchases(){
      syncPurchases();
    }
  },
  mounted() {
    this.getItems();
  }
}
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">In Queue</h1>
    <hr class="wp-header-end">
    <div class="flex mb-4 space-x-2">
      <div class="flex-grow"></div>
      <shapla-button size="small" theme="primary" outline @click="getItems">Refresh</shapla-button>
      <shapla-button size="small" theme="primary" @click="syncPurchases">Sync for new Purchase</shapla-button>
    </div>
    <div class="mb-4">
      <pagination :current_page="pagination.current_page" :per_page="pagination.per_page"
                  :total_items="pagination.total_items" @pagination="getItems"/>
    </div>
    <div class="my-4">
      <data-table
          :columns="[{key:'shipstation_order_id',label:'ShipStation Order ID'},{key:'store_name',label:'Store'},{key:'created_at',label:'Date'}]"
          :items="items"
      >
        <template v-slot:message="data">
          <div class="max-w-xs md:max-w-sm xl:max-w-xl 2xl:max-w-2xl text-ellipsis overflow-hidden">{{
              data.row.message
            }}
          </div>
        </template>
      </data-table>
    </div>
    <div class="mt-4">
      <pagination :current_page="pagination.current_page" :per_page="pagination.per_page"
                  :total_items="pagination.total_items" @pagination="getItems"/>
    </div>
  </div>
</template>
