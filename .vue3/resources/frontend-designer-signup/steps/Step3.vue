<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {computed, reactive} from "vue";

const emit = defineEmits<{
  submit: [value: string]
}>()

const state = reactive({
  paypal_email: ''
})

const canSubmit = computed(() => state.paypal_email.length >= 3)
const onSubmit = () => emit('submit', state.paypal_email);
</script>

<template>
  <div class="signup">
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 3 - <strong class="text-primary">Payment Details</strong>
      </h3>
      <p>Please add your paypal email address so that we can pay you commission.</p>
    </div>
    <div class="signup__body">
      <form action="">
        <ShaplaColumns multiline column-gap="1.5rem">
          <ShaplaColumn :tablet="12">
            <label for="name">PayPal Payment Email</label>
            <input id="name" type="email" placeholder="Enter PayPal Email for Payment" autocomplete="email"
                   class="yousaidit-login-modal__input" v-model="state.paypal_email">
          </ShaplaColumn>
        </ShaplaColumns>
      </form>
      <div class="mt-16 text-center">
        <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit" @click.prevent="onSubmit">Next
        </ShaplaButton>
      </div>
    </div>
  </div>
</template>
