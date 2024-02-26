<script lang="ts" setup>
import {onMounted, reactive} from 'vue'
import {ShaplaButton, ShaplaModal} from '@shapla/vue-components';
import ImEditor from "./ImEditor.vue";
import axios from "../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";

const state = reactive({
  active: false,
  im: {},
  order_id: 0,
  item_id: 0,
  meta_key: '',
  note: {type: '', message: ''},
})

function updateMessage() {
  Spinner.show();
  axios.post('admin/inner-message', {
    order_id: state.order_id,
    item_id: state.item_id,
    meta_key: state.meta_key,
    meta_value: state.im
  }).then(() => {
    Notify.success('Message has been updated successfully.');
  }).finally(() => {
    Spinner.hide();
  })
}

onMounted(() => {
  let buttons = document.querySelectorAll('.edit-im');
  buttons.forEach((_button: HTMLAnchorElement) => {
    _button.addEventListener('click', (event) => {
      event.preventDefault();

      state.active = true;
      const url = new URL(_button.href);
      const im = url.searchParams.get('im');
      if (im) {
        state.im = JSON.parse(im);
        state.order_id = parseInt(url.searchParams.get('order_id') ?? '0');
        state.item_id = parseInt(url.searchParams.get('item_id') ?? '0');
        state.meta_key = url.searchParams.get('meta_key') ?? '';
      }
    })
  })
})
</script>

<template>
  <div class="border-box-deep">
    <ShaplaModal :active="state.active" title="Edit Inner Message" @close="state.active = false">
      <ImEditor
          v-if="state.im"
          :im="state.im"
          @update="value => state.im = value"
      />
      <template v-slot:foot>
        <ShaplaButton theme="primary" @click="updateMessage">Update</ShaplaButton>
      </template>
    </ShaplaModal>
  </div>
</template>
