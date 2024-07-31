<script setup lang="ts">
import {ShaplaButton, ShaplaTab, ShaplaTable, ShaplaTablePagination, ShaplaTabs} from "@shapla/vue-components";
import {Spinner} from "@shapla/vanilla-components";
import {onMounted, reactive} from "vue";
import axios from "../../utils/axios.ts";
import {DesignerFontInfoInterface, PreInstalledFontInterface} from "../../interfaces/custom-font.ts";
import {PaginationDataInterface, ServerCollectionResponseDataInterface} from "../../utils/CrudOperation.ts";
import ModalAddFont from "../components/ModalAddFont.vue";

const state = reactive<{
  openAddFontModal: boolean;
  preInstalledFonts: PreInstalledFontInterface[];
  myFonts: DesignerFontInfoInterface[];
  pagination: PaginationDataInterface;
}>({
  openAddFontModal: false,
  myFonts: [],
  pagination: {per_page: 20, total_items: 0, current_page: 1},
  preInstalledFonts: [],
})

const getMyFonts = (page: number = 1) => {
  Spinner.show();
  axios
      .get('designers/fonts', {
        params: {
          page: page,
          per_page: state.pagination.per_page
        }
      })
      .then(response => {
        const data = response.data.data as ServerCollectionResponseDataInterface;
        state.myFonts = data.items as DesignerFontInfoInterface[];
        state.pagination = data.pagination as PaginationDataInterface;
        state.preInstalledFonts = data.pre_installed_fonts as PreInstalledFontInterface[]
      })
      .finally(() => {
        Spinner.hide();
      })
}

const onPagination = (nextPage: number) => {
  getMyFonts(nextPage);
}

const onFontAdded = (fontInfo: DesignerFontInfoInterface) => {
  state.openAddFontModal = false;
  state.myFonts.push(fontInfo);
  window.DesignerProfile.fonts.push({
    label: fontInfo.font_family,
    key: fontInfo.slug,
    fontUrl: fontInfo.url,
    for_public: fontInfo.for_public,
    for_designer: fontInfo.for_designer
  });
}

onMounted(() => {
  getMyFonts();
})
</script>

<template>
  <ShaplaTabs alignment="center" tab-style="rounded">
    <ShaplaTab name="My Fonts" selected>
      <div class="mb-4 flex">
        <div class="flex-grow"></div>
        <ShaplaButton theme="primary" size="small" @click="()=>state.openAddFontModal = true">Add New Font
        </ShaplaButton>
      </div>
      <div class="mb-4">
        <ShaplaTablePagination
            :total-items="state.pagination.total_items"
            :per-page="state.pagination.per_page"
            :current-page="state.pagination.current_page"
            @paginate="onPagination"
        />
      </div>
      <ShaplaTable
          :show-cb="false"
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
          ]"
          :items="state.myFonts"
      >
        <template v-slot:font_family="data">
          <span :class="`font-family-${data.row.slug}`" :style="`font-family: ${data.row.font_family};`">
            {{ data.row.font_family }}
          </span>
        </template>
      </ShaplaTable>
      <div class="mt-4">
        <ShaplaTablePagination
            :total-items="state.pagination.total_items"
            :per-page="state.pagination.per_page"
            :current-page="state.pagination.current_page"
            @paginate="onPagination"
        />
      </div>
    </ShaplaTab>
    <ShaplaTab name="Pre-installed Fonts">
      <ShaplaTable
          :show-cb="false"
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
          ]"
          :items="state.preInstalledFonts"
      >
        <template v-slot:font_family="data">
          <span :class="`font-family-${data.row.slug}`" :style="`font-family: ${data.row.font_family};`">
            {{ data.row.font_family }}
          </span>
        </template>
      </ShaplaTable>
    </ShaplaTab>
  </ShaplaTabs>
  <ModalAddFont
      :active="state.openAddFontModal"
      @close="state.openAddFontModal = false"
      @font:added="onFontAdded"
  />
</template>