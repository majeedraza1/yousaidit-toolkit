<script lang="ts" setup>
import {reactive, watch} from 'vue'
import {ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";

const emit = defineEmits<{
  update: [value: any]
}>()

const props = defineProps({
  im: {type: Object, default: () => ({})}
})

const state = reactive({
  message: {
    content: '',
    font: '',
    align: '',
    color: '',
    size: 16,
  }
})

watch(() => props.im, newValue => {
  state.message = newValue;
}, {deep: true})

watch(() => state.message, newValue => {
  emit('update', newValue);
}, {deep: true})
</script>

<template>
  <ShaplaColumns multiline>
    <ShaplaColumn :tablet="12">
      <label for="_message_content">Content</label>
      <textarea id="_message_content" v-model="state.message.content" class="w-full" rows="5"></textarea>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <label for="_font_size">Font Size</label>
      <input type="text" id="_font_size" v-model="state.message.size" readonly class="w-full"/>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <label for="_font_family">Font</label>
      <input type="text" id="_message_content" v-model="state.message.font" readonly class="w-full"/>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <label for="_text_align">Align</label>
      <input type="text" id="_text_align" v-model="state.message.align" readonly class="w-full"/>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <label for="_text_color">Text Color</label>
      <input type="text" id="_text_color" v-model="state.message.color" readonly class="w-full"/>
    </ShaplaColumn>
  </ShaplaColumns>
</template>
