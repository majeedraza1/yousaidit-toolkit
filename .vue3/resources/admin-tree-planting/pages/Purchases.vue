<script lang="ts" setup>
import {deleteItems, getPurchases, syncPurchase, syncPurchases} from '../store.ts'
import {onMounted, reactive} from "vue";
import {ShaplaButton, ShaplaTable, ShaplaTablePagination, ShaplaTableStatusList} from "@shapla/vue-components";

const state = reactive({
  items: [],
  selectedItems: [],
  status: 'complete',
  statuses: [],
  pagination: {current_page: 1, per_page: 20, total_items: 0},
})

const getItems = (page: number = 1) => {
  getPurchases(page, state.status).then(data => {
    state.items = data.items;
    state.pagination = data.pagination;
    state.statuses = data.statuses;
  })
}
onMounted(() => {
  getItems()
})

const changeStatus = (newStatus) => {
  state.status = newStatus.key;
  getItems();
}

function onActionClick(action: string, item) {
  if ('sync' === action) {
    syncPurchase(item.id).then(() => {
      getItems();
    })
  }
}

function onSelectItems(selectedItems: number[]) {
  state.selectedItems = selectedItems;
}

function deleteSelectedItems() {
  deleteItems(state.selectedItems).then(() => {
    state.selectedItems = [];
    getItems();
  });
}

</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Purchases</h1>
    <hr class="wp-header-end">
    <div class="flex mb-4 space-x-2">
      <div class="flex-grow"></div>
      <ShaplaButton v-if="state.selectedItems.length" size="small" theme="error" @click="deleteSelectedItems">Delete
        Selected
      </ShaplaButton>
      <ShaplaButton size="small" theme="primary" outline @click="()=>getItems()">Refresh</ShaplaButton>
      <ShaplaButton size="small" theme="primary" @click="syncPurchases">Sync for new Purchase</ShaplaButton>
    </div>
    <div class="mb-4 flex items-center">
      <ShaplaTableStatusList :statuses="state.statuses" @change="changeStatus"/>
      <div class="flex-grow"></div>
      <ShaplaTablePagination :current-page="state.pagination.current_page" :per-page="state.pagination.per_page"
                             :total-items="state.pagination.total_items" @paginate="getItems"/>
    </div>
    <div class="my-4">
      <ShaplaTable
          :columns="[{key:'message',label:'Title'},{key:'status',label:'Status'},{key:'created_at',label:'Date'}]"
          :actions="'complete' !== state.status ?[{key:'sync',label:'Sync'}]:[]"
          :items="state.items"
          :selected-items="state.selectedItems"
          @click:action="onActionClick"
          @select:item="onSelectItems"
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
