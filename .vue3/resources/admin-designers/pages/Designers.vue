<template>
  <div class="yousaidit-admin-designers">
    <h1 class="wp-heading-inline">Designers</h1>
    <hr class="wp-header-end">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="8"></ShaplaColumn>
      <ShaplaColumn :tablet="4">
        <ShaplaSearchForm placeholder="Search designer ..." @input="handleSearchInput" @search="handleSearch"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTablePagination
            :current-page="store.designers_pagination.current_page"
            :per-page="store.designers_pagination.per_page"
            :total-items="store.designers_pagination.total_items"
            @paginate="store.getDesigners"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTable
            :items="store.designers"
            :columns="columns"
            :show-cb="false"
            :actions="[{key: 'view', label: 'View'}]"
            @click:action="handleActionClick"
        >
          <template v-slot:unpaid_commission="data">
            {{ data.row.currency_symbol }}{{ data.row.unpaid_commission }}
          </template>
          <template v-slot:paid_commission="data">
            {{ data.row.currency_symbol }}{{ data.row.paid_commission }}
          </template>
        </ShaplaTable>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTablePagination
            :current-page="store.designers_pagination.current_page"
            :per-page="store.designers_pagination.per_page"
            :total-items="store.designers_pagination.total_items"
            @paginate="store.getDesigners"
        />
      </ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaColumn,
  ShaplaColumns,
  ShaplaSearchForm,
  ShaplaTable,
  ShaplaTablePagination
} from '@shapla/vue-components';
import useAdminDesignerStore from "../store.ts";
import {onMounted} from "vue";
import {DesignerInterface} from "../../interfaces/designer.ts";
import {useRouter} from "vue-router";

const store = useAdminDesignerStore();
const router = useRouter();

onMounted(() => {
  if (store.designers.length < 1) {
    store.getDesigners();
  }
})

const columns = [
  {key: 'display_name', label: 'Name'},
  {key: 'email', label: 'Email'},
  {key: 'total_cards', label: 'Total Cards', numeric: true},
  {key: 'total_sales', label: 'Total Sales', numeric: true},
  {key: 'unpaid_commission', label: 'Commission (unpaid)', numeric: true},
  {key: 'paid_commission', label: 'Commission (paid)', numeric: true},
];

const handleSearchInput = (search: string) => {
  if (search.length < 1) {
    handleSearch('');
  }
}
const handleSearch = (search: string) => {
  store.designers_search = search;
  store.getDesigners();
}

const handleActionClick = (action: string, item: DesignerInterface) => {
  if ('view' === action) {
    router.push({name: 'Designer', params: {id: item.id}});
  }
}
</script>
