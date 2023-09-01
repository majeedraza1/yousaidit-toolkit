<script lang="ts">
import {defineComponent} from 'vue'
import {modal, notification, shaplaButton, spinner} from 'shapla-vue-components';
import ImEditor from "@/admin/inner-message-editor/ImEditor.vue";
import axios from "@/utils/axios";

export default defineComponent({
  name: "App",
  components: {ImEditor, modal, shaplaButton, spinner, notification},
  data() {
    return {
      active: false,
      spinner: false,
      im: {},
      order_id: 0,
      item_id: 0,
      meta_key: '',
      note: {type: '', message: ''},
    }
  },
  methods: {
    updateMessage() {
      this.spinner = true;
      axios.post('admin/inner-message', {
        order_id: this.order_id,
        item_id: this.item_id,
        meta_key: this.meta_key,
        meta_value: this.im
      }).then(() => {
        this.note = {type: 'Success', message: 'Message has been updated successfully.'}
      }).finally(() => {
        this.spinner = false;
      })
    }
  },
  mounted() {
    let buttons = document.querySelectorAll('.edit-im');
    buttons.forEach((_button) => {
      _button.addEventListener('click', (event) => {
        event.preventDefault();

        this.active = true;
        const url = new URL(_button.href);
        const im = url.searchParams.get('im');
        if (im) {
          this.im = JSON.parse(im);
          this.order_id = parseInt(url.searchParams.get('order_id') ?? '0');
          this.item_id = parseInt(url.searchParams.get('item_id') ?? '0');
          this.meta_key = url.searchParams.get('meta_key') ?? '';
        }
      })
    })
  }
})
</script>

<template>
  <div class="border-box-deep">
    <modal :active="active" title="Edit Inner Message" @close="active = false">
      <ImEditor
          v-if="im"
          :im="im"
          @update="value => im = value"
      />
      <template v-slot:foot>
        <shapla-button theme="primary" @click="updateMessage">Update</shapla-button>
      </template>
    </modal>
    <spinner :active="spinner"/>
    <notification v-model="note"/>
  </div>
</template>
