<script lang="ts">
import {defineComponent} from 'vue'
import {modal, shaplaButton} from 'shapla-vue-components';
import ImEditor from "@/admin/inner-message-editor/ImEditor.vue";
import axios from "@/utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";

export default defineComponent({
  name: "App",
  components: {ImEditor, modal, shaplaButton},
  data() {
    return {
      active: false,
      im: {},
      order_id: 0,
      item_id: 0,
      meta_key: '',
      note: {type: '', message: ''},
    }
  },
  methods: {
    updateMessage() {
      Spinner.show();
      axios.post('admin/inner-message', {
        order_id: this.order_id,
        item_id: this.item_id,
        meta_key: this.meta_key,
        meta_value: this.im
      }).then(() => {
        Notify.success('Message has been updated successfully.');
      }).finally(() => {
        Spinner.hide();
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
  </div>
</template>
