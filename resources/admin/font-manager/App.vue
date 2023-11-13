<script>
import {defineComponent} from 'vue'
import {getItems, updatePreInstalledFont} from "./store";
import {dataTable, modal, shaplaButton, shaplaCheckbox} from "shapla-vue-components";
import ListItem from "./components/ListItem.vue";

export default defineComponent({
  name: "App",
  components: {ListItem, shaplaButton, dataTable, modal, shaplaCheckbox},
  data() {
    return {
      default_fonts: [],
      fonts: [],
      showEditModal: false,
      activeFont: {}
    }
  },
  methods: {
    getFonts() {
      getItems().then(data => {
        this.default_fonts = data.default_fonts;
      })
    },
    onActionClick(action, data) {
      if ('edit' === action) {
        this.activeFont = data;
        this.showEditModal = true;
      }
    },
    updatePreInstalledFont() {
      updatePreInstalledFont(this.activeFont).then((data) => {
        this.default_fonts = data.default_fonts;
      })
    }
  },
  mounted() {
    this.getFonts();
  }
})
</script>

<template>
  <div class="font-manager-container border-box-all">
    <h1 class="wp-heading-inline">Font Manager</h1>
    <hr class="wp-header-end">
    <div class="flex mb-2">
      <div class="flex-grow"></div>
      <shapla-button @click="getFonts" theme="primary" size="small" outline>Refresh</shapla-button>
    </div>
    <div>
      <data-table
          :columns="[
              {label:'Font Family',key:'font_family'},
              {label:'Group',key:'group'},
              {label:'For Public',key:'for_public'},
              {label:'For Designer',key:'for_designer'},
          ]"
          :items="default_fonts"
          :actions="[{label:'Edit',key:'edit'}]"
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
        <shapla-button theme="primary" @click="updatePreInstalledFont">Update</shapla-button>
      </template>
    </modal>
  </div>
</template>

<style lang="scss">
.font-manager-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
