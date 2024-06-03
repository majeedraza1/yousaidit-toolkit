<script setup lang="ts">
import {ShaplaButton, ShaplaTable, ShaplaTablePagination} from "@shapla/vue-components";
import {onMounted, reactive} from "vue";
import {DesignerFontInfoInterface} from "../../interfaces/custom-font.ts";
import {getDesignerFonts as _getDesignerFonts} from '../store.ts'
import {PaginationDataInterface} from "../../utils/CrudOperation.ts";

const state = reactive<{
  items: DesignerFontInfoInterface[];
  pagination: PaginationDataInterface;
}>({
  items: [],
  pagination: {current_page: 1, per_page: 20, total_items: 0}
})

const getDesignerFonts = (page: number = 1) => {
  _getDesignerFonts(page, state.pagination.per_page).then(info => {
    state.items = info.items as DesignerFontInfoInterface[];
    state.pagination = info.pagination;
  })
}

const onActionClick = (action: 'edit' | 'delete', item: DesignerFontInfoInterface) => {
}

onMounted(() => {
  getDesignerFonts();
})
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Designer Fonts</h1>
    <hr class="wp-header-end">
    <div class="flex mb-2 space-x-2">
      <div class="flex-grow"></div>
      <ShaplaButton @click="getDesignerFonts" theme="primary" size="small" outline>Refresh</ShaplaButton>
    </div>
    <div>
      <div class="mb-4">
        <ShaplaTablePagination
            :current-page="state.pagination.current_page"
            :per-page="state.pagination.per_page"
            :total-items="state.pagination.total_items"
            @paginate="getDesignerFonts"
        />
      </div>
      <ShaplaTable
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
              {label:'For Public',key:'for_public'},
              {label:'For Designer',key:'for_designer'},
          ]"
          :items="state.items"
          :actions="[{label:'Edit',key:'edit'},{label:'Delete',key:'delete'}]"
          @click:action="onActionClick"
      >
        <template v-slot:for_public="data">
          <span v-if="data.row.for_public">Yes</span>
          <span v-if="!data.row.for_public" class="text-red-600">No</span>
        </template>
        <template v-slot:for_designer="data">
          <span v-if="data.row.for_designer">Yes</span>
          <span v-if="!data.row.for_designer" class="text-red-600">No</span>
        </template>
      </ShaplaTable>
      <div class="mt-4">
        <ShaplaTablePagination
            :current-page="state.pagination.current_page"
            :per-page="state.pagination.per_page"
            :total-items="state.pagination.total_items"
            @paginate="getDesignerFonts"
        />
      </div>
    </div>
  </div>
</template>
