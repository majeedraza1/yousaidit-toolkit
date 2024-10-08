<script lang="ts" setup>
import {onMounted, reactive} from 'vue'
import ListItem from "../components/ListItem.vue";
import {getItems, updatePreInstalledFont} from "../store";
import {ShaplaButton, ShaplaCheckbox, ShaplaModal, ShaplaTable} from "@shapla/vue-components";
import {PreInstalledFontInterface} from "../../interfaces/custom-font.ts";

const state = reactive<{
  default_fonts: PreInstalledFontInterface[],
  showEditModal: boolean,
  activeFont: null | PreInstalledFontInterface
}>({
  default_fonts: [],
  showEditModal: false,
  activeFont: null
})

const getFonts = () => {
  getItems().then(data => {
    state.default_fonts = data.default_fonts as PreInstalledFontInterface[];
  })
}
const onActionClick = (action: string, data: PreInstalledFontInterface) => {
  if ('edit' === action) {
    state.activeFont = data;
    state.showEditModal = true;
  }
}
const onUpdateFont = () => {
  updatePreInstalledFont(state.activeFont).then((data) => {
    state.default_fonts = data.default_fonts as PreInstalledFontInterface[];
  })
}

onMounted(() => {
  getFonts();
})
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Pre-installed Fonts</h1>
    <hr class="wp-header-end">
    <div class="flex mb-2">
      <div class="flex-grow"></div>
      <ShaplaButton @click="getFonts" theme="primary" size="small" outline>Refresh</ShaplaButton>
    </div>
    <div>
      <ShaplaTable
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
              {label:'For Public',key:'for_public'},
              {label:'For Designer',key:'for_designer'},
          ]"
          :items="state.default_fonts"
          :actions="[{label:'Edit',key:'edit'}]"
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
            <shapla-checkbox :value="state.activeFont.for_designer" v-model="state.activeFont.for_designer"/>
          </div>
          <p class="text-xs">If enabled, designer can use this font for static text. Designer can use font for dynamic
            text when it is also set for public use.</p>
        </div>
      </ListItem>
      <template v-slot:foot>
        <ShaplaButton theme="primary" @click="onUpdateFont">Update</ShaplaButton>
      </template>
    </ShaplaModal>
  </div>
</template>
