<script>
import {deleteItems, getPurchases, syncPurchase, syncPurchases} from '../store.ts'
import {dataTable, pagination, shaplaButton, statusList} from 'shapla-vue-components'

export default {
  name: "Purchases",
  components: {dataTable, shaplaButton, pagination, statusList},
  data() {
    return {
      items: [],
      selectedItems: [],
      status: 'complete',
      statuses: [],
      pagination: {current_page: 1, per_page: 20, total_items: 0},
    }
  },
  methods: {
    changeStatus(newStatus) {
      this.status = newStatus.key;
      this.getItems();
    },
    getItems(page = 1) {
      getPurchases(page, this.status).then(data => {
        this.items = data.items;
        this.pagination = data.pagination;
        this.statuses = data.statuses;
      })
    },
    onActionClick(action, item) {
      if ('sync' === action) {
        syncPurchase(item.id).then(() => {
          this.getItems();
        })
      }
    },
    syncPurchases() {
      syncPurchases();
    },
    onSelectItems(selectedItems) {
      this.selectedItems = selectedItems;
    },
    deleteSelectedItems() {
      deleteItems(this.selectedItems).then(() => {
        this.selectedItems = [];
        this.getItems();
      });
    }
  },
  mounted() {
    this.getItems();
  }
}
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Purchases</h1>
    <hr class="wp-header-end">
    <div class="flex mb-4 space-x-2">
      <div class="flex-grow"></div>
      <shapla-button v-if="selectedItems.length" size="small" theme="error" @click="deleteSelectedItems">Delete Selected
      </shapla-button>
      <shapla-button size="small" theme="primary" outline @click="()=>getItems()">Refresh</shapla-button>
      <shapla-button size="small" theme="primary" @click="syncPurchases">Sync for new Purchase</shapla-button>
    </div>
    <div class="mb-4 flex items-center">
      <status-list :statuses="statuses" @change="changeStatus"/>
      <div class="flex-grow"></div>
      <pagination :current_page="pagination.current_page" :per_page="pagination.per_page"
                  :total_items="pagination.total_items" @pagination="getItems"/>
    </div>
    <div class="my-4">
      <data-table
          :columns="[{key:'message',label:'Title'},{key:'status',label:'Status'},{key:'created_at',label:'Date'}]"
          :actions="'complete' !== status ?[{key:'sync',label:'Sync'}]:[]"
          @action:click="onActionClick"
          :items="items"
          :selected-items="selectedItems"
          @item:select="onSelectItems"
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
