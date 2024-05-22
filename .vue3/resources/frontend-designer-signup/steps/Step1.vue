<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {computed, reactive} from "vue";
import {LoginInfoInterface} from "../interfaces.ts";
import {validateEmailFromServer,validateEmail} from "../store.ts";

interface StateInterface extends LoginInfoInterface {
  loading: boolean;
  emailErrors: string[];
}

const emit = defineEmits<{
  submit: [value: LoginInfoInterface]
}>()
const state = reactive<StateInterface>({
  name: '',
  email: '',
  loading: false,
  emailErrors: [],
})

const canSubmit = computed(() => state.name.length >= 2 && state.email.length >= 3 && validateEmail(state.email))
const onSubmit = () => {
  state.loading = true;
  validateEmailFromServer(state.email)
      .then(() => {
        emit('submit', state)
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
    <div class="signup__title text-center">
      <h2 class="text-3xl font-bold mb-0">
        Sign Up to <strong class="text-primary">Become</strong> a <strong class="text-primary">You Said It</strong>
        designer
      </h2>
      <hr class="w-48 border-primary mb-4">
      <p>Fill out the form below to create your free account.</p>
      <p>
        Already have an account ?
        <span class="signup__title__anchor text-primary cursor-pointer" data-target="login-modal">Sign In</span>
      </p>
    </div>
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 1 - <strong class="text-primary">Login Details</strong>
      </h3>
      <p>Lets get started, first lets create your new account</p>
    </div>
    <div class="signup__body max-w-[300px] mx-auto">
      <form action="" method="post" class="lg:px-4">
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="12">
            <label for="name">name</label>
            <input id="name" type="text" placeholder="Enter Name" class="yousaidit-login-modal__input"
                   v-model="state.name" autocomplete="name">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <label for="email">Email</label>
            <input id="email" type="email" placeholder="Enter Email" class="yousaidit-login-modal__input"
                   :class="{'has-error':state.emailErrors.length}" @input="onChangeEmail"
                   v-model="state.email" autocomplete="email">
            <p class="text-sm mt-1 text-red-600" v-if="state.emailErrors.length">{{ state.emailErrors[0] }}</p>
            <p class="text-sm mt-2">Registration confirmation will be emailed to you.</p>
          </ShaplaColumn>
        </ShaplaColumns>
      </form>
      <div class="mt-16 text-center">
        <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit" :loading="state.loading"
                      @click.prevent="onSubmit">Next
        </ShaplaButton>
      </div>
    </div>
  </div>
</template>
