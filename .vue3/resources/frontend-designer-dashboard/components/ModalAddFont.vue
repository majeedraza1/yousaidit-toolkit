<script setup lang="ts">
import {ShaplaButton, ShaplaModal} from '@shapla/vue-components'
import ListItem from "./ListItem.vue";
import {ref} from "vue";
import useDesignerCardStore from '../stores/store-cards.ts'
import {FontInfoInterface} from "../../interfaces/custom-font.ts";

const store = useDesignerCardStore();

defineProps({
  active: {type: Boolean, default: false},
})
const emit = defineEmits<{
  close: [];
  'font:added': [value: FontInfoInterface]
}>();

const emitClose = () => emit('close');
const formEl = ref(null);

const onSubmitForm = () => {
  if (formEl.value) {
    const formData = new FormData(formEl.value);
    store.createNewFont(formData).then((data) => {
      emit('font:added', data);
    })
  }
}
</script>

<template>
  <ShaplaModal v-if="active" :active="active" @close="emitClose" title="Add New Font">
    <form method="post" id="add-new-font-form" autocomplete="off" enctype="multipart/form-data" ref="formEl">
      <ListItem label="Font File">
        <input type="file" name="font_file" accept=".ttf" required>
        <p>Only TTF font file.</p>
      </ListItem>
      <ListItem label="Font Family">
        <input type="text" name="font_family" required>
      </ListItem>
      <ListItem label="Font Group">
        <label>
          <input type="radio" name="group" value="sans-serif">
          <span>sans-serif</span>
        </label>
        <label>
          <input type="radio" name="group" value="serif">
          <span>serif</span>
        </label>
        <label>
          <input type="radio" name="group" value="cursive">
          <span>cursive</span>
        </label>
      </ListItem>
    </form>
    <template v-slot:foot>
      <ShaplaButton theme="primary" @click="onSubmitForm">Submit</ShaplaButton>
    </template>
  </ShaplaModal>
</template>
