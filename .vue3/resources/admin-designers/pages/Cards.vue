<template>
  <div>
    <h1 class="wp-heading-inline">Cards</h1>
    <hr class="wp-header-end">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="4"></ShaplaColumn>
      <ShaplaColumn :tablet="4"></ShaplaColumn>
      <ShaplaColumn :tablet="4">
        <ShaplaSearchForm placeholder="Search title, sku" @input="handleSearchInput" @search="handleSearch"/>
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="8">
        <div class="relative flex space-x-2 items-center">
          <ShaplaSelect
              label="Card Status"
              :options="store.cards_statuses"
              label-key="label_with_count"
              value-key="key"
              v-model="store.cards_filter_args.status"
              :clearable="false"
          />
          <ShaplaSelect
              label="Card Type"
              :options="[{label:'All',value:'all'},{label:'Static',value:'static'},{label:'Dynamic',value:'dynamic'}]"
              v-model="store.cards_filter_args.card_type"
              :clearable="false"
          />
          <ShaplaSelect
              label="Card Designer"
              :options="store.designers"
              label-key="display_name"
              value-key="id"
              v-model="store.cards_filter_args.designer_id"
          />
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="4">
        <ShaplaTablePagination
            :current-page="store.cards_pagination.current_page"
            :per-page="store.cards_pagination.per_page"
            :total-items="store.cards_pagination.total_items"
            @pagination="store.getCards"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTable
            :items="store.cards"
            :columns="columns"
            :show-cb="false"
            :actions="[{key: 'view', label: 'View'}]"
            @click:action="handleActionClick"
        >
          <template v-slot:card_sizes="data">
            <template v-for="(_size,index) in card_sizes" :key="index">
              <template v-if="data.row.card_sizes.includes(_size.value)">
                <template v-if="index === 0">{{ _size.label }}</template>
                <template v-else>, {{ _size.label }}</template>
              </template>
            </template>
          </template>
          <template v-slot:status="data">
            <template v-for="_status in store.cards_statuses">
						<span v-if="_status.key === data.row.status">
							{{ _status.label }}
						</span>
            </template>
          </template>
          <template v-slot:designer="data">
            <a href="" @click.prevent="goToDesignerProfile(data.row.designer)">
              {{ data.row.designer.display_name }}
            </a>
          </template>
        </ShaplaTable>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTablePagination
            :current-page="store.cards_pagination.current_page"
            :per-page="store.cards_pagination.per_page"
            :total-items="store.cards_pagination.total_items"
            @pagination="store.getCards"
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
  ShaplaSelect,
  ShaplaTable,
  ShaplaTablePagination
} from '@shapla/vue-components';
import useAdminDesignerStore from "../store.ts";
import {computed, onMounted, watch} from "vue";
import {useRouter} from "vue-router";
import {DesignerCardInterface, DesignerInterface} from "../../interfaces/designer.ts";

const store = useAdminDesignerStore();
const router = useRouter();

const columns = [
  {key: 'card_title', label: 'Title'},
  {key: 'card_type', label: 'Type'},
  {key: 'designer', label: 'Designer'},
  {key: 'card_sizes', label: 'Sizes'},
  {key: 'status', label: 'Status'},
  {key: 'card_sku', label: 'SKU'},
  {key: 'total_sale', label: 'Total Sales', numeric: true},
];

const card_sizes = computed(() => {
  return window.DesignerProfile.card_sizes.map(size => {
    return {
      value: size.slug,
      label: size.name
    }
  });
})

watch(() => store.cards_filter_args, () => store.getCards(), {deep: true})

onMounted(() => {
  store.getCards(1);
  store.getDesigners(1)
})


const handleSearchInput = (search: string) => {
  if (search.length < 1) {
    handleSearch('');
  }
}
const handleSearch = (search: string) => {
  store.cards_filter_args.search = search;
  store.getCards();
}
const handleActionClick = (action: string, item: DesignerCardInterface) => {
  if ('view' === action) {
    router.push({name: 'Card', params: {id: item.id}});
  }
}
const goToDesignerProfile = (designer: DesignerInterface) => {
  router.push({name: 'Designer', params: {id: designer.id}});
}
</script>
