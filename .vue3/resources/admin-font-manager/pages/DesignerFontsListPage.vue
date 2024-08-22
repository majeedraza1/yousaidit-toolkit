<script setup lang="ts">
import {ShaplaButton, ShaplaCheckbox, ShaplaModal, ShaplaTable, ShaplaTablePagination} from "@shapla/vue-components";
import {onMounted, reactive} from "vue";
import {DesignerFontInfoInterface} from "../../interfaces/custom-font.ts";
import {deleteDesignerFont, getDesignerFonts as _getDesignerFonts, updateDesignerFont} from '../store.ts'
import {PaginationDataInterface} from "../../utils/CrudOperation.ts";
import ListItem from "../components/ListItem.vue";

const state = reactive<{
  items: DesignerFontInfoInterface[];
  pagination: PaginationDataInterface;
  activeFont: null | DesignerFontInfoInterface,
  showEditModal: boolean,
}>({
  items: [],
  pagination: {current_page: 1, per_page: 20, total_items: 0},
  activeFont: null,
  showEditModal: false,
})

const getDesignerFonts = (page: number = 1) => {
  _getDesignerFonts(page, state.pagination.per_page).then(info => {
    state.items = info.items as DesignerFontInfoInterface[];
    state.pagination = info.pagination;
  })
}

const onActionClick = (action: 'edit' | 'delete', item: DesignerFontInfoInterface) => {
  if ('edit' === action) {
    state.activeFont = item;
    state.showEditModal = true;
  }
  if ('delete' === action) {
    deleteDesignerFont(item.id).then(() => {
      getDesignerFonts();
    })
  }
}

const updateFont = () => {
  updateDesignerFont(state.activeFont).then(data => {
    window.console.log(data);
  })
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
            @click:action="onActionClick"
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
  <ShaplaModal v-if="state.activeFont" :active="state.showEditModal" @close="state.showEditModal = false"
               title="Edit Font" content-size="large">
    <ListItem label="Slug" :value="state.activeFont.slug"/>
    <ListItem label="Font Family" :value="state.activeFont.font_family"/>
    <ListItem label="Font File" :value="state.activeFont.font_file"/>
    <ListItem label="Group" :value="state.activeFont.group"/>
    <ListItem label="Path" :value="state.activeFont.path"/>
    <ListItem label="Url" :value="state.activeFont.url"/>
    <ListItem label="For Public">
      <div class="flex space-x-2 items-center">
        <div class="inline-flex">
          <shapla-checkbox :value="state.activeFont.for_public" v-model="state.activeFont.for_public"/>
        </div>
        <p class="text-xs">If enabled, font can be used for inner message.</p>
      </div>
    </ListItem>
    <ListItem label="For Designer">
      <div class="flex space-x-2 items-center">
        <div class="inline-flex">
          <ShaplaCheckbox :value="state.activeFont.for_designer" v-model="state.activeFont.for_designer"/>
        </div>
        <p class="text-xs">If enabled, designer can use this font for static text. Designer can use font for dynamic
          text when it is also set for public use.</p>
      </div>
    </ListItem>
    <template v-slot:foot>
      <ShaplaButton theme="primary" @click="updateFont">Update</ShaplaButton>
    </template>
  </ShaplaModal>
</template>
