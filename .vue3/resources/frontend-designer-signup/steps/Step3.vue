<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {computed, reactive} from "vue";
import {validateEmail, validatePayPalEmailFromServer} from "../store.ts";

const emit = defineEmits<{
  submit: [value: string]
}>()

const state = reactive({
  paypal_email: '',
  loading: false,
  emailErrors: [],
})

const canSubmit = computed(() => state.paypal_email.length >= 3 && validateEmail(state.paypal_email))
const onSubmit = () => {
  state.loading = true;
  validatePayPalEmailFromServer(state.paypal_email)
      .then(() => {
        emit('submit', state.paypal_email);
      })
      .catch(errors => {
        state.emailErrors = errors;
      })
      .finally(() => {
        state.loading = false;
      })
}

const onChangeEmail = () => {
  if (state.emailErrors.length) {
    state.emailErrors = [];
  }
}
</script>

<template>
  <div class="signup">
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 3 - <strong class="text-primary">Payment Details</strong>
      </h3>
      <p>Please add your paypal email address so that we can pay you commission.</p>
    </div>
    <div class="signup__body max-w-[300px] mx-auto">
      <form action="">
        <ShaplaColumns multiline column-gap="1.5rem">
          <ShaplaColumn :tablet="12">
            <label for="name">PayPal Payment Email</label>
            <input id="name" type="email" placeholder="Enter PayPal Email for Payment" autocomplete="email"
                   class="yousaidit-login-modal__input" v-model="state.paypal_email"
                   :class="{'has-error':state.emailErrors.length}" @input="onChangeEmail">
            <p class="text-sm mt-1 text-red-600" v-if="state.emailErrors.length">{{ state.emailErrors[0] }}</p>
          </ShaplaColumn>
        </ShaplaColumns>
      </form>
      <div class="mt-16 text-center">
        <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit" @click.prevent="onSubmit"
                      :loading="state.loading">Next
        </ShaplaButton>
      </div>
    </div>
  </div>
</template>
