<script lang="ts" setup>
import {onMounted, reactive, ref} from 'vue'
import {ShaplaButton, ShaplaCheckbox, ShaplaModal, ShaplaTable} from "@shapla/vue-components";
import ListItem from "../components/ListItem.vue";
import {createNewFont, deleteExtraFont, getExtraFonts, updatePreInstalledFont} from "../store.ts";
import {ExtraFontInterface} from "../../interfaces/custom-font.ts";

const state = reactive<{
  showAddNewModal: boolean;
  showEditModal: boolean,
  extra_fonts: ExtraFontInterface[],
  activeFont: null | ExtraFontInterface,
}>({
  showAddNewModal: false,
  showEditModal: false,
  extra_fonts: [],
  activeFont: null,
})

const formEl = ref(null);

const getFonts = () => {
  getExtraFonts().then(data => {
    state.extra_fonts = data.extra_fonts;
  })
}

const onSubmitForm = () => {
  const formData = new FormData(formEl.value);
  createNewFont(formData).then(data => {
    state.showAddNewModal = false;
    state.extra_fonts = data.extra_fonts;
  })
}

const onActionClick = (action, data) => {
  if ('edit' === action) {
    state.activeFont = data;
    state.showEditModal = true;
  }
  if ('delete' === action) {
    deleteExtraFont(data.slug).then(data => {
      state.extra_fonts = data.extra_fonts;
    });
  }
}

const updateExtraFont = () => {
  updatePreInstalledFont(state.activeFont).then((data) => {
    state.extra_fonts = data.extra_fonts;
  })
}

onMounted(() => {
  getFonts();
})
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Extra Fonts</h1>
    <hr class="wp-header-end">
    <div class="flex mb-2 space-x-2">
      <div class="flex-grow"></div>
      <ShaplaButton @click="getFonts" theme="primary" size="small" outline>Refresh</ShaplaButton>
      <ShaplaButton @click="state.showAddNewModal = true" theme="primary" size="small">Add New</ShaplaButton>
    </div>
    <div>
      <ShaplaTable
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
              {label:'For Public',key:'for_public'},
              {label:'For Designer',key:'for_designer'},
          ]"
          :items="state.extra_fonts"
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
        <ShaplaButton theme="primary" @click="updateExtraFont">Update</ShaplaButton>
      </template>
    </ShaplaModal>
    <ShaplaModal v-if="state.showAddNewModal" :active="state.showAddNewModal" @close="state.showAddNewModal = false"
                 title="Add New Font">
      <form method="post" id="add-new-font-form" autocomplete="off" enctype="multipart/form-data" ref="formEl">
        <ListItem label="Font File">
          <input type="file" name="font_file" accept=".ttf" required>
          <p>Only TTF font file.</p>
        </ListItem>
        <ListItem label="Font Family">
          <input type="text" name="font_family" required>
        </ListItem>
        <ListItem label="Font Group">
          <select name="group" required>
            <option value="">---</option>
            <option value="sans-serif">sans-serif</option>
            <option value="serif">serif</option>
            <option value="cursive">cursive</option>
          </select>
        </ListItem>
        <ListItem label="For Public">
          <div class="flex space-x-2 items-center">
            <div class="inline-flex">
              <input type="checkbox" name="for_public" value="yes">
            </div>
            <p class="text-xs">If enabled, font can be used for inner message.</p>
          </div>
        </ListItem>
        <ListItem label="For Designer">
          <div class="flex space-x-2 items-center">
            <div class="inline-flex">
              <input type="checkbox" name="for_designer" value="yes">
            </div>
            <p class="text-xs">If enabled, designer can use this font for static text. Designer can use font for dynamic
              text when it is also set for public use.</p>
          </div>
        </ListItem>
      </form>
      <template v-slot:foot>
        <ShaplaButton theme="primary" @click="onSubmitForm">Submit</ShaplaButton>
      </template>
    </ShaplaModal>
  </div>
</template>
