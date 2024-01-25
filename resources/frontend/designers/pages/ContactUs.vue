<template>
  <div class="designer-contact-us">
    <columns multiline>
      <column :tablet="12">
        <text-field
            label="Subject"
            v-model="subject"
            :has-error="!!errors.subject.length"
            :validation-text="errors.subject"
        />
      </column>
      <column :tablet="12">
        <text-field
            type="textarea"
            label="Message"
            v-model="message"
            :has-error="!!errors.message.length"
            :validation-text="errors.message"
        />
      </column>
      <column :tablet="12">
        <shapla-button theme="primary" shadow @click="sendMessage">Send</shapla-button>
      </column>
    </columns>
  </div>
</template>

<script>
import {column, columns, shaplaButton, textField} from 'shapla-vue-components';
import axios from "@/utils/axios";;
import {Notify, Spinner} from "@shapla/vanilla-components";

export default {
  name: "ContactUs",
  components: {columns, column, textField, shaplaButton},
  data() {
    return {
      subject: '',
      message: '',
      errors: {
        subject: '',
        message: '',
      }
    }
  },
  methods: {
    sendMessage() {
      this.errors = {subject: '', message: '',};
      Spinner.hide();
      axios.post('designer-contact', {
        subject: this.subject,
        message: this.message,
      }).then(() => {
        Spinner.hide();
        Notify.success('Message has been send successfully.', 'Success!');
        this.subject = '';
        this.message = '';
      }).catch(errors => {
        if (typeof errors.response.data.message === "string") {
          Notify.error(errors.response.data.message, 'Error!');
        }
        if (errors.response.data.errors) {
          this.errors = errors.response.data.errors;
        }
        Spinner.hide();
      })
    }
  }
}
</script>

<style lang="scss">
.designer-contact-us {
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}
</style>
