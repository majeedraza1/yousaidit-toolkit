<script setup lang="ts">
import {computed, onMounted, reactive} from "vue";
import {ShaplaCheckbox, ShaplaCross, ShaplaModal} from "@shapla/vue-components";
import {Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios.ts";
import InputPassword from "./steps/InputPassword.vue";

const state = reactive({
  active: false,
  remember: false,
  user_login: '',
  password: '',
  errors: {
    user_login: [],
    password: [],
  },
})

const submitForm = () => {
  Spinner.show();
  axios.post('web-login', {
    username: state.user_login,
    password: state.password,
    remember: state.remember,
  }).then(() => {
    window.location.reload();
  }).catch(error => {
    Spinner.hide();
    if (error.response && error.response.data.errors) {
      state.errors = error.response.data.errors;
    }
  })
}

const lostPasswordUrl = computed(() => window.StackonetToolkit.lostPasswordUrl);
const signupUrl = computed(() => window.StackonetToolkit.signupUrl);
const canSubmit = computed(() => !!(state.user_login.length >= 4 && state.password.length >= 4))
const hasUserLoginError = computed(() => !!(state.errors.user_login && state.errors.user_login.length))
const hasPasswordError = computed(() => !!(state.errors.password && state.errors.password.length))

onMounted(() => {
  document.body.addEventListener('click', (event) => {
    const dataset = (event.target as HTMLElement).dataset;
    if (dataset.target && dataset.target === 'login-modal') {
      state.active = true;
    }
  })
})
</script>

<template>
  <ShaplaModal :active="state.active" type="box" :show-close-icon="false" :close-on-background-click="true">
    <div class="px-8 pb-8">
      <div class="flex justify-end">
        <ShaplaCross size="large" @click="state.active = false"/>
      </div>
      <div class="yousaidit-login-modal__title text-center mb-8">
        <div class="text-xl font-bold">
          Sign In to <span class="text-primary">You Said It</span>
        </div>
        <div class="">Sign In to manage your account</div>
      </div>
      <form action="" method="post">
        <div class="modal__content__body__body__input mb-4">
          <label for="username">Username</label>
          <input type="text" placeholder="username" class="yousaidit-login-modal__input" autocomplete="username"
                 v-model="state.user_login">
          <p v-if="hasUserLoginError">{{ state.errors.user_login[0] }}</p>
        </div>
        <div class="modal__content__body__body__input  mb-4">
          <label for="passwordModal">password</label>
          <InputPassword v-model="state.password" autocomplete="current-password"/>
          <p v-if="hasPasswordError">{{ state.errors.password[0] }}</p>
        </div>
        <div class="modal__content__body__body__input mb-4">
          <ShaplaCheckbox v-model="state.remember">Remember me</ShaplaCheckbox>
        </div>
        <div class="my-4">
          <button type="submit" class="yousaidit-login-modal__submit" @click.prevent="submitForm"
                  :disabled="!canSubmit">Sign In
          </button>
        </div>
        <div class="modal__content__body__body__foot text-center">
          <p class="mb-2">Do not have an account ? <a :href="signupUrl" class="text-primary">Sign Up</a></p>
          <a :href="lostPasswordUrl" class="text-primary">Forgot password ?</a>
        </div>
      </form>
    </div>
  </ShaplaModal>
</template>