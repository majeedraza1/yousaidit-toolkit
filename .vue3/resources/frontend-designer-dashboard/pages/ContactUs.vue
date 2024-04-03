<template>
  <div class="designer-contact-us">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="12">
        <ShaplaInput
            label="Subject"
            v-model="state.subject"
            :has-error="!!state.errors.subject.length"
            :validation-text="state.errors.subject"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaInput
            type="textarea"
            label="Message"
            v-model="state.message"
            :has-error="!!state.errors.message.length"
            :validation-text="state.errors.message"
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaButton theme="primary" shadow @click="sendMessage">Send</ShaplaButton>
      </ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaColumn ,
  ShaplaColumns ,
  ShaplaInput
} from '@shapla/vue-components';
import axios from "../../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {reactive} from "vue";

const state = reactive( {
  subject: '',
  message: '',
  errors: {
    subject: '',
    message: '',
  }
})

const sendMessage = () => {
  state.errors = {subject: '', message: '',};
  Spinner.hide();
  axios.post('designer-contact', {
    subject: state.subject,
    message: state.message,
  }).then(() => {
    Spinner.hide();
    Notify.success('Message has been send successfully.', 'Success!');
    state.subject = '';
    state.message = '';
  }).catch(errors => {
    if (typeof errors.response.data.message === "string") {
      Notify.error(errors.response.data.message, 'Error!');
    }
    if (errors.response.data.errors) {
      state.errors = errors.response.data.errors;
    }
    Spinner.hide();
  })
}
</script>
