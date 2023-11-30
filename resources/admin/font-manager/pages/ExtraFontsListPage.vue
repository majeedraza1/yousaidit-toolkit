<script lang="js">
import {defineComponent} from 'vue'
import {dataTable, Dialog, modal, shaplaButton, shaplaCheckbox} from "shapla-vue-components";
import ListItem from "../components/ListItem.vue";
import {createNewFont, deleteExtraFont, getExtraFonts, updatePreInstalledFont} from "@/admin/font-manager/store";

export default defineComponent({
  name: "ExtraFontsListPage",
  components: {ListItem, shaplaButton, dataTable, modal, Dialog, shaplaCheckbox},
  data() {
    return {
      showAddNewModal: false,
      showEditModal: false,
      extra_fonts: [],
      activeFont: {},
    }
  },
  methods: {
    getFonts() {
      getExtraFonts().then(data => {
        this.extra_fonts = data.extra_fonts;
      })
    },
    onSubmitForm() {
      const formEl = this.$el.querySelector('#add-new-font-form');
      const formData = new FormData(formEl);
      createNewFont(formData).then(data => {
        this.showAddNewModal = false;
        this.extra_fonts = data.extra_fonts;
      })
    },
    onActionClick(action, data) {
      if ('edit' === action) {
        this.activeFont = data;
        this.showEditModal = true;
      }
      if ('delete' === action) {
        if (window.confirm('Are you sure to delete the font?')) {
          deleteExtraFont(data.slug).then(data => {
            this.extra_fonts = data.extra_fonts;
          });
        }
      }
    },
    updateExtraFont() {
      updatePreInstalledFont(this.activeFont).then((data) => {
        this.extra_fonts = data.extra_fonts;
      })
    }
  },
  mounted() {
    this.getFonts();
  }
})
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Extra Fonts</h1>
    <hr class="wp-header-end">
    <div class="flex mb-2 space-x-2">
      <div class="flex-grow"></div>
      <shapla-button @click="getFonts" theme="primary" size="small" outline>Refresh</shapla-button>
      <shapla-button @click="showAddNewModal = true" theme="primary" size="small">Add New</shapla-button>
    </div>
    <div>
      <data-table
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
              {label:'For Public',key:'for_public'},
              {label:'For Designer',key:'for_designer'},
          ]"
          :items="extra_fonts"
          :actions="[{label:'Edit',key:'edit'},{label:'Delete',key:'delete'}]"
          @action:click="onActionClick"
      >
        <template v-slot:for_public="data">
          <span v-if="data.row.for_public">Yes</span>
          <span v-if="!data.row.for_public" class="text-red-600">No</span>
        </template>
        <template v-slot:for_designer="data">
          <span v-if="data.row.for_designer">Yes</span>
          <span v-if="!data.row.for_designer" class="text-red-600">No</span>
        </template>
      </data-table>
    </div>
    <modal :active="showEditModal" @close="showEditModal = false" title="Edit Font" content-size="large">
      <list-item label="Slug" :value="activeFont.slug"/>
      <list-item label="Font Family" :value="activeFont.font_family"/>
      <list-item label="Font File" :value="activeFont.font_file"/>
      <list-item label="Group" :value="activeFont.group"/>
      <list-item label="Path" :value="activeFont.path"/>
      <list-item label="Url" :value="activeFont.url"/>
      <list-item label="For Public">
        <div class="flex space-x-2 items-center">
          <div class="inline-flex">
            <shapla-checkbox :value="activeFont.for_public" v-model="activeFont.for_public"/>
          </div>
          <p class="text-xs">If enabled, font can be used for inner message.</p>
        </div>
      </list-item>
      <list-item label="For Designer">
        <div class="flex space-x-2 items-center">
          <div class="inline-flex">
            <shapla-checkbox :value="activeFont.for_designer" v-model="activeFont.for_designer"/>
          </div>
          <p class="text-xs">If enabled, designer can use this font for static text. Designer can use font for dynamic
            text when it is also set for public use.</p>
        </div>
      </list-item>
      <template v-slot:foot>
        <shapla-button theme="primary" @click="updateExtraFont">Update</shapla-button>
      </template>
    </modal>
    <modal v-if="showAddNewModal" :active="showAddNewModal" @close="showAddNewModal = false" title="Add New Font">
      <form method="post" id="add-new-font-form" autocomplete="off" enctype="multipart/form-data">
        <list-item label="Font File">
          <input type="file" name="font_file" accept=".ttf" required>
          <p>Only TTF font file.</p>
        </list-item>
        <list-item label="Font Family">
          <input type="text" name="font_family" required>
        </list-item>
        <list-item label="Font Group">
          <select name="group" required>
            <option value="">---</option>
            <option value="sans-serif">sans-serif</option>
            <option value="serif">serif</option>
            <option value="cursive">cursive</option>
          </select>
        </list-item>
        <list-item label="For Public">
          <div class="flex space-x-2 items-center">
            <div class="inline-flex">
              <input type="checkbox" name="for_public" value="yes">
            </div>
            <p class="text-xs">If enabled, font can be used for inner message.</p>
          </div>
        </list-item>
        <list-item label="For Designer">
          <div class="flex space-x-2 items-center">
            <div class="inline-flex">
              <input type="checkbox" name="for_designer" value="yes">
            </div>
            <p class="text-xs">If enabled, designer can use this font for static text. Designer can use font for dynamic
              text when it is also set for public use.</p>
          </div>
        </list-item>
      </form>
      <template v-slot:foot>
        <shapla-button theme="primary" @click="onSubmitForm">Submit</shapla-button>
      </template>
    </modal>
  </div>
</template>
