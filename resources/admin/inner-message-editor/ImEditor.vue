<script lang="ts">
import {defineComponent} from 'vue'
import {column, columns} from "shapla-vue-components";

export default defineComponent({
  name: "ImEditor",
  components: {'shapla-columns': columns, 'shapla-column': column},
  props: {
    im: {type: Object, default: () => ({})}
  },
  data() {
    return {
      message: {
        content: '',
        font: '',
        align: '',
        color: '',
        size: 16,
      }
    }
  },
  watch: {
    im: {
      handler: function (newValue) {
        this.message = newValue
      },
      deep: true,
    },
    message: {
      handler: function (newValue) {
        this.$emit('update', newValue);
      },
      deep: true,
    }
  }
})
</script>

<template>
  <shapla-columns multiline>
    <shapla-column :tablet="12">
      <label for="_message_content">Content</label>
      <textarea id="_message_content" v-model="message.content" class="w-full" rows="5"></textarea>
    </shapla-column>
    <shapla-column :tablet="12">
      <label for="_font_size">Font Size</label>
      <input type="text" id="_font_size" v-model="message.size" readonly class="w-full"/>
    </shapla-column>
    <shapla-column :tablet="12">
      <label for="_font_family">Font</label>
      <input type="text" id="_message_content" v-model="message.font" readonly class="w-full"/>
    </shapla-column>
    <shapla-column :tablet="12">
      <label for="_text_align">Align</label>
      <input type="text" id="_text_align" v-model="message.align" readonly class="w-full"/>
    </shapla-column>
    <shapla-column :tablet="12">
      <label for="_text_color">Text Color</label>
      <input type="text" id="_text_color" v-model="message.color" readonly class="w-full"/>
    </shapla-column>
  </shapla-columns>
</template>
